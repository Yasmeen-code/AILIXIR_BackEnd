#!/usr/bin/env python3

import argparse
import json
from pathlib import Path

import pandas as pd
import requests
import os
import sys

from rdkit import Chem
from rdkit.Chem import Descriptors, Crippen, Lipinski, rdMolDescriptors, QED


def canonicalize(smiles: str):
    mol = Chem.MolFromSmiles(str(smiles))
    if mol is None:
        return None, None
    can = Chem.MolToSmiles(mol, isomericSmiles=True)
    return mol, can


def try_sa_score(mol):
    """
    Compute RDKit SA score.

    RDKit Contrib is installed in different locations depending on the package source.
    Common conda location:
        $CONDA_PREFIX/share/RDKit/Contrib/SA_Score/sascorer.py

    Lower score means easier synthesis.
    Higher score means harder synthesis.
    """
    import_paths = []

    # First try the normal Python import.
    try:
        from rdkit.Contrib.SA_Score import sascorer
        return float(sascorer.calculateScore(mol))
    except Exception:
        pass

    # Then search common RDKit Contrib locations.
    conda_prefix = os.environ.get("CONDA_PREFIX")
    if conda_prefix:
        import_paths.append(Path(conda_prefix) / "share" / "RDKit" / "Contrib" / "SA_Score")

    import_paths.extend([
        Path("/opt/conda/envs/ailixir/share/RDKit/Contrib/SA_Score"),
        Path("/opt/conda/share/RDKit/Contrib/SA_Score"),
        Path("/usr/share/RDKit/Contrib/SA_Score"),
        Path("/usr/local/share/RDKit/Contrib/SA_Score"),
    ])

    for p in import_paths:
        sascorer_file = p / "sascorer.py"
        fpscores_file = p / "fpscores.pkl.gz"

        if sascorer_file.exists() and fpscores_file.exists():
            sys.path.insert(0, str(p))
            try:
                import sascorer
                return float(sascorer.calculateScore(mol))
            except Exception:
                continue

    return None


def compute_properties(smiles: str):
    mol, can = canonicalize(smiles)

    if mol is None:
        return {
            "valid": False,
            "canonical_smiles": None,
            "mw": None,
            "logp": None,
            "tpsa": None,
            "hbd": None,
            "hba": None,
            "rot_bonds": None,
            "qed": None,
            "sa_score": None,
        }

    return {
        "valid": True,
        "canonical_smiles": can,
        "mw": float(Descriptors.MolWt(mol)),
        "logp": float(Crippen.MolLogP(mol)),
        "tpsa": float(rdMolDescriptors.CalcTPSA(mol)),
        "hbd": int(Lipinski.NumHDonors(mol)),
        "hba": int(Lipinski.NumHAcceptors(mol)),
        "rot_bonds": int(Lipinski.NumRotatableBonds(mol)),
        "qed": float(QED.qed(mol)),
        "sa_score": try_sa_score(mol),
    }


def call_affinity_api(smiles_list, url: str):
    if not smiles_list:
        return []

    try:
        response = requests.post(
            url,
            json={"smiles": smiles_list},
            timeout=300,
        )
        response.raise_for_status()

        payload = response.json()
        preds = payload.get("pred_pAff_mean")

        if preds is None:
            raise RuntimeError(
                f"Affinity API response missing 'pred_pAff_mean'. Response was: {json.dumps(payload)[:500]}"
            )

        return [float(x) for x in preds]

    except Exception as e:
        print(f"Warning: Affinity API call failed ({e}). Returning null predictions.")
        return [None] * len(smiles_list)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--input", required=True, help="Input generated_smiles.csv from REINVENT sampling.")
    parser.add_argument("--output", required=True, help="Output enriched CSV.")
    parser.add_argument(
        "--affinity-url",
        default="http://127.0.0.1:8001/reinvent_predict",
        help="DeepPurpose/FastAPI affinity endpoint.",
    )
    parser.add_argument(
        "--top-k",
        type=int,
        default=0,
        help="If >0, keep only top K rows after sorting by pred_pAff_mean desc then QED desc.",
    )
    args = parser.parse_args()

    input_path = Path(args.input)
    output_path = Path(args.output)
    output_path.parent.mkdir(parents=True, exist_ok=True)

    df = pd.read_csv(input_path)

    if "SMILES" not in df.columns:
        raise ValueError(f"Input file must contain a SMILES column. Found columns: {list(df.columns)}")

    props = [compute_properties(smi) for smi in df["SMILES"].tolist()]
    props_df = pd.DataFrame(props)

    out = pd.concat([df.reset_index(drop=True), props_df.reset_index(drop=True)], axis=1)

    valid_mask = out["valid"] == True
    valid_smiles = out.loc[valid_mask, "canonical_smiles"].tolist()

    pred_values = call_affinity_api(valid_smiles, args.affinity_url)

    out["pred_pAff_mean"] = None
    out.loc[valid_mask, "pred_pAff_mean"] = pred_values

    # Docking is optional in v1. Keep stable columns for frontend/backend filters.
    out["docking_score"] = None
    out["docking_status"] = "not_run"
    out["docking_pose_file"] = None

    # Sort for display only.
    # This is not a final scientific ranking.
    out = out.sort_values(
        by=["pred_pAff_mean", "qed"],
        ascending=[False, False],
    )

    if args.top_k and args.top_k > 0:
        out = out.head(args.top_k)

    out.to_csv(output_path, index=False)

    print(f"Wrote: {output_path}")
    print(f"Rows: {len(out)}")
    print("Columns:", ", ".join(out.columns))


if __name__ == "__main__":
    main()
