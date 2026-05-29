#!/usr/bin/env python
"""
System Verification Script
Verifies all components are properly configured and working

Run with: python verify_system.py
"""
import os
import sys
import json
from pathlib import Path

def check_file_exists(file_path: str, description: str = "") -> bool:
    """Check if a file exists"""
    desc = f" ({description})" if description else ""
    if os.path.exists(file_path):
        print(f"  ✅ {file_path}{desc}")
        return True
    else:
        print(f"  ❌ {file_path}{desc} - NOT FOUND")
        return False

def check_python_version() -> bool:
    """Check Python version"""
    print("\n📌 Python Version:")
    version = f"{sys.version_info.major}.{sys.version_info.minor}"
    if sys.version_info >= (3, 10):
        print(f"  ✅ Python {version} (requirement: 3.10+)")
        return True
    else:
        print(f"  ❌ Python {version} (requirement: 3.10+)")
        return False

def check_virtual_env() -> bool:
    """Check if in virtual environment"""
    print("\n🔧 Virtual Environment:")
    if hasattr(sys, 'real_prefix') or (hasattr(sys, 'base_prefix') and sys.base_prefix != sys.prefix):
        print(f"  ✅ Virtual environment detected")
        return True
    else:
        print(f"  ⚠️  Not in virtual environment (recommended for production)")
        return False

def check_package(package_name: str, description: str = "") -> bool:
    """Check if a package is installed"""
    try:
        __import__(package_name)
        desc = f" - {description}" if description else ""
        print(f"  ✅ {package_name}{desc}")
        return True
    except ImportError:
        desc = f" - {description}" if description else ""
        print(f"  ❌ {package_name}{desc} - NOT INSTALLED")
        return False

def check_core_packages() -> bool:
    """Check core dependencies"""
    print("\n📦 Core Dependencies:")
    core_packages = [
        ("fastapi", "FastAPI web framework"),
        ("uvicorn", "ASGI server"),
        ("pydantic", "Data validation"),
        ("numpy", "Numerical computing"),
        ("pandas", "Data processing"),
        ("requests", "HTTP client"),
        ("torch", "PyTorch deep learning"),
    ]
    
    all_installed = True
    for package, desc in core_packages:
        if not check_package(package, desc):
            all_installed = False
    
    return all_installed

def check_optional_packages() -> bool:
    """Check optional but recommended packages"""
    print("\n⭐ Optional Packages (recommended):")
    optional_packages = [
        ("DeepPurpose", "AI prediction model"),
        ("tdc", "Therapeutic Data Commons"),
    ]
    
    all_installed = True
    for package, desc in optional_packages:
        result = check_package(package, desc)
        if not result:
            print(f"     ℹ️  To install: pip install git+https://github.com/kexinhuang12345/{package}.git")
            all_installed = False
    
    return all_installed

def check_gpu() -> bool:
    """Check GPU availability"""
    print("\n🎮 GPU Support:")
    try:
        import torch
        if torch.cuda.is_available():
            print(f"  ✅ GPU detected: {torch.cuda.get_device_name(0)}")
            print(f"     CUDA Version: {torch.version.cuda}")
            print(f"     Device Count: {torch.cuda.device_count()}")
            return True
        else:
            print(f"  ℹ️  No GPU detected - using CPU mode")
            print(f"     (System will still work, but predictions will be slower)")
            return False
    except ImportError:
        print(f"  ❌ PyTorch not installed")
        return False

def check_project_structure() -> bool:
    """Check project file structure"""
    print("\n📂 Project Structure:")
    
    required_files = [
        ("app/main.py", "FastAPI application"),
        ("app/config.py", "Configuration"),
        ("app/models.py", "Data models"),
        ("app/local_tdc.py", "Local drug database"),
        ("app/pipelines/__init__.py", "Pipeline module"),
        ("app/pipelines/disease_targets.py", "Disease targets"),
        ("app/pipelines/protein_sequences.py", "Protein sequences"),
        ("app/pipelines/drug_library.py", "Drug library"),
        ("app/pipelines/ai_screening.py", "AI screening"),
        ("app/pipelines/result_processing.py", "Result processing"),
        ("requirements.txt", "Dependencies"),
        ("start.bat", "Windows startup"),
        ("start.sh", "Linux/Mac startup"),
        ("test_api.py", "Tests"),
        ("PRODUCTION_GUIDE.md", "Documentation"),
    ]
    
    all_present = True
    for file_path, description in required_files:
        if not check_file_exists(file_path, description):
            all_present = False
    
    return all_present

def check_config_files() -> bool:
    """Check configuration values"""
    print("\n⚙️  Configuration:")
    
    try:
        from app.config import settings
        print(f"  ✅ Config loaded successfully")
        print(f"     Device: {settings.DEVICE}")
        print(f"     Max Drugs: {settings.MAX_DRUGS_FOR_DEMO}")
        print(f"     Batch Size: {settings.BATCH_SIZE}")
        print(f"     API Version: {settings.API_VERSION}")
        return True
    except Exception as e:
        print(f"  ❌ Config loading failed: {str(e)}")
        return False

def check_data_models() -> bool:
    """Check data models"""
    print("\n📋 Data Models:")
    
    try:
        from app.models import (
            ScreeningRequest,
            ScreeningResponse,
            PredictionResult,
            DiseaseSearchRequest,
        )
        print(f"  ✅ All data models loaded")
        return True
    except Exception as e:
        print(f"  ❌ Data models failed: {str(e)}")
        return False

def check_pipelines() -> bool:
    """Check pipeline modules"""
    print("\n🔄 Pipeline Modules:")
    
    try:
        from app.pipelines import (
            DiseaseTargetPipeline,
            ProteinSequencePipeline,
            DrugLibraryPipeline,
            AIScreeningPipeline,
            ResultProcessingPipeline,
        )
        print(f"  ✅ All pipelines loaded")
        return True
    except Exception as e:
        print(f"  ❌ Pipelines failed: {str(e)}")
        return False

def main():
    """Run all checks"""
    print("\n" + "="*70)
    print("  🧬 DRUG REPURPOSING SYSTEM - VERIFICATION")
    print("="*70)
    
    checks = []
    
    # Run all checks
    checks.append(("Python Version", check_python_version()))
    checks.append(("Virtual Environment", check_virtual_env()))
    checks.append(("Core Dependencies", check_core_packages()))
    checks.append(("Optional Dependencies", check_optional_packages()))
    checks.append(("GPU Support", check_gpu()))
    checks.append(("Project Structure", check_project_structure()))
    checks.append(("Configuration", check_config_files()))
    checks.append(("Data Models", check_data_models()))
    checks.append(("Pipeline Modules", check_pipelines()))
    
    # Summary
    print("\n" + "="*70)
    print("  📊 VERIFICATION SUMMARY")
    print("="*70)
    
    passed = sum(1 for _, result in checks if result)
    total = len(checks)
    
    for check_name, result in checks:
        status = "✅ PASS" if result else "❌ FAIL"
        print(f"{check_name:.<50} {status}")
    
    print("\n" + "="*70)
    
    if passed == total:
        print("✅ ALL CHECKS PASSED - SYSTEM READY")
        print("="*70)
        print("\nNext steps:")
        print("  1. Start the API: python start.bat (Windows) or ./start.sh (Linux/Mac)")
        print("  2. Visit: http://localhost:8000/docs")
        print("  3. Test the /api/v1/screen endpoint")
        print("\nFor help: See PRODUCTION_GUIDE.md or QUICK_START.md")
        return 0
    else:
        failures = total - passed
        print(f"⚠️  {failures} CHECK(S) FAILED")
        print("="*70)
        print("\nSee above for details and recommendations.")
        print("Missing packages can be installed with requirements.txt:")
        print("  pip install -r requirements.txt")
        return 1

if __name__ == "__main__":
    sys.exit(main())
