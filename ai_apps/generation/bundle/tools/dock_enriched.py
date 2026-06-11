#!/usr/bin/env python3

import argparse
import os
import re
import shutil
import subprocess
from pathlib import Path

import pandas as pd
from rdkit import Chem
from rdkit.Chem import AllChem


ENERGY_RE = re.compile(
    r"Estimated Free Energy of Binding\s*=\s*([-+]?\d+(?:\.\d+)?)\s*kcal/mol",
    re.IGNORECASE,
)


def parse_best_binding_energy(dlg_path: Path):
    if not dlg_path.exists():
        return None

    text = dlg_path.read_text(errors="ignore")
    values = [float(x) for x in ENERGY_RE.findall(text)]

    if not values:
        return None

    # More negative is better.
    return min(values)


def make_3d_sdf(smiles: str, sdf_path: Path, seed: int = 42):
    mol = Chem.MolFromSmiles(str(smiles))
    if mol is None:
        raise ValueError(f"Invalid SMILES: {smiles}")

    mol = Chem.AddHs(mol)

    params = AllChem.ETKDGv3()
    params.randomSeed = seed

    status = AllChem.EmbedMolecule(mol, params)
    if status != 0:
        raise RuntimeError(f"3D embedding failed for SMILES: {smiles}")

    try:
        AllChem.UFFOptimizeMolecule(mol, maxIters=500)
    except Exception:
        pass

    sdf_path.parent.mkdir(parents=True, exist_ok=True)
    writer = Chem.SDWriter(str(sdf_path))
    writer.write(mol)
    writer.close()


def run_cmd(cmd, cwd=None):
    return subprocess.run(
        cmd,
        cwd=str(cwd) if cwd else None,
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.STDOUT,
        check=False,
    )


def dock_one(smiles, ligand_id, work_dir, adgpu_bin, grid_file, nrun):
    work_dir.mkdir(parents=True, exist_ok=True)

    sdf_path = work_dir / f"{ligand_id}.sdf"
    pdbqt_path = work_dir / f"{ligand_id}.pdbqt"

    make_3d_sdf(smiles, sdf_path)

    mk_prepare = shutil.which("mk_prepare_ligand.py")
    if mk_prepare is None:
        raise RuntimeError("mk_prepare_ligand.py was not found in PATH.")

    prep = run_cmd([
        mk_prepare,
        "-i", str(sdf_path),
        "-o", str(pdbqt_path),
    ])

    if prep.returncode != 0 or not pdbqt_path.exists():
        raise RuntimeError(f"Meeko ligand preparation failed:\n{prep.stdout}")

    dock = run_cmd([
        adgpu_bin,
        "--ffile", str(grid_file.resolve()),
        "--lfile", str(pdbqt_path.resolve()),
        "--nrun", str(nrun),
    ], cwd=work_dir)

    if dock.returncode != 0:
        raise RuntimeError(f"AutoDock-GPU failed:\n{dock.stdout}")

    candidates = sorted(work_dir.glob("*.dlg"))
    if not candidates:
        raise RuntimeError(f"No DLG file was produced in {work_dir}")

    dlg_path = candidates[0]
    score = parse_best_binding_energy(dlg_path)

    if score is None:
        raise RuntimeError(f"Could not parse binding energy from {dlg_path}")

    xml_candidates = sorted(work_dir.glob("*.xml"))
    result_file = xml_candidates[0] if xml_candidates else dlg_path

    return score, str(result_file)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--input", required=True, help="Input enriched CSV.")
    parser.add_argument("--output", required=True, help="Output CSV with docking columns.")
    parser.add_argument("--docking-mode", choices=["off", "top_k", "all"], default="off")
    parser.add_argument("--dock-top-k", type=int, default=10)
    parser.add_argument("--nrun", type=int, default=8)
    parser.add_argument("--adgpu-bin", default=os.environ.get("ADGPU_BIN"))
    parser.add_argument("--grid-file", default="docking/maps_current/4WKQ_receptor_v5_SBr.maps.fld")
    parser.add_argument("--work-dir", default="outputs/docking")
    args = parser.parse_args()

    df = pd.read_csv(args.input)

    # Ensure stable dtypes for docking columns.
    if "docking_score" not in df.columns:
        df["docking_score"] = None
    if "docking_status" not in df.columns:
        df["docking_status"] = "not_run"
    if "docking_pose_file" not in df.columns:
        df["docking_pose_file"] = None

    df["docking_status"] = df["docking_status"].astype("object")
    df["docking_pose_file"] = df["docking_pose_file"].astype("object")

    if args.docking_mode == "off":
        df["docking_score"] = None
        df["docking_status"] = "not_run"
        df["docking_pose_file"] = None
        Path(args.output).parent.mkdir(parents=True, exist_ok=True)
        df.to_csv(args.output, index=False)
        print(f"Docking mode off. Wrote: {args.output}")
        return

    if not args.adgpu_bin:
        raise RuntimeError("Set ADGPU_BIN or pass --adgpu-bin.")

    adgpu_bin = Path(args.adgpu_bin)
    if not adgpu_bin.exists():
        raise FileNotFoundError(f"AutoDock-GPU binary not found: {adgpu_bin}")

    grid_file = Path(args.grid_file)
    if not grid_file.exists():
        raise FileNotFoundError(f"Grid file not found: {grid_file}")

    if "canonical_smiles" not in df.columns:
        raise ValueError("Input CSV must contain canonical_smiles column.")

    if args.docking_mode == "all":
        indices = list(df.index)
    else:
        indices = list(df.index[: args.dock_top_k])

    work_root = Path(args.work_dir)
    work_root.mkdir(parents=True, exist_ok=True)

    for count, idx in enumerate(indices, start=1):
        smiles = df.at[idx, "canonical_smiles"]
        ligand_id = f"ligand_{count:04d}"

        try:
            score, result_file = dock_one(
                smiles=smiles,
                ligand_id=ligand_id,
                work_dir=work_root / ligand_id,
                adgpu_bin=str(adgpu_bin),
                grid_file=grid_file,
                nrun=args.nrun,
            )
            df.at[idx, "docking_score"] = score
            df.at[idx, "docking_status"] = "completed"
            df.at[idx, "docking_pose_file"] = result_file
            print(f"[OK] {ligand_id}: {score:.2f} kcal/mol")

        except Exception as e:
            df.at[idx, "docking_score"] = None
            df.at[idx, "docking_status"] = f"failed: {str(e)[:160]}"
            df.at[idx, "docking_pose_file"] = None
            print(f"[FAILED] {ligand_id}: {e}")

    Path(args.output).parent.mkdir(parents=True, exist_ok=True)
    df.to_csv(args.output, index=False)
    print(f"Wrote: {args.output}")


if __name__ == "__main__":
    main()
