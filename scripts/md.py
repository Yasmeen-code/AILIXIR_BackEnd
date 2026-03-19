import sys, os
from openmm.app import PDBFile, Modeller, Simulation, DCDReporter, StateDataReporter
from openmm import Platform, LangevinIntegrator
from openmm.unit import nanometer, molar, kelvin, picosecond
from openff.toolkit.topology import Molecule
from openmmforcefields.generators import SystemGenerator

# =========================
# Inputs
# =========================
workdir, protein_file, ligand_file = sys.argv[1:4]
os.chdir(workdir)

print("📥 Loading protein...")
protein = PDBFile(protein_file)

print("🧬 Loading ligand...")
ligand = Molecule.from_file(ligand_file)

print("⚙️ Preparing force field...")
system_generator = SystemGenerator(
    forcefields=["amber14/protein.ff14SB.xml", "amber14/tip3p.xml"],
    small_molecule_forcefield="openff-2.0.0",
    molecules=[ligand]
)

modeller = Modeller(protein.topology, protein.positions)
print("🌊 Adding solvent...")
modeller.addSolvent(system_generator.forcefield, padding=1.0*nanometer, ionicStrength=0.15*molar)

print("➕ Adding ligand...")
lig_top = ligand.to_topology().to_openmm()
modeller.add(lig_top, ligand.conformers[0])

system = system_generator.create_system(modeller.topology)

try:
    print("🚀 Using GPU (CUDA)")
    platform = Platform.getPlatformByName("CUDA")
    properties = {"CudaPrecision": "mixed"}
except:
    print("⚠️ Using CPU")
    platform = Platform.getPlatformByName("CPU")
    properties = {}

integrator = LangevinIntegrator(300*kelvin, 1/picosecond, 0.002*picosecond)
simulation = Simulation(modeller.topology, system, integrator, platform, properties)
simulation.context.setPositions(modeller.positions)

print("🧹 Minimizing...")
simulation.minimizeEnergy()

print("🌡️ Equilibrating...")
simulation.context.setVelocitiesToTemperature(300*kelvin)
simulation.step(5000)

print("🏃 Running MD...")
simulation.reporters.append(DCDReporter("trajectory.dcd", 1000))
simulation.reporters.append(StateDataReporter("md.log", 1000, step=True, temperature=True, potentialEnergy=True, progress=True, speed=True))
simulation.reporters.append(StateDataReporter("md.csv", 1000, step=True, temperature=True, potentialEnergy=True, kineticEnergy=True, totalEnergy=True))

simulation.step(50000)

state = simulation.context.getState(getPositions=True)
with open("final_structure.pdb", "w") as f:
    PDBFile.writeFile(simulation.topology, state.getPositions(), f)

print("✅ MD Finished!")
