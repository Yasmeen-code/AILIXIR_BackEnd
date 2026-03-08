import sys
import json
from vina import Vina

def main():
    if len(sys.argv) < 7:
        print("Usage: python3 vina_docking.py <protein_file> <ligand_file> <center_x> <center_y> <center_z> <box_size_x> <box_size_y> <box_size_z> [exhaustiveness] [n_poses]")
        sys.exit(1)

    protein_file = sys.argv[1]
    ligand_file = sys.argv[2]
    center_x = float(sys.argv[3])
    center_y = float(sys.argv[4])
    center_z = float(sys.argv[5])
    box_x = float(sys.argv[6])
    box_y = float(sys.argv[7])
    box_z = float(sys.argv[8])
    
    exhaustiveness = int(sys.argv[9]) if len(sys.argv) > 9 else 8
    n_poses = int(sys.argv[10]) if len(sys.argv) > 10 else 5

    try:
        v = Vina(sf_name='vina')
        v.set_receptor(protein_file)
        v.set_ligand_from_file(ligand_file)

        v.compute_vina_maps(center=[center_x, center_y, center_z], box_size=[box_x, box_y, box_z])
        
        # Run docking
        v.dock(exhaustiveness=exhaustiveness, n_poses=n_poses)

        # Output energies in JSON format so PHP can parse it easily
        energies = v.energies()
        
        output_file = ligand_file.replace('.pdbqt', '') + '_out.pdbqt'
        v.write_poses(output_file, n_poses=1)

        result = {
            "status": "success",
            "energies": [list(e) for e in energies],
            "output_file": output_file
        }
        print(json.dumps(result))

    except Exception as e:
        error_result = {
            "status": "error",
            "message": str(e)
        }
        print(json.dumps(error_result))
        sys.exit(1)

if __name__ == "__main__":
    main()
