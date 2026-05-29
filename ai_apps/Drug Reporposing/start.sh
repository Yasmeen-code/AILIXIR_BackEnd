#!/bin/bash
# ========================================================================
# Drug Repurposing AI System - Production Startup (Linux/Mac)
# ========================================================================
# This script installs all dependencies and starts the API server

set -e

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

clear

echo -e "${BLUE}"
echo "========================================================================="
echo " 🧬  DRUG REPURPOSING AI SYSTEM"
echo " 🚀  Production API Startup (Linux/Mac)"
echo "========================================================================="
echo -e "${NC}"
echo ""

# Check if Python 3 is installed
if ! command -v python3 &> /dev/null; then
    echo -e "${RED}ERROR: Python 3 is not installed${NC}"
    echo "Please install Python 3.10+ from https://www.python.org/"
    exit 1
fi

PYTHON_VERSION=$(python3 --version | awk '{print $2}')
echo "✅ Python $PYTHON_VERSION found"
echo ""

# Step 1: Create/Activate virtual environment
echo "[1/5] Setting up virtual environment..."
if [ -d "venv" ]; then
    echo "✅ Virtual environment already exists"
else
    echo "Creating new virtual environment..."
    python3 -m venv venv
    if [ $? -ne 0 ]; then
        echo -e "${RED}ERROR: Failed to create virtual environment${NC}"
        exit 1
    fi
fi

source venv/bin/activate
if [ $? -ne 0 ]; then
    echo -e "${RED}ERROR: Failed to activate virtual environment${NC}"
    exit 1
fi
echo "✅ Virtual environment activated"
echo ""

# Step 2: Upgrade pip
echo "[2/5] Upgrading pip and setuptools..."
python -m pip install --upgrade pip setuptools wheel -q 2>/dev/null
if [ $? -eq 0 ]; then
    echo "✅ pip upgraded"
else
    echo "⚠️  pip upgrade had warnings (continuing...)"
fi
echo ""

# Step 3: Install base dependencies
echo "[3/5] Installing dependencies from requirements.txt..."
if [ ! -f "requirements.txt" ]; then
    echo -e "${RED}ERROR: requirements.txt not found${NC}"
    exit 1
fi

pip install -r requirements.txt -q 2>/dev/null
if [ $? -ne 0 ]; then
    echo "⚠️  WARNING: Some dependencies failed to install"
    echo "This may be normal if you're missing optional packages"
fi
echo "✅ Base dependencies installed"
echo ""

# Step 4: Install specialized packages (optional but recommended)
echo "[4/5] Installing specialized ML packages..."

echo "   Installing DeepPurpose (AI predictor) from GitHub..."
pip install git+https://github.com/kexinhuang12345/DeepPurpose.git -q 2>/dev/null
if [ $? -eq 0 ]; then
    echo "   ✅ DeepPurpose installed"
else
    echo "   ⚠️  WARNING: DeepPurpose installation failed"
    echo "      The API will still run but use local mock predictions"
    echo "      To use real AI predictions, install manually:"
    echo "      pip install git+https://github.com/kexinhuang12345/DeepPurpose.git"
fi

echo "   Installing TDC (drug data) from GitHub..."
pip install git+https://github.com/Alantic/TDC.git -q 2>/dev/null
if [ $? -eq 0 ]; then
    echo "   ✅ TDC installed"
else
    echo "   ℹ️  NOTE: TDC installation failed"
    echo "      API will use local fallback drug library (40+ FDA-approved drugs)"
fi
echo ""

# Step 5: Check for GPU
echo "[5/5] Checking system capabilities..."
if command -v nvidia-smi &> /dev/null; then
    echo "✅ NVIDIA GPU detected - CUDA acceleration will be used"
else
    echo "ℹ️  No NVIDIA GPU detected - running in CPU mode"
    echo "   (API will still work, predictions may be slower)"
fi
echo ""

# Display startup info
echo -e "${GREEN}=========================================================================${NC}"
echo -e "${GREEN}✅ SETUP COMPLETE - STARTING API SERVER${NC}"
echo -e "${GREEN}=========================================================================${NC}"
echo ""
echo "🌐 API DOCUMENTATION & ENDPOINTS:"
echo "   • Interactive Swagger Docs:  http://localhost:8000/docs"
echo "   • Alternative ReDoc Docs:    http://localhost:8000/redoc"
echo "   • OpenAPI Schema:            http://localhost:8000/openapi.json"
echo ""
echo "📊 MONITORING ENDPOINTS:"
echo "   • Health Check:              http://localhost:8000/health"
echo "   • Model Status:              http://localhost:8000/api/v1/model-status"
echo ""
echo "🧬 MAIN SCREENING ENDPOINT (POST):"
echo "   • Virtual Screening:         http://localhost:8000/api/v1/screen"
echo "     Example request body:"
echo "     {"
echo "       \"disease_name\": \"Type 2 Diabetes\","
echo "       \"min_score\": 0.5,"
echo "       \"top_n_targets\": 10,"
echo "       \"known_drugs\": [\"Metformin\"]"
echo "     }"
echo ""
echo "📋 ADDITIONAL ENDPOINTS (POST):"
echo "   • Get Disease Targets:       http://localhost:8000/api/v1/disease-targets"
echo "   • Get Protein Sequences:     http://localhost:8000/api/v1/protein-sequences"
echo "   • Get Drug Library:          http://localhost:8000/api/v1/drug-library"
echo ""
echo "⏹️  Press CTRL+C to stop the server"
echo -e "${GREEN}=========================================================================${NC}"
echo ""

# Start the API server
python -m uvicorn app.main:app --host 0.0.0.0 --port 8000 --reload

    echo ""
}

echo "[4.75/5] Checking GPU availability..."
python -c "import torch; print(f'GPU Available: {torch.cuda.is_available()}')" 2>/dev/null || {
    echo "GPU detection skipped (continue with CPU-only mode)"
}
echo ""

echo "[5/5] Starting API server..."
echo ""
echo "=============================================="
echo "✅ Setup Complete! Starting API server..."
echo "=============================================="
echo ""
echo "API Endpoints:"
echo "  Documentation:  http://localhost:8000/docs"
echo "  Health Check:   http://localhost:8000/health"
echo "  Model Status:   http://localhost:8000/api/v1/model-status"
echo "  Virtual Screen: http://localhost:8000/api/v1/screen"
echo ""
echo "Running Mode:"
echo "  If DeepPurpose installed: PRODUCTION (Real AI predictions)"
echo "  If DeepPurpose failed:    MOCK (Simulated predictions)"
echo ""
echo "Check http://localhost:8000/api/v1/model-status for actual mode"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""

uvicorn app.main:app --reload --host 0.0.0.0 --port 8000
