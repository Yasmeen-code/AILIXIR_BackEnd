import os
import json
import time
import threading
import uuid
import shutil
import subprocess
from datetime import datetime
from pathlib import Path
from typing import Literal, Optional, List, Dict, Any

import pandas as pd
from fastapi import FastAPI, HTTPException, BackgroundTasks, Request
from fastapi.responses import FileResponse, JSONResponse
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field

from rdkit import Chem
from rdkit.Chem import AllChem


APP_ROOT = Path(__file__).resolve().parent
BUNDLE_DIR = APP_ROOT / "bundle"
OUTPUTS_DIR = APP_ROOT / "outputs"
JOBS_DIR = OUTPUTS_DIR / "jobs"

PUBLIC_BASE_URL = os.getenv("PUBLIC_BASE_URL", "").strip().rstrip("/")
DEEPPURPOSE_URL = os.getenv("DEEPPURPOSE_URL", "http://127.0.0.1:7860/reinvent_predict")
REINVENT_DEVICE = os.getenv("REINVENT_DEVICE", "cpu")
ADGPU_BIN = os.getenv("ADGPU_BIN", "")

def get_public_base_url(request: Request | None = None) -> str:
    configured = PUBLIC_BASE_URL.strip().rstrip("/")

    if configured and "localhost" not in configured and "127.0.0.1" not in configured:
        return configured

    if request is not None:
        forwarded_proto = request.headers.get("x-forwarded-proto")
        forwarded_host = request.headers.get("x-forwarded-host") or request.headers.get("host")

        if forwarded_host:
            proto = forwarded_proto or request.url.scheme or "https"
            return f"{proto}://{forwarded_host}".rstrip("/")

        return str(request.base_url).rstrip("/")

    return configured or "http://localhost:8000"

JOBS_DIR.mkdir(parents=True, exist_ok=True)

# Attempt to load DeepPurpose affinity service (optional)
try:
    from bundle.services.deeppurpose.serve_affinity import predict_smiles_list
    HAS_AFFINITY = True
except Exception:
    HAS_AFFINITY = False


app = FastAPI(
    title="Ailixir EGFR Pipeline API",
    version="1.0.0",
    description="Production API for EGFR generation, scoring, docking, and ligand export."
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=os.getenv("CORS_ALLOW_ORIGINS", "*").split(","),
    allow_credentials=False,
    allow_methods=["*"],
    allow_headers=["*"],
)


# -----------------------------
# Request models
# -----------------------------

class GenerateRequest(BaseModel):
    preset: str = Field(default="egfr_generator")
    num_molecules: int = Field(default=100, ge=1, le=5000)
    return_top_k: int = Field(default=20, ge=1, le=1000)
    docking_mode: Literal["off", "top_k", "all"] = "off"
    dock_top_k: int = Field(default=10, ge=1, le=1000)


class LigandExportRequest(BaseModel):
    smiles: str
    format: Literal["pdb", "pdbqt", "mol2"]


# -----------------------------
# Helpers
# -----------------------------

def new_job_id(prefix: str) -> str:
    stamp = datetime.utcnow().strftime("%Y%m%d_%H%M%S")
    short = uuid.uuid4().hex[:6]
    return f"{prefix}_{stamp}_{short}"


def make_file_meta(
    job_id: str,
    file_path: Path,
    request: Request | None = None,
    base_url: str | None = None,
) -> Dict[str, str]:
    rel_path = file_path.relative_to(JOBS_DIR / job_id).as_posix()
    relative_url = f"/files/jobs/{job_id}/{rel_path}"
    resolved_base_url = (base_url or get_public_base_url(request)).rstrip("/")

    return {
        "filename": file_path.name,
        "relative_url": relative_url,
        "download_url": f"{resolved_base_url}{relative_url}",
    }

def clean_record(record: Dict[str, Any]) -> Dict[str, Any]:
    """
    Public response cleaner.
    Removes internal paths and confusing technical fields.
    Keeps molecule scores and user-facing values only.
    """
    drop_keys = {
        "docking_pose_file",
        "file_path",
        "internal_path",
        "pose_file",
    }

    out = {}
    for k, v in record.items():
        if k in drop_keys:
            continue

        # Convert NaN to None for valid JSON
        try:
            if pd.isna(v):
                out[k] = None
            else:
                out[k] = v
        except Exception:
            out[k] = v

    return out


def dataframe_to_public_records(df: pd.DataFrame) -> List[Dict[str, Any]]:
    records = []
    for i, row in df.reset_index(drop=True).iterrows():
        item = clean_record(row.to_dict())
        item["rank"] = i + 1
        records.append(item)
    return records

class JobCancelled(Exception):
    pass


ACTIVE_PROCESSES: Dict[str, subprocess.Popen] = {}
ACTIVE_PROCESSES_LOCK = threading.Lock()


def cancel_flag_path(job_id: str) -> Path:
    return JOBS_DIR / job_id / "cancel.requested"


def is_cancel_requested(job_id: str) -> bool:
    return cancel_flag_path(job_id).exists()


def request_job_cancel(job_id: str) -> None:
    job_dir = JOBS_DIR / job_id
    job_dir.mkdir(parents=True, exist_ok=True)
    cancel_flag_path(job_id).touch(exist_ok=True)


def raise_if_cancelled(job_id: str) -> None:
    if is_cancel_requested(job_id):
        raise JobCancelled(f"Job {job_id} was cancelled.")


def terminate_active_process(job_id: str) -> bool:
    with ACTIVE_PROCESSES_LOCK:
        proc = ACTIVE_PROCESSES.get(job_id)

    if proc is None or proc.poll() is not None:
        return False

    proc.terminate()
    return True

def run_cmd(
    cmd: List[str],
    cwd: Optional[Path] = None,
    timeout: Optional[int] = None,
    job_id: Optional[str] = None,
):
    if job_id:
        raise_if_cancelled(job_id)

    if not job_id:
        return subprocess.run(
            cmd,
            cwd=str(cwd) if cwd else None,
            text=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            timeout=timeout,
            check=False,
        )

    started_at = time.time()

    proc = subprocess.Popen(
        cmd,
        cwd=str(cwd) if cwd else None,
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.STDOUT,
    )

    with ACTIVE_PROCESSES_LOCK:
        ACTIVE_PROCESSES[job_id] = proc

    stdout = ""

    try:
        while True:
            try:
                stdout, _ = proc.communicate(timeout=1)
                break
            except subprocess.TimeoutExpired:
                if is_cancel_requested(job_id):
                    proc.terminate()
                    try:
                        stdout, _ = proc.communicate(timeout=10)
                    except subprocess.TimeoutExpired:
                        proc.kill()
                        stdout, _ = proc.communicate()

                    raise JobCancelled(f"Job {job_id} was cancelled while command was running.")

                if timeout is not None and (time.time() - started_at) > timeout:
                    proc.kill()
                    stdout, _ = proc.communicate()
                    raise subprocess.TimeoutExpired(cmd, timeout, output=stdout)

        if is_cancel_requested(job_id):
            raise JobCancelled(f"Job {job_id} was cancelled.")

        return subprocess.CompletedProcess(
            args=cmd,
            returncode=proc.returncode,
            stdout=stdout,
            stderr=None,
        )

    finally:
        with ACTIVE_PROCESSES_LOCK:
            ACTIVE_PROCESSES.pop(job_id, None)

def canonicalize_smiles(smiles: str):
    mol = Chem.MolFromSmiles(smiles)
    if mol is None:
        raise HTTPException(status_code=400, detail="Invalid SMILES.")
    canonical = Chem.MolToSmiles(mol, isomericSmiles=True)
    return mol, canonical


def make_3d_mol(smiles: str):
    mol, canonical = canonicalize_smiles(smiles)
    mol = Chem.AddHs(mol)

    params = AllChem.ETKDGv3()
    params.randomSeed = 42

    status = AllChem.EmbedMolecule(mol, params)
    if status != 0:
        raise HTTPException(status_code=500, detail="3D embedding failed.")

    try:
        AllChem.UFFOptimizeMolecule(mol, maxIters=500)
    except Exception:
        pass

    return mol, canonical


def write_sdf_for_conversion(mol, sdf_path: Path):
    writer = Chem.SDWriter(str(sdf_path))
    writer.write(mol)
    writer.close()


def export_pdb(mol, out_path: Path):
    Chem.MolToPDBFile(mol, str(out_path))


def export_pdbqt(sdf_path: Path, out_path: Path):
    mk_prepare = shutil.which("mk_prepare_ligand.py") or shutil.which("mk_prepare_ligand")
    if mk_prepare is None:
        raise HTTPException(
            status_code=500,
            detail="Meeko mk_prepare_ligand.py was not found in PATH. Required for PDBQT export."
        )

    result = run_cmd([
        mk_prepare,
        "-i", str(sdf_path),
        "-o", str(out_path),
    ])

    if result.returncode != 0 or not out_path.exists():
        raise HTTPException(
            status_code=500,
            detail=f"PDBQT export failed: {result.stdout[:1000]}"
        )


def export_mol2(sdf_path: Path, out_path: Path):
    obabel = shutil.which("obabel")
    if obabel is None:
        raise HTTPException(
            status_code=500,
            detail="Open Babel obabel was not found in PATH. Required for MOL2 export."
        )

    result = run_cmd([
        obabel,
        "-isdf", str(sdf_path),
        "-omol2",
        "-O", str(out_path),
    ])

    if result.returncode != 0 or not out_path.exists():
        raise HTTPException(
            status_code=500,
            detail=f"MOL2 export failed: {result.stdout[:1000]}"
        )


def count_docked(df: pd.DataFrame) -> int:
    if "docking_status" not in df.columns:
        return 0
    return int((df["docking_status"] == "completed").sum())

def job_status_path(job_id: str) -> Path:
    return JOBS_DIR / job_id / "job_status.json"


def make_api_url(
    path: str,
    request: Request | None = None,
    base_url: str | None = None,
) -> str:
    if not path.startswith("/"):
        path = "/" + path

    resolved_base_url = (base_url or get_public_base_url(request)).rstrip("/")
    return f"{resolved_base_url}{path}"


def write_job_status(job_id: str, payload: Dict[str, Any], base_url: str | None = None):
    job_dir = JOBS_DIR / job_id
    job_dir.mkdir(parents=True, exist_ok=True)

    base = {
        "job_id": job_id,
        "status_url": make_api_url(f"/jobs/{job_id}", base_url=base_url),
        "result_url": make_api_url(f"/jobs/{job_id}/result", base_url=base_url),
    }
    base.update(payload)

    path = job_status_path(job_id)
    tmp_path = path.with_suffix(path.suffix + ".tmp")

    tmp_path.write_text(
        json.dumps(base, indent=2, ensure_ascii=False),
        encoding="utf-8"
    )

    tmp_path.replace(path)


def read_job_status(job_id: str, base_url: str | None = None) -> Dict[str, Any]:
    path = job_status_path(job_id)
    if not path.exists():
        raise HTTPException(status_code=404, detail="Job not found.")

    last_error = None

    for _ in range(5):
        try:
            text = path.read_text(encoding="utf-8").strip()
            if text:
                return json.loads(text)
        except json.JSONDecodeError as exc:
            last_error = exc

        time.sleep(0.1)

    if last_error:
        return {
            "job_id": job_id,
            "status_url": make_api_url(f"/jobs/{job_id}", base_url=base_url),
            "result_url": make_api_url(f"/jobs/{job_id}/result", base_url=base_url),
            "status": "running",
            "stage": "status_update",
            "message": "Job status is being updated. Retry shortly."
        }

    return {
        "job_id": job_id,
        "status_url": make_api_url(f"/jobs/{job_id}", base_url=base_url),
        "result_url": make_api_url(f"/jobs/{job_id}/result", base_url=base_url),
        "status": "running",
        "stage": "status_update",
        "message": "Job status is not ready yet."
    }

def run_generate_job(job_id: str, req_data: Dict[str, Any], base_url: str):
    job_dir = JOBS_DIR / job_id

    try:
        req = GenerateRequest(**req_data)

        raise_if_cancelled(job_id)

        write_job_status(job_id, {
            "status": "running",
            "stage": "sampling",
            "message": "Running REINVENT molecule generation",
            "request": req_data,
        }, base_url=base_url)

        runtime_config = write_runtime_sampling_config(job_dir, req.num_molecules)

        raise_if_cancelled(job_id)
        run_reinvent_sampling(runtime_config, job_dir, job_id)

        raise_if_cancelled(job_id)

        write_job_status(job_id, {
            "status": "running",
            "stage": "enrichment",
            "message": "Running RDKit descriptors and DeepPurpose predictions",
            "request": req_data,
        }, base_url=base_url)

        enriched_csv = run_enrichment(job_dir, req.return_top_k, job_id)

        raise_if_cancelled(job_id)

        write_job_status(job_id, {
            "status": "running",
            "stage": "docking",
            "message": f"Running docking mode: {req.docking_mode}",
            "request": req_data,
        }, base_url=base_url)

        final_csv = run_optional_docking(job_dir, enriched_csv, req, job_id)

        raise_if_cancelled(job_id)

        df = pd.read_csv(final_csv)

        if "docking_pose_file" in df.columns:
            df = df.drop(columns=["docking_pose_file"])

        clean_csv = job_dir / "generated_results.csv"
        clean_json = job_dir / "generated_results.json"

        df.to_csv(clean_csv, index=False)

        results = dataframe_to_public_records(df)

        result_payload = {
            "job_id": job_id,
            "status": "completed",
            "preset": req.preset,
            "docking_mode": req.docking_mode,
            "summary": {
                "num_requested": req.num_molecules,
                "num_generated": int(len(pd.read_csv(job_dir / "generated_smiles.csv"))),
                "num_valid": int((df["valid"] == True).sum()) if "valid" in df.columns else None,
                "num_returned": int(len(df)),
                "num_docked": count_docked(df),
            },
            "files": {
                "csv": make_file_meta(job_id, clean_csv, base_url=base_url),
                "json": make_file_meta(job_id, clean_json, base_url=base_url),
            },
            "results": results,
            "warnings": [
                "Outputs are computational predictions only."
            ],
        }

        clean_json.write_text(
            json.dumps(result_payload, indent=2, ensure_ascii=False),
            encoding="utf-8"
        )

        write_job_status(job_id, {
            "status": "completed",
            "stage": "completed",
            "message": "Generation job completed",
            "request": req_data,
            "summary": result_payload["summary"],
            "files": result_payload["files"],
        }, base_url=base_url)

    except JobCancelled:
        write_job_status(job_id, {
            "status": "cancelled",
            "stage": "cancelled",
            "message": "Generation job cancelled by user.",
            "request": req_data,
        }, base_url=base_url)

    except Exception as e:
        if isinstance(e, HTTPException):
            detail = e.detail
        else:
            detail = str(e)

        write_job_status(job_id, {
            "status": "failed",
            "stage": "failed",
            "message": "Generation job failed",
            "request": req_data,
            "error": detail,
        }, base_url=base_url)

def rewrite_public_urls(payload: Dict[str, Any], base_url: str) -> Dict[str, Any]:
    base_url = base_url.rstrip("/")

    for key in ("status_url", "result_url"):
        value = payload.get(key)
        if isinstance(value, str) and value.startswith(("http://localhost", "http://127.0.0.1")):
            path = "/" + value.split("/", 3)[3] if "/" in value[8:] else ""
            payload[key] = f"{base_url}{path}"

    files = payload.get("files")
    if isinstance(files, dict):
        for meta in files.values():
            if isinstance(meta, dict):
                relative_url = meta.get("relative_url")
                if relative_url:
                    meta["download_url"] = f"{base_url}{relative_url}"

    file_meta = payload.get("file")
    if isinstance(file_meta, dict):
        relative_url = file_meta.get("relative_url")
        if relative_url:
            file_meta["download_url"] = f"{base_url}{relative_url}"

    return payload

# -----------------------------
# Endpoints
# -----------------------------

@app.get("/health")
def health(request: Request):
    return {
        "status": "ok",
        "bundle_exists": BUNDLE_DIR.exists(),
        "models_generator_exists": (BUNDLE_DIR / "models" / "generator" / "egfr_generator.chkpt").exists(),
        "models_affinity_exists": (BUNDLE_DIR / "models" / "affinity" / "model.pt").exists(),
        "docking_grid_exists": (BUNDLE_DIR / "docking" / "maps_current" / "4WKQ_receptor_v5_SBr.maps.fld").exists(),
        "public_base_url": get_public_base_url(request),
        "reinvent_device": REINVENT_DEVICE,
        "adgpu_bin": ADGPU_BIN or None,
    }


@app.post("/reinvent_predict")
async def reinvent_predict(request: Request):
    if not HAS_AFFINITY:
        return JSONResponse(
            status_code=503,
            content={"error": "Affinity prediction service not available (DeepPurpose not installed)"}
        )
    payload = await request.json()
    smiles = payload.get("smiles", []) if isinstance(payload, dict) else []
    preds = predict_smiles_list(smiles)
    return {"pred_pAff_mean": preds}


@app.get("/files/jobs/{job_id}/{path:path}")
def get_job_file(job_id: str, path: str):
    root = (JOBS_DIR / job_id).resolve()
    target = (root / path).resolve()

    if not str(target).startswith(str(root)):
        raise HTTPException(status_code=400, detail="Invalid file path.")

    if not target.exists() or not target.is_file():
        raise HTTPException(status_code=404, detail="File not found.")

    return FileResponse(
        path=str(target),
        filename=target.name,
        media_type="application/octet-stream"
    )


@app.post("/ligands/export")
def export_ligand(req: LigandExportRequest, request: Request):
    job_id = new_job_id("lig")
    job_dir = JOBS_DIR / job_id
    job_dir.mkdir(parents=True, exist_ok=True)

    mol, canonical = make_3d_mol(req.smiles)

    sdf_path = job_dir / "ligand_3d.sdf"
    write_sdf_for_conversion(mol, sdf_path)

    if req.format == "pdb":
        out_path = job_dir / "ligand_3d.pdb"
        export_pdb(mol, out_path)

    elif req.format == "pdbqt":
        out_path = job_dir / "ligand_3d.pdbqt"
        export_pdbqt(sdf_path, out_path)

    elif req.format == "mol2":
        out_path = job_dir / "ligand_3d.mol2"
        export_mol2(sdf_path, out_path)

    else:
        raise HTTPException(status_code=400, detail="Unsupported format.")

    meta = make_file_meta(job_id, out_path, request)
    meta["format"] = req.format

    return {
        "job_id": job_id,
        "status": "completed",
        "canonical_smiles": canonical,
        "format": req.format,
        "file": meta,
    }


def write_runtime_sampling_config(job_dir: Path, num_molecules: int) -> Path:
    """
    Create a job-specific REINVENT sampling config.
    Keeps bundle config untouched.
    """
    template_path = BUNDLE_DIR / "configs" / "sampling_model.bundle.toml"
    if not template_path.exists():
        raise HTTPException(status_code=500, detail=f"Sampling config not found: {template_path}")

    text = template_path.read_text(encoding="utf-8")

    # Force runtime device and job-specific outputs.
    text = text.replace('device = "cuda:0"', f'device = "{REINVENT_DEVICE}"')
    text = text.replace('json_out_config = "outputs/generate/sampling_resolved.json"', f'json_out_config = "{(job_dir / "sampling_resolved.json").as_posix()}"')
    text = text.replace('model_file = "models/generator/egfr_generator.chkpt"', f'model_file = "{(BUNDLE_DIR / "models" / "generator" / "egfr_generator.chkpt").as_posix()}"')
    text = text.replace('output_file = "outputs/generate/generated_smiles.csv"', f'output_file = "{(job_dir / "generated_smiles.csv").as_posix()}"')

    # Replace num_smiles line safely.
    lines = []
    replaced = False
    for line in text.splitlines():
        if line.strip().startswith("num_smiles"):
            lines.append(f"num_smiles = {int(num_molecules)}")
            replaced = True
        else:
            lines.append(line)

    if not replaced:
        lines.append(f"num_smiles = {int(num_molecules)}")

    runtime_config = job_dir / "sampling_runtime.toml"
    runtime_config.write_text("\n".join(lines) + "\n", encoding="utf-8")
    return runtime_config


def find_reinvent_command() -> List[str]:
    """
    Try common REINVENT4 entrypoints.
    Docker will normally expose `reinvent`.
    Local fallback may use python -m reinvent.Reinvent.
    """
    if shutil.which("reinvent"):
        return ["reinvent"]

    return [os.getenv("PYTHON", "python"), "-m", "reinvent.Reinvent"]


def run_reinvent_sampling(runtime_config: Path, job_dir: Path, job_id: str):
    cmd = find_reinvent_command() + ["-l", str(job_dir / "reinvent_sampling.log"), str(runtime_config)]

    result = run_cmd(cmd, cwd=BUNDLE_DIR, timeout=1800, job_id=job_id)

    log_path = job_dir / "reinvent_command_output.log"
    log_path.write_text(result.stdout or "", encoding="utf-8", errors="replace")

    if result.returncode != 0:
        raise HTTPException(
            status_code=500,
            detail={
                "message": "REINVENT sampling failed.",
                "command": " ".join(cmd),
                "log_tail": (result.stdout or "")[-2000:],
            }
        )


def run_enrichment(job_dir: Path, return_top_k: int, job_id: str) -> Path:
    input_csv = job_dir / "generated_smiles.csv"
    enriched_csv = job_dir / "generated_smiles_enriched.csv"

    if not input_csv.exists():
        raise HTTPException(status_code=500, detail=f"Generated SMILES CSV not found: {input_csv}")

    script = BUNDLE_DIR / "tools" / "enrich_generated.py"
    if not script.exists():
        raise HTTPException(status_code=500, detail=f"Enrichment script not found: {script}")

    cmd = [
        os.getenv("PYTHON", "python"),
        str(script),
        "--input", str(input_csv),
        "--output", str(enriched_csv),
        "--affinity-url", DEEPPURPOSE_URL,
        "--top-k", str(return_top_k),
    ]

    result = run_cmd(cmd, cwd=BUNDLE_DIR, timeout=1800, job_id=job_id)

    (job_dir / "enrichment.log").write_text(result.stdout or "", encoding="utf-8", errors="replace")

    if result.returncode != 0:
        raise HTTPException(
            status_code=500,
            detail={
                "message": "Enrichment failed.",
                "command": " ".join(cmd),
                "log_tail": (result.stdout or "")[-2000:],
            }
        )

    return enriched_csv


def run_optional_docking(job_dir: Path, enriched_csv: Path, req: GenerateRequest, job_id: str) -> Path:
    final_csv = job_dir / "generated_results.csv"

    script = BUNDLE_DIR / "tools" / "dock_enriched.py"
    if not script.exists():
        raise HTTPException(status_code=500, detail=f"Docking script not found: {script}")

    grid_file = BUNDLE_DIR / "docking" / "maps_current" / "4WKQ_receptor_v5_SBr.maps.fld"

    cmd = [
        os.getenv("PYTHON", "python"),
        str(script),
        "--input", str(enriched_csv),
        "--output", str(final_csv),
        "--docking-mode", req.docking_mode,
        "--dock-top-k", str(req.dock_top_k),
        "--grid-file", str(grid_file),
        "--work-dir", str(job_dir / "docking"),
    ]

    if req.docking_mode != "off":
        if not ADGPU_BIN:
            raise HTTPException(
                status_code=500,
                detail="Docking requested, but ADGPU_BIN is not set. Set AutoDock-GPU binary path in production."
            )
        cmd += ["--adgpu-bin", ADGPU_BIN]

    result = run_cmd(cmd, cwd=BUNDLE_DIR, timeout=7200, job_id=job_id)

    (job_dir / "docking.log").write_text(result.stdout or "", encoding="utf-8", errors="replace")

    if result.returncode != 0:
        raise HTTPException(
            status_code=500,
            detail={
                "message": "Docking step failed.",
                "command": " ".join(cmd),
                "log_tail": (result.stdout or "")[-2000:],
            }
        )

    return final_csv


@app.post("/generate", status_code=202)
def submit_generate(req: GenerateRequest, background_tasks: BackgroundTasks, request: Request):
    job_id = new_job_id("gen")
    job_dir = JOBS_DIR / job_id
    job_dir.mkdir(parents=True, exist_ok=True)

    req_data = req.model_dump()
    base_url = get_public_base_url(request)
    write_job_status(job_id, {
        "status": "queued",
        "stage": "queued",
        "message": "Generation job accepted and queued",
        "request": req_data,
    }, base_url=base_url)

    background_tasks.add_task(run_generate_job, job_id, req_data, base_url)

    return {
        "job_id": job_id,
        "status": "queued",
        "message": "Generation job accepted. Poll status_url until completed.",
        "status_url": make_api_url(f"/jobs/{job_id}", base_url=base_url),
        "result_url": make_api_url(f"/jobs/{job_id}/result", base_url=base_url),
    }


@app.get("/jobs/{job_id}")
def get_job_status(job_id: str, request: Request):
    base_url = get_public_base_url(request)
    status = read_job_status(job_id, base_url=base_url)
    return rewrite_public_urls(status, base_url)

@app.post("/jobs/{job_id}/cancel", status_code=202)
def cancel_job(job_id: str, request: Request):
    base_url = get_public_base_url(request)
    job_dir = JOBS_DIR / job_id

    if not job_dir.exists():
        raise HTTPException(status_code=404, detail="Job not found.")

    status = read_job_status(job_id, base_url=base_url)
    current_status = status.get("status")

    if current_status in {"completed", "failed", "cancelled"}:
        status["message"] = f"Job already {current_status}."
        return rewrite_public_urls(status, base_url)

    request_job_cancel(job_id)
    process_terminated = terminate_active_process(job_id)

    write_job_status(job_id, {
        "status": "cancel_requested",
        "stage": status.get("stage", "cancel_requested"),
        "message": "Cancellation requested. The job will stop at the next safe checkpoint.",
        "process_terminated": process_terminated,
        "request": status.get("request"),
    }, base_url=base_url)

    return rewrite_public_urls(read_job_status(job_id, base_url=base_url), base_url)

@app.get("/jobs/{job_id}/result")
def get_job_result(job_id: str, request: Request):
    base_url = get_public_base_url(request)
    status = read_job_status(job_id, base_url=base_url)

    if status.get("status") == "failed":
        return JSONResponse(status_code=500, content=rewrite_public_urls(status, base_url))

    if status.get("status") != "completed":
        return JSONResponse(status_code=202, content=rewrite_public_urls(status, base_url))

    result_path = JOBS_DIR / job_id / "generated_results.json"
    if not result_path.exists():
        raise HTTPException(status_code=404, detail="Result file not found.")

    payload = json.loads(result_path.read_text(encoding="utf-8"))
    return rewrite_public_urls(payload, base_url)
