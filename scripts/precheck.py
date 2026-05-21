#!/usr/bin/env python3
"""
Pre-check Module for Molecular Dynamics Simulation
Comprehensive system and input validation
Author: AILIXIR Team
Version: 2.0
"""

import sys
import os
import subprocess
import shutil
import json
import platform
import re
import warnings
import importlib
from pathlib import Path
from datetime import datetime
import argparse

# Fix UTF-8 encoding for Windows
if sys.platform == 'win32':
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8')

# Use importlib.metadata instead of deprecated pkg_resources
try:
    from importlib.metadata import version, distribution, distributions
    USE_IMPORTLIB = True
except ImportError:
    # Fallback for Python < 3.8
    import pkg_resources
    USE_IMPORTLIB = False

# Suppress warnings
warnings.filterwarnings('ignore')

class MDPrecheck:
    """Complete pre-check system for MD simulations"""

    def __init__(self, base_dir=None, verbose=True):
        """
        Initialize pre-check system

        Parameters:
        -----------
        base_dir : str
            Base working directory
        verbose : bool
            Print detailed output
        """
        self.base_dir = base_dir or os.getcwd()
        self.verbose = verbose
        self.checks = []
        self.warnings = []
        self.errors = []

        # Color codes for terminal output
        self.colors = {
            'red': '\033[91m',
            'green': '\033[92m',
            'yellow': '\033[93m',
            'blue': '\033[94m',
            'purple': '\033[95m',
            'cyan': '\033[96m',
            'white': '\033[97m',
            'reset': '\033[0m',
            'bold': '\033[1m'
        }

        # Disable colors if not supported (Windows)
        if sys.platform == 'win32' and not os.environ.get('ANSICON'):
            for key in self.colors:
                self.colors[key] = ''

        # System information
        self.system_info = {}

        # Required packages with minimum versions
        self.required_packages = {
            'numpy': '1.19.0',
            'pandas': '1.2.0',
            'openmm': '7.5.0'
        }

        self.optional_packages = {
            'mdtraj': '1.9.0',
            'matplotlib': '3.3.0',
            'scipy': '1.6.0',
            'sklearn': '0.24.0',
            'seaborn': '0.11.0',
            'numba': '0.53.0',
            'pymbar': '3.0.0',
            'networkx': '2.5.0'
        }

        # Required tools
        self.required_tools = {
            'antechamber': 'AmberTools',
            'tleap': 'AmberTools',
            'parmed': 'ParmEd',
            'gmx': 'GROMACS (optional)'
        }

    def get_package_version(self, package_name):
        """Get package version using importlib.metadata or pkg_resources"""
        try:
            if USE_IMPORTLIB:
                try:
                    return version(package_name)
                except:
                    return None
            else:
                try:
                    return pkg_resources.get_distribution(package_name).version
                except:
                    return None
        except:
            return None

    def print_header(self, text, char='='):
        """Print formatted header"""
        if self.verbose:
            print(f"\n{self.colors['bold']}{self.colors['cyan']}{char*80}{self.colors['reset']}")
            print(f"{self.colors['bold']}{self.colors['cyan']}{text.center(80)}{self.colors['reset']}")
            print(f"{self.colors['bold']}{self.colors['cyan']}{char*80}{self.colors['reset']}\n")

    def print_success(self, text):
        """Print success message"""
        if self.verbose:
            print(f"{self.colors['green']}✅ {text}{self.colors['reset']}")

    def print_error(self, text):
        """Print error message"""
        if self.verbose:
            print(f"{self.colors['red']}❌ {text}{self.colors['reset']}")

    def print_warning(self, text):
        """Print warning message"""
        if self.verbose:
            print(f"{self.colors['yellow']}⚠️  {text}{self.colors['reset']}")

    def print_info(self, text):
        """Print info message"""
        if self.verbose:
            print(f"{self.colors['blue']}ℹ️  {text}{self.colors['reset']}")

    def print_detail(self, text):
        """Print detailed info"""
        if self.verbose:
            print(f"  {text}")

    def collect_system_info(self):
        """Collect system information"""
        self.print_header("SYSTEM INFORMATION", '-')

        self.system_info = {
            'platform': platform.system(),
            'platform_release': platform.release(),
            'platform_version': platform.version(),
            'architecture': platform.machine(),
            'processor': platform.processor(),
            'python_version': sys.version,
            'python_executable': sys.executable,
            'hostname': platform.node(),
        }

        for key, value in self.system_info.items():
            self.print_detail(f"{key.replace('_', ' ').title()}: {value}")

        # Check memory
        try:
            import psutil
            memory = psutil.virtual_memory()
            self.system_info['total_memory_gb'] = memory.total / (1024**3)
            self.system_info['available_memory_gb'] = memory.available / (1024**3)
            self.print_detail(f"Total Memory: {self.system_info['total_memory_gb']:.1f} GB")
            self.print_detail(f"Available Memory: {self.system_info['available_memory_gb']:.1f} GB")

            # Check if enough memory (at least 4GB recommended)
            if self.system_info['available_memory_gb'] < 4:
                self.print_warning(f"Low memory available ({self.system_info['available_memory_gb']:.1f} GB). 4GB+ recommended for MD simulations.")
        except ImportError:
            self.print_warning("psutil not installed. Cannot check memory availability.")

        # Check CPU cores
        try:
            cpu_count = os.cpu_count()
            self.system_info['cpu_cores'] = cpu_count
            self.print_detail(f"CPU Cores: {cpu_count}")
            if cpu_count < 4:
                self.print_warning(f"Only {cpu_count} CPU cores available. 4+ cores recommended for MD simulations.")
        except:
            pass

        return True

    def check_operating_system(self):
        """Check operating system compatibility"""
        self.print_header("OPERATING SYSTEM CHECK", '-')

        system = platform.system()
        self.print_detail(f"Detected OS: {system}")

        if system == 'Windows':
            self.print_warning("Windows detected. WSL (Windows Subsystem for Linux) recommended for MD simulations.")
            # Check if running in WSL
            try:
                with open('/proc/version', 'r') as f:
                    if 'microsoft' in f.read().lower():
                        self.print_success("Running in WSL - Optimal for Windows")
                    else:
                        self.print_warning("Not running in WSL. Consider using WSL2 for better performance.")
            except:
                self.print_warning("Cannot determine if running in WSL")
        elif system == 'Linux':
            self.print_success("Linux detected - Optimal for MD simulations")
        elif system == 'Darwin':
            self.print_success("macOS detected - Compatible with MD simulations")
        else:
            self.print_warning(f"{system} detected. Compatibility not guaranteed.")

        return True

    def check_python_environment(self):
        """Check Python environment"""
        self.print_header("PYTHON ENVIRONMENT", '-')

        # Python version
        python_version = sys.version_info
        self.print_detail(f"Python version: {python_version.major}.{python_version.minor}.{python_version.micro}")

        if python_version.major == 3 and python_version.minor >= 8:
            self.print_success(f"Python {python_version.major}.{python_version.minor} is supported")
        else:
            self.print_error(f"Python 3.8+ required. Current: {python_version.major}.{python_version.minor}")
            self.errors.append("Python version too old")

        # Check pip
        try:
            result = subprocess.run([sys.executable, '-m', 'pip', '--version'],
                                  capture_output=True, text=True, check=False)
            if result.returncode == 0:
                pip_version = result.stdout.split()[1]
                self.print_detail(f"pip version: {pip_version}")
                self.print_success("pip is available")
            else:
                self.print_error("pip not available")
                self.errors.append("pip not found")
        except:
            self.print_error("pip check failed")
            self.errors.append("pip check failed")

        # Check conda if available
        try:
            result = subprocess.run(['conda', '--version'],
                                  capture_output=True, text=True, check=False)
            if result.returncode == 0:
                conda_version = result.stdout.strip()
                self.print_detail(f"Conda: {conda_version}")
                self.print_success("Conda is available")
                self.system_info['conda_version'] = conda_version
            else:
                self.print_info("Conda not found (optional)")
        except:
            self.print_info("Conda not found (optional)")

        return len(self.errors) == 0

    def check_required_packages(self):
        """Check required Python packages"""
        self.print_header("REQUIRED PACKAGES", '-')

        all_installed = True

        for package, min_version in self.required_packages.items():
            try:
                # Try to import package
                module = importlib.import_module(package)

                # Get version
                if hasattr(module, '__version__'):
                    pkg_version = module.__version__
                else:
                    pkg_version = self.get_package_version(package)

                # Check version
                if pkg_version and pkg_version != "unknown":
                    from packaging import version as pkg_version_parser
                    if pkg_version_parser.parse(pkg_version) >= pkg_version_parser.parse(min_version):
                        self.print_success(f"{package} {pkg_version} (>= {min_version})")
                    else:
                        self.print_warning(f"{package} {pkg_version} (< {min_version} recommended)")
                        self.warnings.append(f"{package} version {pkg_version} < {min_version}")
                else:
                    self.print_success(f"{package} installed (version unknown)")

            except ImportError:
                self.print_error(f"{package} not installed (required >= {min_version})")
                self.errors.append(f"{package} not installed")
                all_installed = False

        return all_installed

    def check_optional_packages(self):
        """Check optional Python packages"""
        self.print_header("OPTIONAL PACKAGES", '-')

        installed = []
        missing = []

        for package, min_version in self.optional_packages.items():
            try:
                module = importlib.import_module(package)

                # Get version
                if hasattr(module, '__version__'):
                    pkg_version = module.__version__
                else:
                    pkg_version = self.get_package_version(package)

                self.print_success(f"{package} {pkg_version if pkg_version else ''}")
                installed.append(package)

            except ImportError:
                self.print_info(f"{package} not installed (optional, enables advanced features)")
                missing.append(package)

        if missing:
            self.print_detail(f"\nOptional packages not installed: {', '.join(missing)}")
            self.print_detail("Install with: pip install " + ' '.join(missing))

        return installed

    def check_openmm(self):
        """Check OpenMM specifically"""
        self.print_header("OPENMM CHECK", '-')

        try:
            import openmm
            from openmm import version as openmm_version

            self.print_success(f"OpenMM version: {openmm_version.version}")

            # Check OpenMM installation details
            try:
                from openmm import Platform
                platforms = Platform.getPlatforms()
                self.print_detail(f"Available platforms: {', '.join([p.getName() for p in platforms])}")

                # Check CUDA
                try:
                    cuda_platform = Platform.getPlatformByName('CUDA')
                    self.print_success("CUDA platform available (GPU acceleration)")
                except:
                    self.print_warning("CUDA platform not available (GPU acceleration disabled)")

                # Check OpenCL
                try:
                    opencl_platform = Platform.getPlatformByName('OpenCL')
                    self.print_success("OpenCL platform available")
                except:
                    pass

                # Check CPU
                try:
                    cpu_platform = Platform.getPlatformByName('CPU')
                    self.print_success("CPU platform available")
                except:
                    pass

            except:
                self.print_detail("Platform information not available")

            return True

        except ImportError:
            self.print_error("OpenMM not installed")
            self.errors.append("OpenMM not installed")
            return False

    def check_amber_tools(self):
        """Check AmberTools installation"""
        self.print_header("AMBERTOOLS CHECK", '-')

        tools_found = []

        for tool, suite in self.required_tools.items():
            # Check if tool is in PATH
            tool_path = shutil.which(tool)
            if tool_path:
                self.print_success(f"{tool} found: {tool_path}")
                tools_found.append(tool)

                # Try to get version
                try:
                    result = subprocess.run([tool, '-h'],
                                          capture_output=True, text=True, check=False)
                    # Extract version from output
                    version_match = re.search(r'version\s+([0-9.]+)', result.stdout + result.stderr, re.I)
                    if version_match:
                        self.print_detail(f"  Version: {version_match.group(1)}")
                except:
                    pass
            else:
                if suite == 'AmberTools':
                    self.print_warning(f"{tool} not found (from {suite})")
                    self.warnings.append(f"{tool} not found")
                else:
                    self.print_info(f"{tool} not found ({suite} - optional)")

        if 'antechamber' in tools_found and 'tleap' in tools_found:
            self.print_success("AmberTools core tools available")
            return True
        else:
            self.print_warning("Some AmberTools components missing. Ligand parameterization may fail.")
            return False

    def check_input_files(self):
        """Check input files"""
        self.print_header("INPUT FILES CHECK", '-')

        inputs_dir = os.path.join(self.base_dir, 'inputs')

        # Check inputs directory
        if not os.path.exists(inputs_dir):
            self.print_error(f"Inputs directory not found: {inputs_dir}")
            self.errors.append("inputs directory not found")
            return False

        self.print_success(f"Inputs directory: {inputs_dir}")

        # Check required files
        required_files = ['protein.pdb']
        optional_files = ['ligand.pdb', 'ligand.mol2', 'ligand.sdf', 'complex.pdb']

        found_required = []
        missing_required = []

        for file in required_files:
            filepath = os.path.join(inputs_dir, file)
            if os.path.exists(filepath):
                size = os.path.getsize(filepath)
                self.print_success(f"{file} found ({size} bytes)")
                found_required.append(file)

                # Basic validation of PDB file
                if file.endswith('.pdb'):
                    self._validate_pdb_file(filepath)
            else:
                self.print_error(f"{file} not found")
                missing_required.append(file)

        # Check optional files
        found_optional = []
        for file in optional_files:
            filepath = os.path.join(inputs_dir, file)
            if os.path.exists(filepath):
                size = os.path.getsize(filepath)
                self.print_info(f"{file} found ({size} bytes) - optional")
                found_optional.append(file)

        # Summary
        if missing_required:
            self.print_error(f"Missing required files: {', '.join(missing_required)}")
            return False

        # Check if we have a complete system
        if 'ligand.pdb' in found_optional or 'ligand.mol2' in found_optional:
            self.print_success("Ligand files found - will run protein-ligand simulation")
            self.has_ligand = True
        else:
            self.print_info("No ligand files found - will run protein-only simulation")
            self.has_ligand = False

        return True

    def _validate_pdb_file(self, pdb_file):
        """Basic PDB file validation"""
        try:
            with open(pdb_file, 'r') as f:
                lines = f.readlines()

            atom_lines = [l for l in lines if l.startswith('ATOM') or l.startswith('HETATM')]

            if len(atom_lines) == 0:
                self.print_error(f"  No ATOM/HETATM records found in {pdb_file}")
                self.errors.append(f"Invalid PDB file: {pdb_file}")
                return False

            # Check for common issues
            residues = set()
            for line in atom_lines:
                if len(line) >= 22:
                    residue_name = line[17:20].strip()
                    residues.add(residue_name)

            self.print_detail(f"  Number of atoms: {len(atom_lines)}")
            self.print_detail(f"  Residues found: {', '.join(list(residues)[:10])}")

            # Check for missing TER records
            if not any(l.startswith('TER') for l in lines):
                self.print_warning(f"  No TER records found in {pdb_file}")

            return True

        except Exception as e:
            self.print_error(f"  Failed to validate {pdb_file}: {str(e)}")
            return False

    def check_directories(self):
        """Check and create required directories"""
        self.print_header("DIRECTORY STRUCTURE", '-')

        required_dirs = [
            'inputs',
            'outputs',
            'temp',
            'outputs/logs',
            'outputs/trajectories',
            'outputs/structures',
            'outputs/analysis',
            'outputs/plots',
            'outputs/checkpoints',
            'outputs/reports'
        ]

        for dir_path in required_dirs:
            full_path = os.path.join(self.base_dir, dir_path)
            if os.path.exists(full_path):
                self.print_success(f"{dir_path} exists")
            else:
                try:
                    os.makedirs(full_path, exist_ok=True)
                    self.print_success(f"{dir_path} created")
                except Exception as e:
                    self.print_error(f"Failed to create {dir_path}: {str(e)}")
                    self.errors.append(f"Cannot create {dir_path}")
                    return False

        # Check write permissions
        test_file = os.path.join(self.base_dir, 'temp', '.write_test')
        try:
            with open(test_file, 'w') as f:
                f.write('test')
            os.remove(test_file)
            self.print_success("Write permissions OK")
        except:
            self.print_error("No write permission in working directory")
            self.errors.append("Write permission denied")
            return False

        return True

    def check_disk_space(self):
        """Check available disk space"""
        self.print_header("DISK SPACE CHECK", '-')

        try:
            import shutil
            usage = shutil.disk_usage(self.base_dir)

            free_gb = usage.free / (1024**3)
            total_gb = usage.total / (1024**3)

            self.print_detail(f"Total disk space: {total_gb:.1f} GB")
            self.print_detail(f"Free disk space: {free_gb:.1f} GB")

            # Recommended space for MD simulation
            if free_gb < 10:
                self.print_warning(f"Low disk space ({free_gb:.1f} GB free). MD simulations may require 10-50 GB.")
                self.warnings.append(f"Low disk space: {free_gb:.1f} GB")
            elif free_gb < 50:
                self.print_warning(f"Limited disk space ({free_gb:.1f} GB). Consider cleaning up after simulation.")
            else:
                self.print_success(f"Sufficient disk space ({free_gb:.1f} GB free)")

            return True

        except Exception as e:
            self.print_warning(f"Cannot check disk space: {str(e)}")
            return True

    def check_wsl(self):
        """Check if running in WSL (Windows Subsystem for Linux)"""
        self.print_header("WSL ENVIRONMENT CHECK", '-')

        try:
            with open('/proc/version', 'r') as f:
                version_content = f.read().lower()

            if 'microsoft' in version_content or 'wsl' in version_content:
                self.print_success("Running in WSL environment")

                # Check WSL version
                if 'microsoft standard' in version_content:
                    self.print_detail("WSL 2 detected")
                else:
                    self.print_detail("WSL 1 detected (WSL 2 recommended for better performance)")

                # Check /mnt/c availability
                if os.path.exists('/mnt/c'):
                    self.print_success("Windows filesystem accessible")
                else:
                    self.print_warning("Windows filesystem not accessible")

                return True
            else:
                self.print_info("Not running in WSL (native Linux)")
                return True

        except:
            self.print_info("Not running in WSL (native Linux or other)")
            return True

    def check_gpu(self):
        """Check GPU availability"""
        self.print_header("GPU CHECK", '-')

        try:
            # Check for CUDA
            result = subprocess.run(['nvidia-smi'], capture_output=True, text=True, check=False)
            if result.returncode == 0:
                # Parse GPU information
                lines = result.stdout.split('\n')
                gpu_found = False
                for line in lines:
                    if 'GeForce' in line or 'Tesla' in line or 'Quadro' in line or 'RTX' in line:
                        gpu_name = line.strip()
                        self.print_success(f"GPU detected: {gpu_name}")
                        gpu_found = True

                # Get GPU memory
                for line in lines:
                    if 'MiB' in line and '|' in line:
                        memory_match = re.search(r'(\d+)MiB', line)
                        if memory_match:
                            memory_mb = int(memory_match.group(1))
                            self.print_detail(f"GPU Memory: {memory_mb} MB")
                            if memory_mb < 4096:
                                self.print_warning(f"Low GPU memory ({memory_mb} MB). 4GB+ recommended.")

                if not gpu_found:
                    self.print_warning("NVIDIA GPU not found or not recognized")

                return gpu_found
            else:
                self.print_info("NVIDIA GPU not detected (CPU simulation will be used)")
                return False

        except FileNotFoundError:
            self.print_info("nvidia-smi not found (GPU not available)")
            return False

    def check_environment_variables(self):
        """Check important environment variables"""
        self.print_header("ENVIRONMENT VARIABLES", '-')

        important_vars = [
            'PATH',
            'LD_LIBRARY_PATH',
            'OPENMM_PLUGIN_DIR',
            'CUDA_HOME',
            'CONDA_PREFIX'
        ]

        for var in important_vars:
            value = os.environ.get(var, 'NOT SET')
            if value != 'NOT SET':
                if var == 'PATH':
                    # Truncate PATH for display
                    paths = value.split(os.pathsep)
                    self.print_detail(f"{var}: {paths[0]} ... ({len(paths)} entries)")
                else:
                    self.print_detail(f"{var}: {value[:80]}")

                if var == 'OPENMM_PLUGIN_DIR' and value == 'NOT SET':
                    self.print_warning("OPENMM_PLUGIN_DIR not set (might cause issues)")

        return True

    def generate_report(self):
        """Generate comprehensive pre-check report"""
        self.print_header("PRE-CHECK SUMMARY", '=')

        # Statistics
        total_checks = len(self.checks) + len(self.warnings) + len(self.errors)
        self.print_detail(f"Total checks performed: {total_checks}")
        self.print_detail(f"Passed: {len(self.checks)}")
        self.print_detail(f"Warnings: {len(self.warnings)}")
        self.print_detail(f"Errors: {len(self.errors)}")

        print()

        # List warnings
        if self.warnings:
            self.print_header("WARNINGS", '-')
            for warning in self.warnings:
                self.print_warning(warning)

        # List errors
        if self.errors:
            self.print_header("ERRORS", '-')
            for error in self.errors:
                self.print_error(error)

        # Final verdict
        print()
        if self.errors:
            self.print_header("VERDICT: FAILED", '=')
            self.print_error("Pre-check failed. Please fix the errors above before running MD simulation.")
            return False
        elif self.warnings:
            self.print_header("VERDICT: PASSED WITH WARNINGS", '=')
            self.print_warning("Pre-check passed with warnings. Simulation may work but with limitations.")
            return True
        else:
            self.print_header("VERDICT: PASSED", '=')
            self.print_success("All pre-checks passed! System is ready for MD simulation.")
            return True

    def save_report(self):
        """Save pre-check report to file"""
        report_dir = os.path.join(self.base_dir, 'outputs', 'reports')
        os.makedirs(report_dir, exist_ok=True)

        report_file = os.path.join(report_dir, f"precheck_report_{datetime.now().strftime('%Y%m%d_%H%M%S')}.txt")

        # Use UTF-8 encoding for file writing
        with open(report_file, 'w', encoding='utf-8') as f:
            f.write("="*80 + "\n")
            f.write("MD SIMULATION PRE-CHECK REPORT\n")
            f.write("="*80 + "\n\n")

            f.write(f"Date: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
            f.write(f"Base Directory: {self.base_dir}\n\n")

            f.write("SYSTEM INFORMATION\n")
            f.write("-"*40 + "\n")
            for key, value in self.system_info.items():
                f.write(f"{key}: {value}\n")

            f.write("\nPRE-CHECK RESULTS\n")
            f.write("-"*40 + "\n")
            f.write(f"Total Checks: {len(self.checks) + len(self.warnings) + len(self.errors)}\n")
            f.write(f"Passed: {len(self.checks)}\n")
            f.write(f"Warnings: {len(self.warnings)}\n")
            f.write(f"Errors: {len(self.errors)}\n")

            if self.warnings:
                f.write("\nWARNINGS\n")
                f.write("-"*40 + "\n")
                for warning in self.warnings:
                    f.write(f"⚠ {warning}\n")

            if self.errors:
                f.write("\nERRORS\n")
                f.write("-"*40 + "\n")
                for error in self.errors:
                    f.write(f"✗ {error}\n")

            f.write("\nRECOMMENDATIONS\n")
            f.write("-"*40 + "\n")
            if not self.errors:
                f.write("✓ System is ready for MD simulation\n")
            else:
                f.write("✗ Please fix all errors before proceeding\n")
                f.write("  - Install missing packages\n")
                f.write("  - Check input files\n")
                f.write("  - Verify directory permissions\n")

        self.print_info(f"Report saved to: {report_file}")
        return report_file

    def run_all_checks(self):
        """Run all pre-checks"""
        print(f"\n{self.colors['bold']}{self.colors['purple']}")
        print("╔════════════════════════════════════════════════════════════════════════════╗")
        print("║                    MD SIMULATION PRE-CHECK SYSTEM                          ║")
        print("║                              Version 2.0                                   ║")
        print("╚════════════════════════════════════════════════════════════════════════════╝")
        print(f"{self.colors['reset']}")

        # Run all checks
        self.collect_system_info()
        self.check_operating_system()
        self.check_python_environment()
        self.check_required_packages()
        self.check_optional_packages()
        self.check_openmm()
        self.check_amber_tools()
        self.check_input_files()
        self.check_directories()
        self.check_disk_space()
        self.check_wsl()
        self.check_gpu()
        self.check_environment_variables()

        # Generate final report
        passed = self.generate_report()

        # Save report
        self.save_report()

        return passed

def main():
    """Main entry point"""
    parser = argparse.ArgumentParser(
        description='Pre-check system for Molecular Dynamics simulations',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python precheck.py                          # Check current directory
  python precheck.py /path/to/simulation     # Check specific directory
  python precheck.py --verbose               # Verbose output
  python precheck.py --save-report           # Save report to file
        """
    )

    parser.add_argument('base_dir', nargs='?', default=os.getcwd(),
                       help='Base working directory (default: current directory)')
    parser.add_argument('--verbose', '-v', action='store_true',
                       help='Print verbose output')
    parser.add_argument('--quiet', '-q', action='store_true',
                       help='Quiet mode (minimal output)')
    parser.add_argument('--save-report', '-s', action='store_true',
                       help='Save report to file')

    args = parser.parse_args()

    # Set verbose mode
    verbose = not args.quiet

    # Create precheck instance
    precheck = MDPrecheck(args.base_dir, verbose=verbose)

    # Run all checks
    success = precheck.run_all_checks()

    # Exit with appropriate code
    sys.exit(0 if success else 1)

if __name__ == "__main__":
    main()
