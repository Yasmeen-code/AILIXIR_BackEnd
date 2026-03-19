import sys, os, json
import MDAnalysis as mda
from MDAnalysis.analysis import rms, pca
import numpy as np

workdir = sys.argv[1]
os.chdir(workdir)

traj_file = "trajectory.dcd"
top_file = "protein.pdb"

print("📊 Loading trajectory...")
u = mda.Universe(top_file, traj_file)

results = {}

# =========================
# RMSD
# =========================
R = rms.RMSD(u, select="protein and name CA")
R.run()
results["rmsd"] = R.rmsd[:,2].tolist()

# =========================
# Radius of Gyration
# =========================
rg = []
for ts in u.trajectory:
    rg.append(u.atoms.radius_of_gyration())
results["rg"] = rg

# =========================
# RMSF
# =========================
RMSF = rms.RMSF(u, select="protein and name CA")
RMSF.run()
results["rmsf"] = RMSF.rmsf.tolist()

# =========================
# PCA
# =========================
pca_analysis = pca.PCA(u, select="protein and name CA")
pca_analysis.run()
results["pca"] = {
    "pc1": pca_analysis.results.pcs[:,0].tolist(),
    "pc2": pca_analysis.results.pcs[:,1].tolist(),
    "eigenvalues": pca_analysis.results.eigenvalues.tolist()
}

# =========================
# Correlation Matrix
# =========================
ca_atoms = u.select_atoms("protein and name CA")
coords = []
for ts in u.trajectory:
    coords.append(ca_atoms.positions.copy())
coords = np.array(coords)

mean_coords = coords.mean(axis=0)
displacements = coords - mean_coords

n_atoms = displacements.shape[1]
corr_matrix = np.zeros((n_atoms, n_atoms))
for i in range(n_atoms):
    for j in range(n_atoms):
        xi = displacements[:,i,:].flatten()
        xj = displacements[:,j,:].flatten()
        corr_matrix[i,j] = np.corrcoef(xi, xj)[0,1]

results["correlation_matrix"] = corr_matrix.tolist()

# =========================
# Save JSON
# =========================
with open("analysis.json", "w") as f:
    json.dump(results, f)

print("✅ Analysis Done!")
