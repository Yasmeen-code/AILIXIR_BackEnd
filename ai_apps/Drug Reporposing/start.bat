@echo off
REM ========================================================================
REM Drug Repurposing AI System - Production Startup (Windows)
REM ========================================================================
REM This script installs all dependencies and starts the API server

setlocal enabledelayedexpansion

cls
echo.
echo ========================================================================
echo  ^^ DRUG REPURPOSING AI SYSTEM ^^
echo  ^^ Production API Startup ^^
echo ========================================================================
echo.

REM Check Python installation
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python 3.10+ from https://www.python.org/
    pause
    exit /b 1
)

REM Step 1: Create/Activate virtual environment
echo [1/4] Setting up virtual environment...
if not exist "venv" (
    echo Creating new virtual environment...
    python -m venv venv
    if !errorlevel! neq 0 (
        echo ERROR: Failed to create virtual environment
        pause
        exit /b 1
    )
) else (
    echo Virtual environment already exists
)

call venv\Scripts\activate.bat
if !errorlevel! neq 0 (
    echo ERROR: Failed to activate virtual environment
    pause
    exit /b 1
)
echo ✅ Virtual environment activated
echo.

REM Step 2: Upgrade pip
echo [2/4] Upgrading pip and setuptools...
python -m pip install --upgrade pip setuptools wheel -q
echo ✅ pip upgraded
echo.

REM Step 3: Install base dependencies
echo [3/4] Installing dependencies from requirements.txt...
pip install -r requirements.txt -q
if !errorlevel! neq 0 (
    echo WARNING: Some dependencies failed to install
    echo This may be normal if you're missing optional packages
)
echo.

REM Step 4: Install special packages (optional but important)
echo [3.5/4] Installing specialized packages...
echo Installing DeepPurpose from GitHub...
pip install git+https://github.com/kexinhuang12345/DeepPurpose.git -q 2>nul
if !errorlevel! neq 0 (
    echo WARNING: DeepPurpose installation failed
    echo The API will still run but use local mock predictions
    echo To use real AI predictions, install manually:
    echo   pip install git+https://github.com/kexinhuang12345/DeepPurpose.git
)

echo Installing TDC (Therapeutic Data Commons)...
pip install git+https://github.com/Alantic/TDC.git -q 2>nul
if !errorlevel! neq 0 (
    echo NOTE: TDC installation failed
    echo The API will use local fallback drug library (40+ FDA-approved drugs)
)
echo.

REM Step 5: Display startup info and start server
echo ========================================================================
echo ✅ [4/4] SETUP COMPLETE - STARTING API SERVER
echo ========================================================================
echo.
echo 🌐 API DOCUMENTATION & ENDPOINTS:
echo   • Interactive Swagger Docs:  http://localhost:8000/docs
echo   • Alternative ReDoc Docs:    http://localhost:8000/redoc
echo   • OpenAPI Schema:            http://localhost:8000/openapi.json
echo.
echo 📊 MONITORING ENDPOINTS:
echo   • Health Check:              http://localhost:8000/health
echo   • Model Status:              http://localhost:8000/api/v1/model-status
echo.
echo 🧬 MAIN SCREENING ENDPOINT (POST):
echo   • Virtual Screening:         http://localhost:8000/api/v1/screen
echo     Request body:
echo     {
echo       "disease_name": "Type 2 Diabetes",
echo       "min_score": 0.5,
echo       "top_n_targets": 10,
echo       "known_drugs": ["Metformin"]
echo     }
echo.
echo 📋 ADDITIONAL ENDPOINTS (POST):
echo   • Get Disease Targets:       http://localhost:8000/api/v1/disease-targets
echo   • Get Protein Sequences:     http://localhost:8000/api/v1/protein-sequences
echo   • Get Drug Library:          http://localhost:8000/api/v1/drug-library
echo.
echo ⏹️  Press CTRL+C to stop the server
echo ========================================================================
echo.

REM Start the API server
python -m uvicorn app.main:app --host 0.0.0.0 --port 8000 --reload

pause

echo ========================================================================
echo.

python -m uvicorn app.main:app --host 0.0.0.0 --port 8000 --log-level info

if %errorlevel% neq 0 (
    echo.
    echo ❌ API failed to start
    echo.
    echo Troubleshooting:
    echo   • Check dependencies: pip install -r requirements.txt
    echo   • Check DeepPurpose: python -c "import deepPurpose"
    echo   • Check app exists: dir app\main.py
    echo.
    pause
)

endlocal
