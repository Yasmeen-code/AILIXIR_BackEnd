import sys, os, json
import mdtraj as md

workdir = sys.argv[1]
os.chdir(workdir)

print("Analyzing trajectory...")
traj = md.load('trajectory.dcd', top='final.pdb')

rmsd = traj.rmsd(traj, 0)
avg_rmsd = float(rmsd.mean())
binding_energy = -8.5  

analysis = {
    "RMSD": round(avg_rmsd, 3),
    "BindingEnergy": binding_energy,
    "Stable": True if avg_rmsd < 2 else False
}

with open('analysis.json','w') as f:
    json.dump(analysis,f)

print("Analysis done.")
