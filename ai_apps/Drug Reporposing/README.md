# 🧬 Drug Repurposing AI System

An advanced AI-powered drug repurposing platform that identifies potential therapeutic uses for existing drugs by predicting drug-target binding affinities using deep learning models.

> ⭐ **PRODUCTION-READY SYSTEM** - All real data, real APIs, real AI models. See [QUICK_START.md](QUICK_START.md) for 60-second setup.

## 📚 Documentation

For different user types:
- **⚡ Quick Start** (60 seconds): See [QUICK_START.md](QUICK_START.md)
- **📖 Full Guide** (comprehensive): See [PRODUCTION_GUIDE.md](PRODUCTION_GUIDE.md)
- **🔧 Implementation Details**: See [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md)

## 📋 Overview

This system implements a complete pipeline for drug repurposing:

1. **Disease Target Identification** - Identifies proteins associated with a disease using Open Targets API
2. **Protein Sequence Retrieval** - Fetches amino acid sequences from UniProt
3. **Drug Library Loading** - Loads FDA-approved drugs with SMILES notation from TDC
4. **AI-Based Virtual Screening** - Uses DeepPurpose (MPNN-CNN) to predict drug-target binding affinities
5. **Result Processing** - Ranks results and identifies potential new treatments vs. known treatments

## 🏗️ Project Structure

```
Drug Repurposing/
├── app/
│   ├── __init__.py
│   ├── main.py                 # FastAPI application (8 endpoints)
│   ├── config.py               # Configuration & GPU detection
│   ├── models.py               # Pydantic data models
│   ├── local_tdc.py            # Fallback: 40+ FDA drugs
│   └── pipelines/
│       ├── __init__.py
│       ├── disease_targets.py   # Stage 1: OpenTargets API
│       ├── protein_sequences.py # Stage 2: UniProt API
│       ├── drug_library.py      # Stage 3: TDC + Fallback
│       ├── ai_screening.py      # Stage 4: DeepPurpose AI
│       └── result_processing.py # Stage 5: Results
├── docker/
│   ├── Dockerfile             # Docker configuration
│   └── docker-compose.yml     # Docker Compose
├── requirements.txt            # All dependencies
├── start.bat                   # Windows auto-setup
├── start.sh                    # Linux/Mac auto-setup
├── test_api.py                 # Pytest tests
├── test_integration.py         # Integration tests
├── verify_system.py            # System verification
├── QUICK_START.md              # 60-second setup
├── PRODUCTION_GUIDE.md         # Complete guide
├── IMPLEMENTATION_SUMMARY.md   # What was built
└── README.md                   # This file
```

## 🚀 Get Started (Choose One)

### 🏃 **Option 1: Auto-Setup (Recommended)**

Just run one command and everything is set up automatically:

**Windows:**
```bash
start.bat
```

**Linux / Mac:**
```bash
chmod +x start.sh && ./start.sh
```

Both scripts will:
- Create virtual environment
- Install all dependencies
- Install DeepPurpose & TDC (with fallbacks)
- Start API server on http://localhost:8000
- Display endpoint documentation

### 📖 **Option 2: Manual Setup**

#### Prerequisites
- Python 3.10+
- pip

#### Installation Steps

1. **Create virtual environment:**
```bash
cd "e:\pyDS\Drug Reporposing"
```

2. **Create virtual environment:**
```bash
# Using venv
python -m venv venv

# Activate virtual environment
# On Windows:
venv\Scripts\activate
# On Linux/Mac:
source venv/bin/activate
```

3. **Install dependencies:**
```bash
pip install -r requirements.txt
```

4. **Run the API:**
```bash
uvicorn app.main:app --host 0.0.0.0 --port 8000 --reload
```

5. **Access the API:**
- **Interactive API Docs** (Swagger UI): http://localhost:8000/docs
- **Alternative API Docs** (ReDoc): http://localhost:8000/redoc
- **Health Check**: http://localhost:8000/health

### Option 2: Docker Installation (Recommended)

#### Prerequisites
- Docker
- Docker Compose

#### Setup and Run

1. **Navigate to project directory:**
```bash
cd "e:\pyDS\Drug Reporposing"
```

2. **Build and start containers:**
```bash
docker-compose -f docker/docker-compose.yml up -d
```

3. **View logs:**
```bash
docker-compose -f docker/docker-compose.yml logs -f api
```

4. **Access the API:**
- http://localhost:8000/docs

5. **Stop containers:**
```bash
docker-compose -f docker/docker-compose.yml down
```

## 📡 API Endpoints

### Health Check
```
GET /health
```
Check if the service is running.

**Response:**
```json
{
  "status": "healthy",
  "version": "1.0.0",
  "service": "Drug Repurposing AI System"
}
```

### Disease Targets
```
POST /api/v1/disease-targets
```
Get target proteins associated with a disease.

**Request:**
```json
{
  "disease_name": "Type 2 Diabetes",
  "top_n": 10
}
```

**Response:**
```json
{
  "disease": "Type 2 Diabetes",
  "total_targets": 10,
  "targets": [
    {
      "symbol": "INSR",
      "name": "Insulin Receptor",
      "score": 0.85
    }
  ]
}
```

### Protein Sequences
```
POST /api/v1/protein-sequences
```
Fetch protein sequences from UniProt.

**Request:**
```json
[
  {"symbol": "INSR", "name": "Insulin Receptor", "score": 0.85}
]
```

### Drug Library
```
GET /api/v1/drug-library
```
Get available FDA-approved drugs.

**Response:**
```json
{
  "total_drugs": 100,
  "drugs": [
    {
      "name": "Drug_001",
      "smiles": "CC(=O)Oc1ccccc1C(=O)O",
      "drug_id": "1"
    }
  ]
}
```

### Virtual Drug Screening (Main Pipeline)
```
POST /api/v1/screen
```
Run the complete drug repurposing pipeline.

**Request:**
```json
{
  "disease_name": "Type 2 Diabetes",
  "min_score": 0.5,
  "top_n_targets": 10,
  "known_drugs": ["Metformin", "Insulin"]
}
```

**Response:**
```json
{
  "disease": "Type 2 Diabetes",
  "total_targets": 10,
  "total_drugs": 100,
  "total_predictions": 1000,
  "top_results": [
    {
      "drug_name": "Drug_001",
      "target_symbol": "INSR",
      "score": 0.85,
      "status": "🆕 Potential Discovery"
    }
  ],
  "success": true,
  "message": "Screening completed in 45.32s. Found 892 candidates."
}
```

## ⚙️ Configuration

Edit `app/config.py` to customize:

```python
# API Settings
DEBUG = True/False
HOST = "0.0.0.0"
PORT = 8000

# Model Settings
USE_MOCK_MODEL = False  # Set to True for testing without DeepPurpose
DEEP_PURPOSE_MODEL = "MPNN_CNN_BindingDB"

# Drug Settings
USE_MOCK_DRUGS = False  # Set to True for testing without TDC
TDC_DATASET = "Half_Life_Obach"

# Screening Parameters
DEFAULT_TOP_TARGETS = 10
DEFAULT_TOP_RESULTS = 15
MAX_TARGETS = 50
```

## 🔧 Pipeline Stages

### Stage 1: Disease Target Mapping
**File:** `pipelines/disease_targets.py`
```python
pipeline = DiseaseTargetPipeline()
targets = pipeline.get_disease_targets("Type 2 Diabetes", top_n=10)
```

### Stage 2: Protein Sequences
**File:** `pipelines/protein_sequences.py`
```python
pipeline = ProteinSequencePipeline()
targets_with_seqs = pipeline.get_protein_sequences(targets)
```

### Stage 3: Drug Library
**File:** `pipelines/drug_library.py`
```python
pipeline = DrugLibraryPipeline()
drugs = pipeline.load_drug_library()
```

### Stage 4: AI Screening
**File:** `pipelines/ai_screening.py`
```python
pipeline = AIScreeningPipeline()
pipeline.load_model("MPNN_CNN_BindingDB")
results = pipeline.run_virtual_screening(drugs, targets_with_seqs)
```

### Stage 5: Result Processing
**File:** `pipelines/result_processing.py`
```python
pipeline = ResultProcessingPipeline()
final_results = pipeline.process_final_results(results, known_drugs=["Metformin"])
```

## 🧪 Running in Mock Mode

For testing without external dependencies:

**Set environment variables:**
```bash
# Linux/Mac
export USE_MOCK_MODEL=True
export USE_MOCK_DRUGS=True

# Windows PowerShell
$env:USE_MOCK_MODEL="True"
$env:USE_MOCK_DRUGS="True"
```

Or modify `app/config.py`:
```python
USE_MOCK_MODEL = True
USE_MOCK_DRUGS = True
```

## 📦 Installing External Dependencies

### DeepPurpose (AI Model)
```bash
pip install git+https://github.com/kexinhuang12345/DeepPurpose.git
pip install torch  # Required by DeepPurpose
```

### TDC (Therapeutic Data Commons)
```bash
pip install tdc
```

## 🐳 Docker Customization

### Build Custom Image
```bash
docker build -f docker/Dockerfile -t drug-repurposing:latest .
```

### Run Container Directly
```bash
docker run -p 8000:8000 \
  -e USE_MOCK_MODEL=False \
  -e LOG_LEVEL=INFO \
  drug-repurposing:latest
```

### Override Environment Variables
```bash
docker-compose -f docker/docker-compose.yml up -d \
  -e USE_MOCK_MODEL=False \
  -e DEBUG=False
```

## 📊 Example Workflow

### 1. Start the API
```bash
docker-compose -f docker/docker-compose.yml up
```

### 2. Query Disease Targets
```bash
curl -X POST http://localhost:8000/api/v1/disease-targets \
  -H "Content-Type: application/json" \
  -d '{"disease_name": "Type 2 Diabetes", "top_n": 10}'
```

### 3. Run Complete Screening
```bash
curl -X POST http://localhost:8000/api/v1/screen \
  -H "Content-Type: application/json" \
  -d '{
    "disease_name": "Type 2 Diabetes",
    "min_score": 0.5,
    "top_n_targets": 10,
    "known_drugs": ["Metformin", "Insulin"]
  }'
```

## 🔍 Logging

View real-time logs:
```bash
# Docker
docker-compose -f docker/docker-compose.yml logs -f api

# Local
# Logs are output to console
```

Configure log level in `app/config.py`:
```python
LOG_LEVEL = "INFO"  # DEBUG, INFO, WARNING, ERROR, CRITICAL
```

## 🏆 Key Features

✅ **Modular Pipeline Architecture** - Separation of concerns for maintainability
✅ **FastAPI Integration** - Modern async REST API with auto-documentation
✅ **Mock Mode Support** - Test without external dependencies
✅ **Docker Containerization** - Easy deployment and scalability
✅ **Error Handling** - Comprehensive error handling and logging
✅ **CORS Support** - Ready for cross-origin requests
✅ **Health Checks** - Built-in monitoring endpoints
✅ **Type Hints** - Full Python type annotations
✅ **Pydantic Validation** - Automatic request/response validation

## 🚦 Status Codes

- **200** - Success
- **400** - Bad Request (validation error)
- **404** - Not Found (disease not found)
- **500** - Server Error (API failure)

## 🔐 Security Notes

- Non-root Docker user for security
- CORS configured for safe cross-origin requests
- Input validation on all endpoints
- Timeout protection on API calls

## 🤝 Contributing

To extend this system:

1. **Add new pipeline stage** - Create new file in `pipelines/`
2. **Add new API endpoint** - Extend `main.py`
3. **Add new data model** - Update `models.py`
4. **Update configuration** - Modify `config.py`

Example: Adding a new pipeline stage:
```python
# pipelines/new_stage.py
class NewStagePipeline:
    def process(self, data):
        # Your logic here
        return processed_data
```

## 📚 References

- [Open Targets API](https://docs.targetvalidation.org/)
- [UniProt REST API](https://www.uniprot.org/help/api)
- [TDC Documentation](https://tdcommons.ai/)
- [DeepPurpose GitHub](https://github.com/kexinhuang12345/DeepPurpose)
- [FastAPI Documentation](https://fastapi.tiangolo.com/)

## 📄 License

[Add your license here]

## 📧 Support

For issues or questions, please create an issue on the repository.

## 🎉 Acknowledgments

- Open Targets Initiative
- UniProt Consortium
- TDC Team
- DeepPurpose Team

---

**Version:** 1.0.0  
**Last Updated:** 2024
