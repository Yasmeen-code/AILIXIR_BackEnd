# API Documentation

## Drug Repurposing AI System - REST API

### Base URL
```
http://localhost:8000
```

### API Version
```
v1
```

---

## 📌 Endpoints Overview

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/` | Root endpoint - API info |
| `GET` | `/health` | Health check |
| `GET` | `/docs` | Interactive API documentation (Swagger UI) |
| `POST` | `/api/v1/disease-targets` | Get disease targets |
| `POST` | `/api/v1/protein-sequences` | Get protein sequences |
| `GET` | `/api/v1/drug-library` | Get drug library |
| `POST` | `/api/v1/screen` | Run virtual screening (main pipeline) |

---

## 🔍 Detailed Endpoint Documentation

### 1. Root Endpoint
```
GET /
```

**Description:** Get API information and available endpoints

**Response:**
```json
{
  "name": "Drug Repurposing AI System",
  "version": "1.0.0",
  "description": "AI-powered drug repurposing system using Deep Learning and Open Targets",
  "docs": "/docs",
  "health": "/health"
}
```

**Status Codes:**
- `200` - Success

---

### 2. Health Check
```
GET /health
```

**Description:** Check if the service is healthy and operational

**Response:**
```json
{
  "status": "healthy",
  "version": "1.0.0",
  "service": "Drug Repurposing AI System"
}
```

**Status Codes:**
- `200` - Service is healthy

**Example:**
```bash
curl http://localhost:8000/health
```

---

### 3. Get Disease Targets
```
POST /api/v1/disease-targets
```

**Description:** Identify proteins associated with a disease using Open Targets API

**Request Body:**
```json
{
  "disease_name": "Type 2 Diabetes",
  "top_n": 10
}
```

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `disease_name` | string | Yes | - | Name of the disease to search for |
| `top_n` | integer | No | 10 | Number of top targets (1-100) |

**Response:**
```json
{
  "disease": "Type 2 Diabetes",
  "total_targets": 10,
  "targets": [
    {
      "symbol": "INSR",
      "name": "Insulin Receptor",
      "score": 0.8532
    },
    {
      "symbol": "GCK",
      "name": "Glucokinase",
      "score": 0.7891
    }
  ]
}
```

**Status Codes:**
- `200` - Success
- `404` - Disease not found
- `500` - Server error

**Example:**
```bash
curl -X POST http://localhost:8000/api/v1/disease-targets \
  -H "Content-Type: application/json" \
  -d '{
    "disease_name": "Type 2 Diabetes",
    "top_n": 10
  }'
```

---

### 4. Get Protein Sequences
```
POST /api/v1/protein-sequences
```

**Description:** Retrieve amino acid sequences from UniProt for identified proteins

**Request Body:**
```json
[
  {
    "symbol": "INSR",
    "name": "Insulin Receptor",
    "score": 0.85
  }
]
```

**Response:**
```json
{
  "total_requested": 1,
  "total_found": 1,
  "targets": [
    {
      "symbol": "INSR",
      "name": "Insulin Receptor",
      "score": 0.85,
      "sequence": "MAAEEEEEEGELEVLGKGGGSTLSACLVCDSNGLLN..."
    }
  ]
}
```

**Status Codes:**
- `200` - Success
- `500` - Server error

**Example:**
```bash
curl -X POST http://localhost:8000/api/v1/protein-sequences \
  -H "Content-Type: application/json" \
  -d '[{"symbol": "INSR"}]'
```

---

### 5. Get Drug Library
```
GET /api/v1/drug-library
```

**Description:** Load FDA-approved drugs with SMILES strings

**Query Parameters:** None

**Response:**
```json
{
  "total_drugs": 100,
  "drugs": [
    {
      "name": "Drug_001",
      "smiles": "CC(=O)Oc1ccccc1C(=O)O",
      "drug_id": "1"
    },
    {
      "name": "Drug_002",
      "smiles": "CN1C=NC2=C1C(=O)N(C(=O)N2C)C",
      "drug_id": "2"
    }
  ]
}
```

**Status Codes:**
- `200` - Success
- `500` - Server error

**Example:**
```bash
curl http://localhost:8000/api/v1/drug-library
```

---

### 6. Virtual Drug Screening (Main Pipeline)
```
POST /api/v1/screen
```

**Description:** Run the complete drug repurposing pipeline end-to-end

This is the main endpoint that orchestrates all pipeline stages:
1. Disease target identification
2. Protein sequence retrieval
3. Drug library loading
4. AI-based virtual screening
5. Result processing and ranking

**Request Body:**
```json
{
  "disease_name": "Type 2 Diabetes",
  "min_score": 0.5,
  "top_n_targets": 10,
  "known_drugs": ["Metformin", "Insulin", "Sitagliptin"]
}
```

**Parameters:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `disease_name` | string | Yes | - | Name of the disease |
| `min_score` | number | No | 0.0 | Minimum binding affinity score (0.0-1.0) |
| `top_n_targets` | integer | No | 10 | Number of disease targets to use (1-50) |
| `known_drugs` | array | No | ["Metformin"] | List of known drugs for filtering |

**Response:**
```json
{
  "disease": "Type 2 Diabetes",
  "total_targets": 10,
  "total_drugs": 100,
  "total_predictions": 1000,
  "top_results": [
    {
      "drug_name": "Drug_085",
      "target_symbol": "INSR",
      "score": 0.9234,
      "status": "🆕 Potential Discovery"
    },
    {
      "drug_name": "Drug_042",
      "target_symbol": "GCK",
      "score": 0.8912,
      "status": "🆕 Potential Discovery"
    },
    {
      "drug_name": "Drug_001",
      "target_symbol": "INSR",
      "score": 0.8756,
      "status": "✅ Known Treatment"
    }
  ],
  "success": true,
  "message": "Screening completed in 45.32s. Found 892 candidates."
}
```

**Response Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `disease` | string | Disease name searched |
| `total_targets` | integer | Number of targets identified |
| `total_drugs` | integer | Number of drugs screened |
| `total_predictions` | integer | Total drug-target pairs predicted |
| `top_results` | array | Top 10 prediction results |
| `success` | boolean | Whether screening completed successfully |
| `message` | string | Summary message with timing |

**Top Results Fields:**
| Field | Type | Description |
|-------|------|-------------|
| `drug_name` | string | Drug identifier |
| `target_symbol` | string | Protein target symbol |
| `score` | number | Binding affinity score (0.0-1.0, higher = better) |
| `status` | string | "✅ Known Treatment" or "🆕 Potential Discovery" |

**Status Codes:**
- `200` - Success
- `400` - Bad request (invalid disease, no targets found)
- `404` - Disease not found
- `500` - Server error

**Execution Time:** 30-120 seconds (varies based on targets, drugs, model)

**Example:**
```bash
curl -X POST http://localhost:8000/api/v1/screen \
  -H "Content-Type: application/json" \
  -d '{
    "disease_name": "Type 2 Diabetes",
    "min_score": 0.5,
    "top_n_targets": 10,
    "known_drugs": ["Metformin", "Insulin", "Sitagliptin"]
  }'
```

**Python Example:**
```python
import requests

url = "http://localhost:8000/api/v1/screen"
payload = {
    "disease_name": "Type 2 Diabetes",
    "min_score": 0.5,
    "top_n_targets": 10,
    "known_drugs": ["Metformin"]
}

response = requests.post(url, json=payload)
results = response.json()

print(f"Found {len(results['top_results'])} top candidates")
for result in results['top_results']:
    print(f"{result['drug_name']}: {result['score']}")
```

---

## 📊 Data Models

### DiseaseSearchRequest
```json
{
  "disease_name": "string",
  "top_n": "integer (1-100)"
}
```

### TargetInfo
```json
{
  "symbol": "string",
  "name": "string (optional)",
  "score": "number",
  "sequence": "string (optional)"
}
```

### DrugInfo
```json
{
  "name": "string",
  "smiles": "string",
  "drug_id": "string (optional)"
}
```

### PredictionResult
```json
{
  "drug_name": "string",
  "target_symbol": "string",
  "score": "number (0-1)",
  "status": "string (optional)"
}
```

### ScreeningRequest
```json
{
  "disease_name": "string",
  "min_score": "number (0-1)",
  "top_n_targets": "integer (1-50)",
  "known_drugs": "array of strings"
}
```

### ScreeningResponse
```json
{
  "disease": "string",
  "total_targets": "integer",
  "total_drugs": "integer",
  "total_predictions": "integer",
  "top_results": "array of PredictionResult",
  "success": "boolean",
  "message": "string"
}
```

---

## ⚠️ Error Responses

All errors follow this format:

```json
{
  "detail": "Error message description",
  "status_code": 400
}
```

**Common Errors:**

### 404 Not Found
```json
{
  "detail": "No targets found for disease: Unknown Disease",
  "status_code": 404
}
```

### 400 Bad Request
```json
{
  "detail": "Could not fetch sequences for any targets",
  "status_code": 400
}
```

### 500 Server Error
```json
{
  "detail": "Screening failed: Connection error to API",
  "status_code": 500
}
```

---

## 🔄 Request/Response Examples

### Complete Screening Workflow

**Step 1: Check Health**
```bash
curl http://localhost:8000/health
```

**Step 2: Search Disease Targets**
```bash
curl -X POST http://localhost:8000/api/v1/disease-targets \
  -H "Content-Type: application/json" \
  -d '{"disease_name": "Type 2 Diabetes", "top_n": 5}'
```

**Step 3: Run Full Screening**
```bash
curl -X POST http://localhost:8000/api/v1/screen \
  -H "Content-Type: application/json" \
  -d '{
    "disease_name": "Type 2 Diabetes",
    "min_score": 0.6,
    "top_n_targets": 5,
    "known_drugs": ["Metformin"]
  }'
```

---

## 🎯 Scoring Interpretation

**Binding Affinity Scores (0.0 - 1.0):**

| Score Range | Interpretation |
|-------------|-----------------|
| 0.0 - 0.3 | Weak binding (unlikely) |
| 0.3 - 0.6 | Moderate binding (possible) |
| 0.6 - 0.8 | Good binding (promising) |
| 0.8 - 1.0 | Excellent binding (strong candidate) |

---

## 🚀 Performance Tips

1. **Limit targets** - Use `top_n_targets` ≤ 10 for faster results
2. **Set score threshold** - Use `min_score` ≥ 0.5 to filter noise
3. **Use mock mode** - For testing without DeepPurpose
4. **Parallel requests** - API supports multiple concurrent requests

---

## 📝 Testing with cURL

### Basic Health Check
```bash
curl -v http://localhost:8000/health
```

### With Response Headers
```bash
curl -i http://localhost:8000/health
```

### With Formatted JSON Output
```bash
curl -X POST http://localhost:8000/api/v1/screen \
  -H "Content-Type: application/json" \
  -d @request.json | python -m json.tool
```

### Measure Response Time
```bash
curl -w "Time: %{time_total}s\n" http://localhost:8000/health
```

---

## 📚 Integration Examples

### Python
```python
import requests
import json

base_url = "http://localhost:8000"

# Health check
response = requests.get(f"{base_url}/health")
print(response.json())

# Run screening
screening_data = {
    "disease_name": "Type 2 Diabetes",
    "min_score": 0.6,
    "known_drugs": ["Metformin"]
}
response = requests.post(f"{base_url}/api/v1/screen", json=screening_data)
results = response.json()
```

### JavaScript/Node.js
```javascript
const axios = require('axios');

const baseUrl = 'http://localhost:8000';

// Health check
axios.get(`${baseUrl}/health`)
  .then(res => console.log(res.data));

// Run screening
const screeningData = {
  disease_name: 'Type 2 Diabetes',
  min_score: 0.6,
  known_drugs: ['Metformin']
};

axios.post(`${baseUrl}/api/v1/screen`, screeningData)
  .then(res => console.log(res.data));
```

---

## 🔐 Security Considerations

- All inputs are validated using Pydantic
- API implements CORS for safe cross-origin requests
- Timeouts protect against hanging requests
- No sensitive data is logged
- Consider adding API key authentication for production

---

## 📞 Support

For API issues:
1. Check the `/docs` endpoint for interactive testing
2. Review logs from the running service
3. Verify all external APIs are accessible
4. Check Docker health status: `docker ps`

---

**Last Updated:** 2024  
**Version:** 1.0.0
