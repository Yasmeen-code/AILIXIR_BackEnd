from fastapi import FastAPI, Request, HTTPException
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from typing import List
from pathlib import Path
from collections import OrderedDict
from rdkit import Chem
from DeepPurpose.utils import data_process
from DeepPurpose import DTI as models
import traceback
import csv
import torch

# --------------------------------------------------
# Project paths (absolute, derived from this file)
# --------------------------------------------------
PROJECT_ROOT = Path(__file__).resolve().parents[2]

MODEL_DIR = PROJECT_ROOT / "models" / "affinity"
TARGET_SEQUENCE_FILE = PROJECT_ROOT / "models" / "affinity" / "target_sequence.txt"

app = FastAPI(title="EGFR DeepPurpose REINVENT Adapter")

# ---------- Request models ----------
class PredictRequest(BaseModel):
    smiles: List[str]

# ---------- Helpers ----------
def load_default_egfr_sequence(sequence_path: Path) -> str:
    if not sequence_path.exists():
        raise FileNotFoundError(f"Target sequence file not found: {sequence_path}")

    seq = sequence_path.read_text(encoding="utf-8").strip()
    if not seq:
        raise RuntimeError(f"Target sequence file is empty: {sequence_path}")

    return seq

def canon(smi: str) -> str:
    try:
        m = Chem.MolFromSmiles(smi)
        return Chem.MolToSmiles(m, isomericSmiles=True) if m is not None else smi
    except Exception:
        return smi

# ---------- Load model and target ----------
seq = load_default_egfr_sequence(TARGET_SEQUENCE_FILE)
model = models.model_pretrained(path_dir=str(MODEL_DIR))

# ---------- Simple cache ----------
CACHE = OrderedDict()
CACHE_MAX = 50000

def cache_get(k: str):
    if k in CACHE:
        CACHE.move_to_end(k)
        return CACHE[k]
    return None

def cache_put(k: str, v: float):
    CACHE[k] = v
    CACHE.move_to_end(k)
    if len(CACHE) > CACHE_MAX:
        CACHE.popitem(last=False)

# ---------- Shared prediction core ----------
def predict_smiles_list(smiles_in: List[str]) -> List[float]:
    if not smiles_in:
        return []

    out = [None] * len(smiles_in)
    to_compute = []
    idx_map = []

    for i, smi in enumerate(smiles_in):
        k = canon(smi)
        v = cache_get(k)
        if v is None:
            to_compute.append(smi)
            idx_map.append((i, k))
        else:
            out[i] = float(v)

    if to_compute:
        X_target = [seq] * len(to_compute)
        y_dummy = [0.0] * len(to_compute)

        ret = data_process(
            to_compute,
            X_target,
            y_dummy,
            drug_encoding="Morgan",
            target_encoding="AAC",
            split_method="no_split",
        )

        X_pred = ret[0] if isinstance(ret, (tuple, list)) else ret

        with torch.inference_mode():
            preds = model.predict(X_pred)

        for j, p in enumerate(preds):
            i, k = idx_map[j]
            val = float(p)
            out[i] = val
            cache_put(k, val)

    return [0.0 if v is None else float(v) for v in out]

# ---------- Helpful debugging ----------
@app.exception_handler(RequestValidationError)
async def validation_exception_handler(request: Request, exc: RequestValidationError):
    body = await request.body()
    print("\n=== 422 VALIDATION ERROR ===")
    print("PATH:", request.url.path)
    print("ERRORS:", exc.errors())
    print("BODY:", body.decode("utf-8", errors="replace"))
    print("=== END 422 ===\n")
    return JSONResponse(status_code=422, content={"detail": exc.errors()})

# ---------- Legacy endpoint ----------
@app.post("/predict")
def predict(req: PredictRequest):
    try:
        preds = predict_smiles_list(req.smiles or [])
        return {"pred_pAff_mean": preds}
    except Exception as e:
        traceback.print_exc()
        raise HTTPException(status_code=500, detail=str(e))

# ---------- REINVENT-compatible endpoint ----------
@app.post("/reinvent_predict")
async def reinvent_predict(request: Request):
    try:
        payload = await request.json()

        # REINVENT in your logs sends {"smiles": [...]}
        if isinstance(payload, dict) and "smiles" in payload:
            smiles = payload.get("smiles") or []
            if not isinstance(smiles, list):
                raise HTTPException(status_code=422, detail="Field 'smiles' must be a list.")
            preds = predict_smiles_list(smiles)
            return {"pred_pAff_mean": preds}

        # Optional compatibility with list-of-items payload
        if isinstance(payload, list):
            smiles = []
            query_ids = []
            for item in payload:
                if not isinstance(item, dict):
                    raise HTTPException(status_code=422, detail="Each list item must be an object.")
                if "input_string" not in item or "query_id" not in item:
                    raise HTTPException(
                        status_code=422,
                        detail="Each item must contain 'input_string' and 'query_id'."
                    )
                smiles.append(item["input_string"])
                query_ids.append(str(item["query_id"]))

            preds = predict_smiles_list(smiles)
            successes = [
                {"query_id": qid, "output_value": float(pred)}
                for qid, pred in zip(query_ids, preds)
            ]
            return {"output": {"successes_list": successes}}

        raise HTTPException(
            status_code=422,
            detail="Unsupported request body. Expected either {'smiles': [...]} or a list of {'input_string','query_id'} items."
        )

    except HTTPException:
        raise
    except Exception as e:
        traceback.print_exc()
        raise HTTPException(status_code=500, detail=str(e))

# ---------- Health ----------
@app.get("/health")
def health():
    return {
        "status": "ok",
        "model_dir": str(MODEL_DIR),
        "target_sequence_file": str(TARGET_SEQUENCE_FILE),
        "seq_len": len(seq),
    }