#!/usr/bin/env python3
"""
MD Simulation Script for Protein-Ligand Systems
Author: AILIXIR Team
Version: 2.0
"""

import sys
import os
import json
import logging
import argparse
import warnings
from datetime import datetime
from pathlib import Path

# Suppress warnings
warnings.filterwarnings('ignore')

# Import OpenMM
try:
    import openmm
    import openmm.app as app
    import openmm.unit as unit
    from openmm.app import PDBFile, Modeller, ForceField, Simulation
    from openmm import LangevinMiddleIntegrator, MonteCarloBarostat
    from openmm import Platform
except ImportError as e:
    print(f"Error: OpenMM not installed. {e}")
    sys.exit(1)

# Import other required packages
try:
    import numpy as np
    import pandas as pd
except ImportError as e:
    print(f"Error: Required package not installed. {e}")
    sys.exit(1)

# Optional imports
try:
    import mdtraj as md
    MDTRAJ_AVAILABLE = True
except ImportError:
    MDTRAJ_AVAILABLE = False

try:
    import matplotlib.pyplot as plt
    MATPLOTLIB_AVAILABLE = True
except ImportError:
    MATPLOTLIB_AVAILABLE = False


class MDSimulation:
    """Main MD Simulation class for protein-ligand systems"""

    def __init__(self, base_dir=None, params=None):
        """
        Initialize MD Simulation

        Parameters:
        -----------
        base_dir : str
            Base working directory
        params : dict
            Simulation parameters
        """
        self.base_dir = base_dir or os.getcwd()
        self.params = params or self._default_params()

        # Setup directories
        self.inputs_dir = os.path.join(self.base_dir, 'inputs')
        self.outputs_dir = os.path.join(self.base_dir, 'outputs')
        self.temp_dir = os.path.join(self.base_dir, 'temp')
        self.logs_dir = os.path.join(self.outputs_dir, 'logs')
        self.traj_dir = os.path.join(self.outputs_dir, 'trajectories')
        self.structures_dir = os.path.join(self.outputs_dir, 'structures')
        self.analysis_dir = os.path.join(self.outputs_dir, 'analysis')
        self.plots_dir = os.path.join(self.outputs_dir, 'plots')
        self.checkpoints_dir = os.path.join(self.outputs_dir, 'checkpoints')
        self.reports_dir = os.path.join(self.outputs_dir, 'reports')

        # Create directories
        self._create_directories()

        # Setup logging
        self._setup_logging()

        # Initialize variables
        self.modeller = None
        self.forcefield = None
        self.system = None
        self.simulation = None
        self.has_ligand = False

        # Print directories
        self._print_directories()

    def _default_params(self):
        """Default simulation parameters"""
        return {
            # Basic parameters
            'temperature': 300,  # K
            'pressure': 1.01325,  # bar
            'timestep': 2,  # fs
            'equilibration_steps': 50000,
            'production_steps': 500000,
            'production_ns': 10,  # ns for reporting

            # Output parameters
            'save_interval': 1000,  # steps
            'report_interval': 1000,  # steps

            # Force field parameters
            'nonbonded_cutoff': 1.0,  # nm
            'ionic_strength': 0.15,  # M
            'padding': 1.0,  # nm
            'forcefield': 'amber14-all.xml',
            'water_model': 'tip3p',

            # Simulation options
            'constraints': app.HBonds,
            'integrator': 'Langevin',
            'friction_coeff': 1.0,  # ps^-1
            'barostat_frequency': 25,  # steps
            'rigid_water': True,
            'remove_com_motion': True,
            'hydrogen_mass': 3.0,  # amu (HMR)
        }

    def _create_directories(self):
        """Create required directories"""
        directories = [
            self.inputs_dir,
            self.outputs_dir,
            self.temp_dir,
            self.logs_dir,
            self.traj_dir,
            self.structures_dir,
            self.analysis_dir,
            self.plots_dir,
            self.checkpoints_dir,
            self.reports_dir
        ]

        for directory in directories:
            os.makedirs(directory, exist_ok=True)

    def _print_directories(self):
        """Print directory paths"""
        print(f"📁 Inputs directory: {self.inputs_dir}")
        print(f"📁 Outputs directory: {self.outputs_dir}")
        print(f"📁 Temp directory: {self.temp_dir}")
        print(f"📁 Logs directory: {self.logs_dir}")
        print(f"📁 Trajectories directory: {self.traj_dir}")
        print(f"📁 Structures directory: {self.structures_dir}")
        print(f"📁 Analysis directory: {self.analysis_dir}")
        print(f"📁 Plots directory: {self.plots_dir}")
        print(f"📁 Checkpoints directory: {self.checkpoints_dir}")
        print(f"📁 Reports directory: {self.reports_dir}")

    def _setup_logging(self):
        """Setup logging configuration"""
        self.logger = logging.getLogger('MDSimulation')
        self.logger.setLevel(logging.INFO)

        # File handler
        log_file = os.path.join(self.logs_dir, f"md_simulation_{datetime.now().strftime('%Y%m%d_%H%M%S')}.log")
        fh = logging.FileHandler(log_file)
        fh.setLevel(logging.INFO)

        # Console handler
        ch = logging.StreamHandler()
        ch.setLevel(logging.INFO)

        # Formatter
        formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
        fh.setFormatter(formatter)
        ch.setFormatter(formatter)

        self.logger.addHandler(fh)
        self.logger.addHandler(ch)

        self.logger.info("=" * 80)
        self.logger.info("MD SIMULATION INITIALIZED")
        self.logger.info("=" * 80)
        self.logger.info(f"Base directory: {self.base_dir}")

        # Log parameters (convert HBonds to string for JSON)
        params_copy = {}
        for key, value in self.params.items():
            if hasattr(value, '__class__') and value.__class__.__name__ == 'HBonds':
                params_copy[key] = str(value)
            else:
                params_copy[key] = value

        self.logger.info(f"Simulation parameters: {json.dumps(params_copy, indent=2, default=str)}")

    def check_prerequisites(self):
        """Check if all prerequisites are met"""
        self.logger.info("🔍 Checking prerequisites...")

        all_passed = True

        # Check OpenMM
        self.logger.info(f"OpenMM version: {openmm.__version__}")

        # Check required packages
        try:
            import numpy
            self.logger.info("  ✓ numpy available")
        except ImportError:
            self.logger.error("  ✗ numpy not available")
            all_passed = False

        try:
            import pandas
            self.logger.info("  ✓ pandas available")
        except ImportError:
            self.logger.error("  ✗ pandas not available")
            all_passed = False

        # Check optional packages
        if MDTRAJ_AVAILABLE:
            self.logger.info("  ✓ mdtraj available (optional)")
        else:
            self.logger.warning("  ⚠ mdtraj not available (optional)")

        if MATPLOTLIB_AVAILABLE:
            self.logger.info("  ✓ matplotlib available (optional)")
        else:
            self.logger.warning("  ⚠ matplotlib not available (optional)")

        try:
            import scipy
            self.logger.info("  ✓ scipy available (optional)")
        except ImportError:
            self.logger.warning("  ⚠ scipy not available (optional)")

        # Check input files
        protein_fixed = os.path.join(self.inputs_dir, 'protein_fixed.pdb')
        protein_original = os.path.join(self.inputs_dir, 'protein.pdb')

        if os.path.exists(protein_fixed) or os.path.exists(protein_original):
            self.logger.info("  ✓ protein.pdb found")
        else:
            self.logger.error("  ✗ protein.pdb not found")
            all_passed = False

        # Check for ligand
        ligand_mol2 = os.path.join(self.inputs_dir, 'ligand_gaff.mol2')
        ligand_pdb = os.path.join(self.inputs_dir, 'ligand.pdb')

        if os.path.exists(ligand_mol2) or os.path.exists(ligand_pdb):
            self.logger.info("  ✓ ligand.pdb found (optional)")
        else:
            self.logger.info("  ℹ No ligand found - will run protein-only simulation")

        # Summary
        if all_passed:
            self.logger.info("✅ OpenMM: OK")
            self.logger.info("✅ Python Packages: OK")
            self.logger.info("✅ Input Files: OK")
            self.logger.info("✅ Output Directories: OK")
            self.logger.info("✅ All prerequisites passed!")
            return True
        else:
            self.logger.error("❌ Prerequisites check failed")
            return False

    def load_structures(self):
        """Load protein and ligand structures"""
        self.logger.info("📦 Loading molecular structures...")

        # Determine protein file
        protein_fixed = os.path.join(self.inputs_dir, 'protein_fixed.pdb')
        protein_original = os.path.join(self.inputs_dir, 'protein.pdb')

        if os.path.exists(protein_fixed):
            protein_path = protein_fixed
            self.logger.info("  Using fixed protein")
        elif os.path.exists(protein_original):
            protein_path = protein_original
            self.logger.info("  Using original protein")
        else:
            raise FileNotFoundError(f"Protein file not found in {self.inputs_dir}")

        # Load protein
        pdb = PDBFile(protein_path)
        self.modeller = Modeller(pdb.topology, pdb.positions)

        self.logger.info(f"  ✓ Protein loaded: {protein_path}")
        self.logger.info(f"    - Atoms: {len(list(self.modeller.topology.atoms()))}")
        self.logger.info(f"    - Residues: {len(list(self.modeller.topology.residues()))}")
        self.logger.info(f"    - Chains: {len(list(self.modeller.topology.chains()))}")

        # Load ligand
        ligand_mol2 = os.path.join(self.inputs_dir, 'ligand_gaff.mol2')
        ligand_pdb = os.path.join(self.inputs_dir, 'ligand.pdb')

        if os.path.exists(ligand_mol2):
            self.logger.info("  Loading GAFF-prepared ligand...")
            self._load_ligand_mol2(ligand_mol2)
        elif os.path.exists(ligand_pdb):
            self.logger.info("  Loading PDB ligand...")
            self._load_ligand_pdb(ligand_pdb)
        else:
            self.logger.info("  ℹ No ligand found - protein-only simulation")
            self.has_ligand = False

        return True

    def _load_ligand_mol2(self, ligand_path):
        """Load ligand from MOL2 file"""
        try:
            if not MDTRAJ_AVAILABLE:
                raise ImportError("MDTraj required for MOL2 loading")

            # Load with MDTraj
            traj = md.load(ligand_path)

            # Convert to OpenMM topology
            omm_topology = app.Topology()
            omm_positions = []

            chain = omm_topology.addChain()
            residue = omm_topology.addResidue('LIG', chain)

            for atom in traj.topology.atoms:
                omm_topology.addAtom(str(atom.name), atom.element.symbol, residue)
                pos = traj.xyz[0][atom.index]  # nm
                omm_positions.append(unit.Quantity(pos, unit.nanometer))

            self.modeller.add(omm_topology, omm_positions)

            self.logger.info(f"  ✓ Ligand loaded from MOL2: {ligand_path}")
            self.logger.info(f"    - Atoms: {len(traj.topology.atoms)}")
            self.has_ligand = True

        except Exception as e:
            self.logger.error(f"  ✗ Failed to load MOL2 ligand: {e}")
            # Fallback to PDB if available
            ligand_pdb = os.path.join(self.inputs_dir, 'ligand.pdb')
            if os.path.exists(ligand_pdb):
                self.logger.info("  Falling back to PDB ligand...")
                self._load_ligand_pdb(ligand_pdb)
            else:
                self.has_ligand = False

    def _load_ligand_pdb(self, ligand_path):
        """Load ligand from PDB file"""
        try:
            ligand_pdb = PDBFile(ligand_path)
            self.modeller.add(ligand_pdb.topology, ligand_pdb.positions)

            self.logger.info(f"  ✓ Ligand loaded from PDB: {ligand_path}")
            self.logger.info(f"    - Atoms: {ligand_pdb.topology.getNumAtoms()}")
            self.has_ligand = True

        except Exception as e:
            self.logger.error(f"  ✗ Failed to load PDB ligand: {e}")
            self.has_ligand = False

    def setup_forcefield(self):
        """Setup force field"""
        self.logger.info("⚙️ Setting up force field...")

        # Load force field files
        forcefield_files = [self.params['forcefield']]

        # Add water model
        if self.params['water_model'] == 'tip3p':
            forcefield_files.append('amber14/tip3p.xml')
        else:
            forcefield_files.append(f'amber14/{self.params["water_model"]}.xml')

        self.forcefield = ForceField(*forcefield_files)
        self.logger.info(f"  ✓ Force field: {', '.join(forcefield_files)}")

        return True

    def add_solvent_and_ions(self):
        """Add solvent molecules and ions to the system"""
        self.logger.info("💧 Adding solvent and ions...")

        try:
            padding = self.params['padding'] * unit.nanometers

            self.modeller.addSolvent(
                self.forcefield,
                padding=padding,
                positiveIon='Na+',
                negativeIon='Cl-',
                ionicStrength=self.params['ionic_strength'] * unit.molar
            )

            self.logger.info(f"  ✓ Solvent and ions added together")
            self.logger.info(f"    - Padding: {self.params['padding']} nm")
            self.logger.info(f"    - Ionic strength: {self.params['ionic_strength']} M")
            self.logger.info(f"    - Final atom count: {self.modeller.topology.getNumAtoms()}")

        except Exception as e:
            self.logger.error(f"  ✗ Failed to add solvent with ions: {e}")

            # Try adding solvent only first
            try:
                self.modeller.addSolvent(self.forcefield, padding=padding)
                self.logger.info(f"  ✓ Solvent added (padding: {self.params['padding']} nm)")

                # Then add ions
                ionic_strength = self.params['ionic_strength'] * unit.molar
                self.modeller.addIons(self.forcefield, ionicStrength=ionic_strength)
                self.logger.info(f"  ✓ Ions added (ionic strength: {self.params['ionic_strength']} M)")
                self.logger.info(f"    - Final atom count: {self.modeller.topology.getNumAtoms()}")

            except Exception as e2:
                self.logger.error(f"  ✗ Failed to add solvent/ions: {e2}")
                raise

        return True

    def create_system(self):
        """Create the OpenMM system"""
        self.logger.info("🔧 Creating system...")

        # Create system
        self.system = self.forcefield.createSystem(
            self.modeller.topology,
            nonbondedMethod=app.PME,
            nonbondedCutoff=self.params['nonbonded_cutoff'] * unit.nanometers,
            constraints=self.params['constraints'],
            rigidWater=self.params['rigid_water'],
            hydrogenMass=self.params['hydrogen_mass'] * unit.amu
        )

        self.logger.info(f"  ✓ System created")
        self.logger.info(f"    - Nonbonded cutoff: {self.params['nonbonded_cutoff']} nm")
        self.logger.info(f"    - Constraints: {self.params['constraints']}")
        self.logger.info(f"    - Hydrogen mass: {self.params['hydrogen_mass']} amu")

        return True

    def setup_integrator(self):
        """Setup integrator"""
        self.logger.info("⚙️ Setting up integrator...")

        if self.params['integrator'] == 'Langevin':
            self.integrator = LangevinMiddleIntegrator(
                self.params['temperature'] * unit.kelvin,
                self.params['friction_coeff'] / unit.picosecond,
                self.params['timestep'] * unit.femtoseconds
            )
            self.logger.info(f"  ✓ Langevin integrator setup")
            self.logger.info(f"    - Temperature: {self.params['temperature']} K")
            self.logger.info(f"    - Friction: {self.params['friction_coeff']} ps^-1")
            self.logger.info(f"    - Timestep: {self.params['timestep']} fs")
        else:
            raise ValueError(f"Unknown integrator: {self.params['integrator']}")

        return True

    def add_barostat(self):
        """Add barostat for NPT ensemble"""
        self.logger.info("⚙️ Adding barostat...")

        # Add Monte Carlo barostat
        self.system.addForce(MonteCarloBarostat(
            self.params['pressure'] * unit.bar,
            self.params['temperature'] * unit.kelvin,
            self.params['barostat_frequency']
        ))

        self.logger.info(f"  ✓ Barostat added")
        self.logger.info(f"    - Pressure: {self.params['pressure']} bar")
        self.logger.info(f"    - Frequency: {self.params['barostat_frequency']} steps")

        return True

    def create_simulation(self):
        """Create the simulation object"""
        self.logger.info("🎯 Creating simulation...")

        # Get platform
        platform = Platform.getPlatformByName('CUDA')
        properties = {'CudaPrecision': 'mixed'}

        try:
            self.simulation = Simulation(
                self.modeller.topology,
                self.system,
                self.integrator,
                platform,
                properties
            )
            self.logger.info(f"  ✓ Simulation created on CUDA platform")
        except:
            # Fallback to CPU
            platform = Platform.getPlatformByName('CPU')
            self.simulation = Simulation(
                self.modeller.topology,
                self.system,
                self.integrator,
                platform
            )
            self.logger.info(f"  ✓ Simulation created on CPU platform")

        # Set initial positions
        self.simulation.context.setPositions(self.modeller.positions)

        # Save initial structure
        initial_pdb = os.path.join(self.structures_dir, 'system_initial.pdb')
        with open(initial_pdb, 'w') as f:
            PDBFile.writeFile(
                self.simulation.topology,
                self.simulation.context.getState(getPositions=True).getPositions(),
                f
            )
        self.logger.info(f"  ✓ Initial structure saved: {initial_pdb}")

        return True

    def minimize_energy(self, tolerance=10.0, max_iterations=0):
        """Energy minimization"""
        self.logger.info("⚡ Running energy minimization...")

        self.logger.info(f"  Initial energy: {self._get_potential_energy():.2f} kJ/mol")

        self.simulation.minimizeEnergy(tolerance=tolerance, maxIterations=max_iterations)

        final_energy = self._get_potential_energy()
        self.logger.info(f"  Final energy: {final_energy:.2f} kJ/mol")
        self.logger.info(f"  ✓ Energy minimization complete")

        # Save minimized structure
        minimized_pdb = os.path.join(self.structures_dir, 'system_minimized.pdb')
        with open(minimized_pdb, 'w') as f:
            PDBFile.writeFile(
                self.simulation.topology,
                self.simulation.context.getState(getPositions=True).getPositions(),
                f
            )
        self.logger.info(f"  ✓ Minimized structure saved: {minimized_pdb}")

        return True

    def _get_potential_energy(self):
        """Get potential energy in kJ/mol"""
        state = self.simulation.context.getState(getEnergy=True)
        energy = state.getPotentialEnergy().value_in_unit(unit.kilojoules_per_mole)
        return energy

    def setup_reporters(self, phase='production'):
        """Setup reporters for trajectory and data output"""
        self.logger.info(f"📊 Setting up reporters for {phase}...")

        # DCD reporter for trajectory
        dcd_file = os.path.join(self.traj_dir, f'{phase}.dcd')
        dcd_reporter = app.DCDReporter(
            dcd_file,
            self.params['save_interval']
        )
        self.simulation.reporters.append(dcd_reporter)
        self.logger.info(f"  ✓ DCD reporter: {dcd_file}")

        # Data reporter for energies
        data_file = os.path.join(self.analysis_dir, f'{phase}_energy.csv')
        data_reporter = app.StateDataReporter(
            data_file,
            self.params['report_interval'],
            step=True,
            time=True,
            potentialEnergy=True,
            kineticEnergy=True,
            totalEnergy=True,
            temperature=True,
            volume=True,
            density=True,
            speed=True
        )
        self.simulation.reporters.append(data_reporter)
        self.logger.info(f"  ✓ Data reporter: {data_file}")

        # Checkpoint reporter
        checkpoint_file = os.path.join(self.checkpoints_dir, f'{phase}_checkpoint.chk')
        checkpoint_reporter = app.CheckpointReporter(
            checkpoint_file,
            self.params['report_interval'] * 10
        )
        self.simulation.reporters.append(checkpoint_reporter)
        self.logger.info(f"  ✓ Checkpoint reporter: {checkpoint_file}")

        return True

    def equilibrate(self):
        """Run equilibration"""
        self.logger.info("🔄 Starting equilibration...")

        # NVT equilibration
        self.logger.info("  Phase 1: NVT Equilibration")
        self.logger.info(f"    - Steps: {self.params['equilibration_steps'] // 2}")
        self.logger.info(f"    - Ensemble: NVT")

        # Initialize velocities
        self.simulation.context.setVelocitiesToTemperature(self.params['temperature'] * unit.kelvin)

        # Run NVT
        self.simulation.step(self.params['equilibration_steps'] // 2)

        # Save NVT final structure
        nvt_pdb = os.path.join(self.structures_dir, 'system_nvt.pdb')
        with open(nvt_pdb, 'w') as f:
            PDBFile.writeFile(
                self.simulation.topology,
                self.simulation.context.getState(getPositions=True).getPositions(),
                f
            )
        self.logger.info(f"    ✓ NVT structure saved: {nvt_pdb}")

        # Add barostat for NPT
        self.logger.info("  Phase 2: NPT Equilibration")
        self.logger.info(f"    - Steps: {self.params['equilibration_steps'] // 2}")
        self.logger.info(f"    - Ensemble: NPT")

        self.add_barostat()
        self.simulation.context.reinitialize(preserveState=True)

        # Run NPT
        self.simulation.step(self.params['equilibration_steps'] // 2)

        # Save NPT final structure
        npt_pdb = os.path.join(self.structures_dir, 'system_npt.pdb')
        with open(npt_pdb, 'w') as f:
            PDBFile.writeFile(
                self.simulation.topology,
                self.simulation.context.getState(getPositions=True).getPositions(),
                f
            )
        self.logger.info(f"    ✓ NPT structure saved: {npt_pdb}")

        self.logger.info("✅ Equilibration complete!")

        return True

    def run_production(self):
        """Run production simulation"""
        self.logger.info("🚀 Starting production run...")
        self.logger.info(f"  Steps: {self.params['production_steps']}")
        self.logger.info(f"  Duration: {self.params['production_ns']} ns")

        # Setup reporters
        self.setup_reporters('production')

        # Run production
        self.logger.info("  Running production simulation...")

        for i in range(0, self.params['production_steps'], 10000):
            self.simulation.step(10000)
            progress = (i + 10000) / self.params['production_steps'] * 100
            self.logger.info(f"    Progress: {progress:.1f}% ({i + 10000}/{self.params['production_steps']} steps)")

        # Save final structure
        final_pdb = os.path.join(self.structures_dir, 'system_final.pdb')
        with open(final_pdb, 'w') as f:
            PDBFile.writeFile(
                self.simulation.topology,
                self.simulation.context.getState(getPositions=True).getPositions(),
                f
            )
        self.logger.info(f"  ✓ Final structure saved: {final_pdb}")

        # Save state
        state_xml = os.path.join(self.checkpoints_dir, 'final_state.xml')
        with open(state_xml, 'w') as f:
            f.write(self.simulation.context.getState(getPositions=True, getVelocities=True).getXml())
        self.logger.info(f"  ✓ Final state saved: {state_xml}")

        self.logger.info("✅ Production run complete!")

        return True

    def analyze_results(self):
        """Analyze simulation results"""
        self.logger.info("📈 Analyzing results...")

        # Load trajectory
        dcd_file = os.path.join(self.traj_dir, 'production.dcd')
        pdb_file = os.path.join(self.structures_dir, 'system_npt.pdb')

        if not MDTRAJ_AVAILABLE:
            self.logger.warning("⚠ MDTraj not available, skipping analysis")
            return False

        if not os.path.exists(dcd_file):
            self.logger.warning(f"⚠ Trajectory file not found: {dcd_file}")
            return False

        try:
            # Load trajectory
            traj = md.load(dcd_file, top=pdb_file)
            self.logger.info(f"  ✓ Loaded trajectory: {len(traj)} frames")

            # Calculate RMSD
            self.logger.info("  Calculating RMSD...")

            # Align to first frame
            traj.superpose(traj, 0)

            # Calculate RMSD for protein backbone
            backbone = traj.topology.select('backbone')
            rmsd = md.rmsd(traj, traj, 0, atom_indices=backbone)

            # Save RMSD data
            rmsd_df = pd.DataFrame({
                'time_ns': traj.time / 1000.0,
                'rmsd_nm': rmsd
            })
            rmsd_file = os.path.join(self.analysis_dir, 'rmsd.csv')
            rmsd_df.to_csv(rmsd_file, index=False)
            self.logger.info(f"  ✓ RMSD saved: {rmsd_file}")

            # Plot RMSD
            if MATPLOTLIB_AVAILABLE:
                plt.figure(figsize=(10, 6))
                plt.plot(rmsd_df['time_ns'], rmsd_df['rmsd_nm'], 'b-', linewidth=1)
                plt.xlabel('Time (ns)')
                plt.ylabel('RMSD (nm)')
                plt.title('Protein Backbone RMSD')
                plt.grid(True, alpha=0.3)

                rmsd_plot = os.path.join(self.plots_dir, 'rmsd_plot.png')
                plt.savefig(rmsd_plot, dpi=150, bbox_inches='tight')
                plt.close()
                self.logger.info(f"  ✓ RMSD plot saved: {rmsd_plot}")

            # Calculate energy statistics
            energy_file = os.path.join(self.analysis_dir, 'production_energy.csv')
            if os.path.exists(energy_file):
                energy_df = pd.read_csv(energy_file)

                self.logger.info("  Energy statistics:")
                self.logger.info(f"    - Avg potential energy: {energy_df['Potential Energy (kJ/mole)'].mean():.2f} kJ/mol")
                self.logger.info(f"    - Std potential energy: {energy_df['Potential Energy (kJ/mole)'].std():.2f} kJ/mol")
                self.logger.info(f"    - Avg temperature: {energy_df['Temperature (K)'].mean():.2f} K")
                self.logger.info(f"    - Avg density: {energy_df['Density (g/mL)'].mean():.4f} g/mL")

            self.logger.info("✅ Analysis complete!")

        except Exception as e:
            self.logger.error(f"  ✗ Analysis failed: {e}")
            return False

        return True

    def generate_report(self):
        """Generate final report"""
        self.logger.info("📋 Generating report...")

        report_file = os.path.join(self.reports_dir, f"md_report_{datetime.now().strftime('%Y%m%d_%H%M%S')}.txt")

        with open(report_file, 'w', encoding='utf-8') as f:
            f.write("=" * 80 + "\n")
            f.write("MD SIMULATION REPORT\n")
            f.write("=" * 80 + "\n\n")

            f.write(f"Date: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
            f.write(f"Base Directory: {self.base_dir}\n\n")

            f.write("SIMULATION PARAMETERS\n")
            f.write("-" * 40 + "\n")
            for key, value in self.params.items():
                f.write(f"{key}: {value}\n")

            f.write("\nSYSTEM INFORMATION\n")
            f.write("-" * 40 + "\n")
            f.write(f"Protein: {'Yes' if os.path.exists(os.path.join(self.inputs_dir, 'protein.pdb')) else 'No'}\n")
            f.write(f"Ligand: {'Yes' if self.has_ligand else 'No'}\n")
            f.write(f"Total atoms: {self.modeller.topology.getNumAtoms() if self.modeller else 'N/A'}\n")

            f.write("\nSIMULATION STATUS\n")
            f.write("-" * 40 + "\n")
            f.write("✅ Simulation completed successfully\n")

            f.write("\nOUTPUT FILES\n")
            f.write("-" * 40 + "\n")
            f.write(f"Trajectory: {os.path.join(self.traj_dir, 'production.dcd')}\n")
            f.write(f"Final structure: {os.path.join(self.structures_dir, 'system_final.pdb')}\n")
            f.write(f"Energy data: {os.path.join(self.analysis_dir, 'production_energy.csv')}\n")
            f.write(f"RMSD data: {os.path.join(self.analysis_dir, 'rmsd.csv')}\n")

        self.logger.info(f"  ✓ Report saved: {report_file}")

        return True

    def run(self):
        """Run the complete MD simulation workflow"""
        try:
            # Check prerequisites
            if not self.check_prerequisites():
                self.logger.error("❌ Prerequisites check failed. Exiting.")
                return False

            # Load structures
            self.load_structures()

            # Setup force field
            self.setup_forcefield()

            # Add solvent and ions
            self.add_solvent_and_ions()

            # Create system
            self.create_system()

            # Setup integrator
            self.setup_integrator()

            # Create simulation
            self.create_simulation()

            # Energy minimization
            self.minimize_energy()

            # Equilibration
            self.equilibrate()

            # Production run
            self.run_production()

            # Analysis
            self.analyze_results()

            # Generate report
            self.generate_report()

            self.logger.info("=" * 80)
            self.logger.info("🎉 MD SIMULATION COMPLETED SUCCESSFULLY!")
            self.logger.info("=" * 80)

            return True

        except Exception as e:
            self.logger.error(f"❌ MD simulation failed: {e}")
            self.logger.error("Detailed error:", exc_info=True)
            return False


def main():
    """Main entry point"""
    parser = argparse.ArgumentParser(
        description='MD Simulation for Protein-Ligand Systems',
        formatter_class=argparse.RawDescriptionHelpFormatter
    )

    parser.add_argument('base_dir', nargs='?', default=os.getcwd(),
                        help='Base working directory (default: current directory)')
    parser.add_argument('--config', '-c', type=str,
                        help='JSON configuration file with simulation parameters')
    parser.add_argument('--steps', type=int,
                        help='Override production steps')
    parser.add_argument('--ns', type=float,
                        help='Override production duration in nanoseconds')

    args = parser.parse_args()

    # Load parameters from config file if provided
    params = None
    if args.config and os.path.exists(args.config):
        with open(args.config, 'r') as f:
            params = json.load(f)

    # Override parameters from command line
    if params is None:
        params = {}

    if args.steps:
        params['production_steps'] = args.steps
        params['production_ns'] = args.steps * 2 / 500000  # 2 fs timestep

    if args.ns:
        params['production_ns'] = args.ns
        params['production_steps'] = int(args.ns * 500000)  # 2 fs timestep

    # Create and run simulation
    md_sim = MDSimulation(args.base_dir, params)
    success = md_sim.run()

    sys.exit(0 if success else 1)


if __name__ == "__main__":
    main()
