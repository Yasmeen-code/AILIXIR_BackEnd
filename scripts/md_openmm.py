import sys, os
from openmm.app import PDBFile, DCDReporter, StateDataReporter, ForceField, Simulation
from openmm import LangevinIntegrator, PME, HBonds
from openmm.unit import nanometer, kelvin, picosecond

workdir = sys.argv[1]
os.chdir(workdir)

print("Loading prepared complex...")
pdb = PDBFile('complex_prepared.pdb')
forcefield = ForceField('amber14-all.xml', 'amber14/tip3pfb.xml', 'gaff.xml')

system = forcefield.createSystem(pdb.topology, nonbondedMethod=PME,
                                nonbondedCutoff=1*nanometer, constraints=HBonds)
integrator = LangevinIntegrator(300*kelvin, 1/picosecond, 0.002*picosecond)
simulation = Simulation(pdb.topology, system, integrator)
simulation.context.setPositions(pdb.positions)

# reporters
simulation.reporters.append(DCDReporter('trajectory.dcd', 1000))
simulation.reporters.append(StateDataReporter('log.txt', 1000, step=True,
                                            potentialEnergy=True, temperature=True))

print("Running MD simulation...")
simulation.step(5000)

positions = simulation.context.getState(getPositions=True).getPositions()
PDBFile.writeFile(simulation.topology, positions, open('final.pdb','w'))
print("MD simulation finished.")
