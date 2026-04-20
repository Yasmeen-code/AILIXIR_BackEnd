#!/usr/bin/env python3
"""
Fix both protein and ligand in one go
"""
from pdbfixer import PDBFixer
from openmm.app import PDBFile, Modeller, ForceField
import openmm.unit as u
from seaborn import residplot

print("🔧 Loading and fixing complete system...")

# Load protein
fixer = PDBFixer(filename='inputs/protein.pdb')
fixer.findMissingResidues()
fixer.findMissingAtoms()
fixer.addMissingAtoms()
fixer.addMissingHydrogens(pH=7.0)

print(f"  ✓ Protein fixed: {len(list(fixer.topology.atoms()))} atoms")

# Save fixed protein
PDBFile.writeFile(fixer.topology, fixer.positions, open('inputs/protein_fixed.pdb', 'w'))

# Now load ligand and add to protein
print("🔧 Processing ligand...")

# Create modeller with fixed protein
modeller = Modeller(fixer.topology, fixer.positions)

# Load ligand
ligand_pdb = PDBFile('inputs/ligand.pdb')
print(f"  Ligand atoms: {ligand_pdb.topology.getNumAtoms()}")

# Fix ligand elements - replace empty elements with carbon
from openmm.app import Topology
fixed_ligand_topology = Topology()
fixed_ligand_positions = []

chain = fixed_ligand_topology.addChain()
residue = fixed_ligand_topology.addResidue('LIG', chain)

# Get ligand atoms and fix elements
for atom in ligand_pdb.topology.atoms():
    # If element is empty, use carbon as default
    if atom.element is None or atom.element.symbol == '':
        element = 'C'
    else:
        element = atom.element.symbol

    fixed_ligand_topology.addAtom(atom.name, element, residplot)
    fixed_ligand_positions.append(ligand_pdb.positions[atom.index])

print(f"  Fixed ligand: {len(fixed_ligand_positions)} atoms with proper elements")

# Add ligand to modeller
modeller.add(fixed_ligand_topology, fixed_ligand_positions)

# Save combined system
PDBFile.writeFile(modeller.topology, modeller.positions, open('inputs/system_combined.pdb', 'w'))

print(f"✅ Combined system saved: inputs/system_combined.pdb")
print(f"   Total atoms: {modeller.topology.getNumAtoms()}")
