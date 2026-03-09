"""
SMILES → PDBQT Converter
=========================
Usage: python smiles_to_pdbqt.py <smiles_string> <output_pdbqt_path>

Pipeline:
  1. RDKit: parse SMILES → add Hs → 3D embed → energy minimize → write .sdf
  2. OpenBabel (obabel): .sdf → .pdbqt (adds partial charges, atom types, torsions)

Output: JSON to stdout  {"status": "success"|"error", ...}
"""

import sys
import json
import os
import tempfile
import subprocess

def main():
    if len(sys.argv) < 3:
        print(json.dumps({
            "status": "error",
            "message": "Usage: python smiles_to_pdbqt.py <smiles_string> <output_pdbqt_path>"
        }))
        sys.exit(1)

    smiles = sys.argv[1]
    output_path = sys.argv[2]

    try:
        # ---- Step A: RDKit  (SMILES → 3D SDF) ----
        from rdkit import Chem
        from rdkit.Chem import AllChem

        mol = Chem.MolFromSmiles(smiles)
        if mol is None:
            raise ValueError(f"RDKit could not parse SMILES: {smiles}")

        # Add hydrogens for proper 3D geometry
        mol = Chem.AddHs(mol)

        # Generate 3D coordinates (ETKDG v3 for best quality)
        embed_result = AllChem.EmbedMolecule(mol, AllChem.ETKDGv3())
        if embed_result == -1:
            # Fallback: use random coordinates
            embed_result = AllChem.EmbedMolecule(mol, AllChem.ETKDGv3())
            if embed_result == -1:
                raise ValueError("Failed to generate 3D coordinates for molecule")

        # Energy minimization with MMFF94 force field
        try:
            result = AllChem.MMFFOptimizeMolecule(mol, maxIters=2000)
            if result == -1:
                # MMFF failed, try UFF
                AllChem.UFFOptimizeMolecule(mol, maxIters=2000)
        except Exception:
            # If both fail, proceed with unoptimized geometry
            pass

        # Write to temporary SDF file
        sdf_fd, sdf_path = tempfile.mkstemp(suffix=".sdf")
        os.close(sdf_fd)

        writer = Chem.SDWriter(sdf_path)
        writer.write(mol)
        writer.close()

        # ---- Step B: OpenBabel  (SDF → PDBQT) ----
        # Ensure output directory exists
        os.makedirs(os.path.dirname(os.path.abspath(output_path)), exist_ok=True)

        # Resolve obabel from the same venv bin/ as the Python interpreter
        python_dir = os.path.dirname(sys.executable)
        obabel_bin = os.path.join(python_dir, "obabel")
        if not os.path.exists(obabel_bin):
            # Fallback to system obabel
            obabel_bin = "obabel"

        obabel_cmd = [
            obabel_bin,
            "-isdf", sdf_path,
            "-opdbqt",
            "-O", output_path,
            "-p", "7.4", # protonate at physiological pH
        ]

        proc = subprocess.run(
            obabel_cmd,
            capture_output=True,
            text=True,
            timeout=60
        )

        # Clean up temp SDF
        if os.path.exists(sdf_path):
            os.remove(sdf_path)

        if proc.returncode != 0:
            raise ValueError(f"OpenBabel conversion failed: {proc.stderr.strip()}")

        if not os.path.exists(output_path) or os.path.getsize(output_path) == 0:
            raise ValueError("OpenBabel produced an empty or missing .pdbqt file")

        # Success
        print(json.dumps({
            "status": "success",
            "output_file": output_path,
            "smiles": smiles
        }))

    except Exception as e:
        # Clean up on error
        if 'sdf_path' in locals() and os.path.exists(sdf_path):
            os.remove(sdf_path)

        print(json.dumps({
            "status": "error",
            "message": str(e)
        }))
        sys.exit(1)


if __name__ == "__main__":
    main()
