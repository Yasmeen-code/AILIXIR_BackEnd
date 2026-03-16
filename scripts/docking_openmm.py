import sys, os
from openmm.app import PDBFile, Modeller, ForceField, Simulation
from openmm import LangevinIntegrator, PME, HBonds
from openmm.unit import nanometer, kelvin, picosecond

workdir = sys.argv[1]
protein_file = sys.argv[2]
ligand_file  = sys.argv[3]

os.chdir(workdir)

print("Loading protein and ligand...")
pdb = PDBFile(protein_file)
forcefield = ForceField('amber14-all.xml', 'amber14/tip3pfb.xml', 'gaff.xml')

modeller = Modeller(pdb.topology, pdb.positions)
modeller.addSolvent(forcefield, model='tip3p', padding=1.0*nanometer)
modeller.addIons(forcefield, 'Na+', 0)
modeller.addIons(forcefield, 'Cl-', 0)

system = forcefield.createSystem(modeller.topology, nonbondedMethod=PME,
                                nonbondedCutoff=1*nanometer, constraints=HBonds)

integrator = LangevinIntegrator(300*kelvin, 1/picosecond, 0.002*picosecond)
simulation = Simulation(modeller.topology, system, integrator)
simulation.context.setPositions(modeller.positions)

print("Energy minimization...")
simulation.minimizeEnergy()

PDBFile.writeFile(simulation.topology,
                simulation.context.getState(getPositions=True).getPositions(),
                open('complex_prepared.pdb', 'w'))

print("Docking preparation with water box, ions, and GAFF done.")
