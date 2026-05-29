
content = '''# 📡 AILIXIR API Reference

**Version:** 2.1 | **Last Updated:** May 29, 2026 | **Status:** Production Ready

Complete API specification for AILIXIR-Backend, including all endpoints, authentication, request/response schemas, error handling, and integration examples.

---

## 📋 Table of Contents

- [Overview](#overview)
- [Authentication](#authentication)
- [Base URLs](#base-urls)
- [Common Patterns](#common-patterns)
- [Health & Status](#health--status)
- [Authentication Endpoints](#authentication-endpoints)
- [AI Integration Endpoints](#ai-integration-endpoints)
- [AI Service Endpoints](#ai-service-endpoints)
- [AI Agent Endpoints](#ai-agent-endpoints)
- [Chemical Search Endpoints](#chemical-search-endpoints)
- [Docking API](#docking-api)
- [Convert SMILES API](#convert-smiles-api)
- [User Management Endpoints](#user-management-endpoints)
- [Awards & Scientists Endpoints](#awards--scientists-endpoints)
- [News Endpoints](#news-endpoints)
- [Error Handling](#error-handling)
- [Rate Limiting](#rate-limiting)
- [Integration Examples](#integration-examples)

---

## 🔍 Overview

AILIXIR provides a RESTful API for accessing all drug discovery services. The API is built with:

- **Framework:** Laravel 11 with Sanctum authentication
- **Response Format:** JSON
- **Authentication:** JWT Bearer tokens
- **Rate Limiting:** In place (contact admin for limits)
- **Versioning:** URL-based (`/api/v1/...`)

**Key Features:**
- Asynchronous job processing (long-running AI tasks)
- Real-time health monitoring
- User authentication and authorization
- Result caching and versioning
- File upload/download support
- AI-powered chemistry analysis agent with conversation memory

---

## 🔐 Authentication

### Overview

AILIXIR uses **Laravel Sanctum** for API authentication. Most endpoints require a valid JWT bearer token.

### Types of Tokens

| Token Type | Purpose | Duration |
|-----------|---------|----------|
| **Access Token** | Query user data, submit jobs | Session-based |
| **API Token** | Long-lived access (optional) | 365 days |
| **Refresh Token** | Renew access after expiry | 14 days |

### Authentication Header

All authenticated requests must include:

```http
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### Token Generation

Tokens are issued after successful login or registration. Store securely (never in localStorage for sensitive apps).

---

## 🌐 Base URLs

### Production

```
https://ailixir.pharmaai.io/api
```

### Development (Local)

```
http://localhost:8080/api
```

### Service-Specific Discovery

Each microservice exposes its own API discovery endpoint:

```
http://localhost:8002/docs      # ADMET Service (FastAPI)
http://localhost:8001/docs      # Drug Repurposing (FastAPI)
http://localhost:5000/docs      # Chemical RAG (FastAPI)
http://localhost:8080/docs      # Laravel API (Swagger)
```

---

## 🔄 Common Patterns

### Pagination

List endpoints support pagination:

```query
?page=1&per_page=20
```

**Response Field:** `meta.pagination`

```json
{
  "data": [...],
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 100,
      "last_page": 5
    }
  }
}
```

### Filtering

Endpoints support filtering (details per endpoint):

```query
?filter[status]=completed&filter[created_after]=2026-05-01
```

### Sorting

```query
?sort=created_at&sort_order=desc
```

### Asynchronous Jobs

Long-running operations return `202 Accepted` with job tracking URL:

```json
{
  "success": true,
  "job_id": "abc-123-def",
  "status": "pending",
  "check_url": "/api/ai/status/abc-123-def"
}
```

**Check Status:**
```
GET /api/ai/status/{job_id}
```

---

## ❤️ Health & Status

### System Health

Check overall system status:

```http
GET /health
```

**Response (200 OK):**
```json
{
  "status": "healthy",
  "timestamp": "2026-05-29T12:30:45Z",
  "version": "2.0.0",
  "uptime_seconds": 876543
}
```

### All Services Health

Check all AI microservices:

```http
GET /ai-services/health
```

**Response (200 OK):**
```json
{
  "success": true,
  "services": {
    "admet": {
      "status": "healthy",
      "http_status": 200,
      "body": {
        "status": "ready",
        "model_version": "MPNN_CNN_BindingDB"
      }
    },
    "drug_repurposing": {
      "status": "healthy",
      "http_status": 200,
      "body": {
        "status": "ready",
        "model": "DeepPurpose v0.1.5"
      }
    },
    "chemical_rag": {
      "status": "healthy",
      "http_status": 200,
      "body": {
        "status": "ready",
        "index_size": 1000000
      }
    }
  }
}
```

**Response (503 Service Unavailable):** One or more services is down

---

## 👤 Authentication Endpoints

### Register User

```http
POST /user/register
Content-Type: application/json

{
  "name": "John Researcher",
  "email": "john@pharmaai.io",
  "password": "secure_password_123",
  "password_confirmation": "secure_password_123"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Registration successful. Please verify your email.",
  "user": {
    "id": 1,
    "name": "John Researcher",
    "email": "john@pharmaai.io",
    "email_verified_at": null
  },
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

### Login

```http
POST /user/login
Content-Type: application/json

{
  "email": "john@pharmaai.io",
  "password": "secure_password_123"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "John Researcher",
    "email": "john@pharmaai.io",
    "email_verified_at": "2026-05-29T10:00:00Z"
  },
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

### Verify Email

After account creation, verify email to activate account:

```http
POST /user/verify-email
Content-Type: application/json

{
  "email": "john@pharmaai.io",
  "otp": "123456"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Email verified successfully"
}
```

### Resend Verification Email

```http
POST /user/resend-verification
Content-Type: application/json

{
  "email": "john@pharmaai.io"
}
```

### Forgot Password

```http
POST /user/forgot-password
Content-Type: application/json

{
  "email": "john@pharmaai.io"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Password reset OTP sent to email"
}
```

### Reset Password

```http
POST /user/reset-password
Content-Type: application/json

{
  "email": "john@pharmaai.io",
  "otp": "123456",
  "password": "new_secure_password",
  "password_confirmation": "new_secure_password"
}
```

### Google OAuth

```http
POST /user/auth/google
Content-Type: application/json

{
  "token": "google_id_token_jwt"
}
```

### Logout

```http
POST /user/logout
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## 🤖 AI Integration Endpoints

These endpoints are for testing and monitoring AI microservices. Available only if `AI_INTEGRATION_ROUTES_ENABLED=true`.

### Test ADMET Prediction

Quick test of ADMET service:

```http
POST /ai-services/test/admet
Content-Type: application/json

{
  "smiles": "CC(=O)Oc1ccccc1C(=O)O",
  "batch_size": 32
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "service": "admet",
  "upstream_status": 200,
  "data": {
    "status": "success",
    "predictions": {
      "absorption": 0.78,
      "distribution": 0.65,
      "metabolism": 0.45,
      "excretion": 0.82,
      "toxicity": 0.12
    },
    "processing_time_ms": 234
  }
}
```

### Test Chemical Search

Quick test of Chemical RAG service:

```http
POST /ai-services/test/chemical-search
Content-Type: application/json

{
  "smiles": "CC(=O)Oc1ccccc1C(=O)O",
  "top_k": 5
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "service": "chemical_rag",
  "upstream_status": 200,
  "data": {
    "query": {
      "smiles": "CC(=O)Oc1ccccc1C(=O)O"
    },
    "results": [
      {
        "rank": 1,
        "cid": 2244,
        "name": "Aspirin",
        "smiles": "CC(=O)Oc1ccccc1C(=O)O",
        "similarity": 1.0,
        "image_url": "https://..."
      }
    ],
    "processing_time_ms": 145
  }
}
```

### Test Drug Repurposing

Quick test of Drug Repurposing service:

```http
GET /ai-services/test/drug-repurposing
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```json
{
  "success": true,
  "service": "drug_repurposing",
  "health": {
    "status": 200,
    "body": {
      "status": "healthy",
      "model": "DeepPurpose",
      "version": "0.1.5"
    }
  },
  "model_status": {
    "status": 200,
    "body": {
      "available_models": [
        "MPNN_CNN_BindingDB"
      ]
    }
  }
}
```

---

## 🧬 AI Service Endpoints

### Submit AI Job

Submit a drug discovery analysis job:

```http
POST /ai/run
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json

{
  "job_type": "admet_prediction",
  "parameters": {
    "smiles": "CC(=O)Oc1ccccc1C(=O)O",
    "batch_size": 32
  },
  "notification_email": "john@pharmaai.io"
}
```

**Response (202 Accepted):**
```json
{
  "success": true,
  "job_id": "job-abc-123-def",
  "status": "pending",
  "created_at": "2026-05-29T12:30:45Z",
  "check_url": "/api/ai/status/job-abc-123-def"
}
```

### Get Job Status

```http
GET /ai/status/{job_id}
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK - Pending):**
```json
{
  "success": true,
  "job_id": "job-abc-123-def",
  "status": "processing",
  "progress": 45,
  "estimated_completion": "2026-05-29T12:35:00Z"
}
```

**Response (200 OK - Completed):**
```json
{
  "success": true,
  "job_id": "job-abc-123-def",
  "status": "completed",
  "results": {
    "smiles": "CC(=O)Oc1ccccc1C(=O)O",
    "predictions": {
      "absorption": 0.78,
      "distribution": 0.65,
      "metabolism": 0.45,
      "excretion": 0.82,
      "toxicity": 0.12
    }
  },
  "completed_at": "2026-05-29T12:32:15Z"
}
```

**Response (200 OK - Failed):**
```json
{
  "success": false,
  "job_id": "job-abc-123-def",
  "status": "failed",
  "error": "Invalid SMILES string",
  "failed_at": "2026-05-29T12:30:50Z"
}
```

### Preview Results

Get a preview of results while job is processing:

```http
GET /ai/preview/{job_id}
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```json
{
  "success": true,
  "job_id": "job-abc-123-def",
  "status": "processing",
  "preview": {
    "processed_compounds": 50,
    "total_compounds": 100,
    "average_prediction_time_ms": 234
  }
}
```

### Download Results (Top)

Download first N results (useful for large datasets):

```http
GET /ai/download/top/{job_id}?limit=100
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```
Content-Type: text/csv
Content-Disposition: attachment; filename="results-top-100.csv"

smiles,absorption,distribution,metabolism,excretion,toxicity
CC(=O)Oc1ccccc1C(=O)O,0.78,0.65,0.45,0.82,0.12
...
```

### Download Full Results

Download complete results as CSV:

```http
GET /ai/download/full/{job_id}
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```
Content-Type: application/zip
Content-Disposition: attachment; filename="results-full.zip"

# Contains: predictions.csv, metadata.json, summary.txt
```

### Job History

Get all jobs submitted by user:

```http
GET /ai/history?page=1&per_page=20&filter[status]=completed&sort=created_at&sort_order=desc
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": "job-abc-123-def",
      "job_type": "admet_prediction",
      "status": "completed",
      "created_at": "2026-05-29T12:00:00Z",
      "completed_at": "2026-05-29T12:30:00Z",
      "result_summary": {
        "total_compounds": 100,
        "average_absorption": 0.72
      }
    }
  ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 42,
      "last_page": 3
    }
  }
}
```

---

## 🤖 AI Agent Endpoints (Chemistry AI)

AI-powered chemistry analysis agent with conversation memory. All endpoints require authentication.

**Base Path:** `/api/chemistry`

**Capabilities:**
- SMILES validation and property calculation
- Drug-likeness classification (Lipinski Ro5, Veber, Lead-likeness)
- ADMET profiling with structural toxicity alerts
- Molecular docking result ranking and recommendation
- Multi-molecule side-by-side comparison
- Async CSV batch processing
- Conversation context via thread_id

---

### Create Conversation Thread

Start a new isolated chemistry conversation thread.

```http
POST /api/chemistry/thread
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "thread_id": "aa761d2c-ecbc-4da0-abe4-189c2d77eede",
    "id": 1,
    "created_at": "2026-05-29T12:00:00Z"
  }
}
```

**Notes:**
- One thread_id per user session
- Do not share thread IDs across different users
- Pass the same thread_id across multiple calls to maintain conversation history

---

### List User Threads

Get all active threads for the authenticated user.

```http
GET /api/chemistry/threads
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "thread_id": "aa761d2c-ecbc-4da0-abe4-189c2d77eede",
      "title": "New Conversation",
      "last_used_at": "2026-05-29T12:30:00Z",
      "created_at": "2026-05-29T12:00:00Z"
    }
  ]
}
```

---

### Send Chat Message

Send a natural language chemistry question to the AI agent.

```http
POST /api/chemistry/chat
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json

{
  "message": "Is CC(=O)Oc1ccccc1C(=O)O a good drug candidate?",
  "thread_id": "aa761d2c-ecbc-4da0-abe4-189c2d77eede"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "reply": "Based on the analysis of CC(=O)Oc1ccccc1C(=O)O (Aspirin)...",
    "thread_id": "aa761d2c-ecbc-4da0-abe4-189c2d77eede",
    "processing_time_ms": 11888
  }
}
```

**Example Messages:**
- `"Is CC(=O)Oc1ccccc1C(=O)O a good drug candidate?"`
- `"Compare aspirin and ibuprofen CC(C)Cc1ccc(cc1)C(C)C(=O)O"`
- `"What are the toxicity concerns for this molecule?"` (follow-up)
- `"Which molecule we discussed has the best CNS penetration?"` (follow-up)

**Notes:**
- `thread_id` is optional. Omit to start a fresh conversation automatically.
- The agent remembers all prior messages on the same thread.
- The agent automatically handles: SMILES validation, molecular property calculation, drug-likeness rules, ADMET profiling, docking ranking, and multi-molecule comparison.

---

### Analyze Single SMILES

Full molecular analysis pipeline in one call.

```http
POST /api/chemistry/analyze/smiles
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json

{
  "smiles": "CC(=O)Oc1ccccc1C(=O)O",
  "thread_id": "aa761d2c-ecbc-4da0-abe4-189c2d77eede"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "reply": "Complete analysis text...",
    "thread_id": "aa761d2c-ecbc-4da0-abe4-189c2d77eede",
    "processing_time_ms": 5234
  }
}
```

**Pipeline Steps:**
1. Validate SMILES
2. Compute molecular properties (MW, LogP, HBD, HBA, TPSA, QED, Fsp3)
3. Drug-likeness classification (Lipinski Ro5, Veber, Lead-likeness)
4. ADMET profile with structural toxicity alerts

**Notes:**
- `smiles` field is required in JSON body
- URL-encode special characters if sending via query string: `CC%28%3DO%29Oc1ccccc1C%28%3DO%29O`

---

### Compare Multiple Molecules

Side-by-side comparison of 2 or more molecules with recommendation.

```http
POST /api/chemistry/analyze/compare
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json

{
  "smiles": [
    "CC(=O)Oc1ccccc1C(=O)O",
    "CC(C)Cc1ccc(cc1)C(C)C(=O)O",
    "Cn1cnc2c1c(=O)n(c(=O)n2C)C"
  ],
  "thread_id": "aa761d2c-ecbc-4da0-abe4-189c2d77eede"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "reply": "Side-by-side property table and recommendation...",
    "thread_id": "aa761d2c-ecbc-4da0-abe4-189c2d77eede",
    "processing_time_ms": 23543
  }
}
```

**Agent Returns:**
- Side-by-side property table (MW, LogP, HBD, HBA, TPSA, QED, Fsp3)
- Drug-likeness pass/fail for each molecule
- Named recommendation with justification

---

### Analyze Docking Results

Rank and recommend best docking candidates.

```http
POST /api/chemistry/analyze/docking
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json

{
  "docking_data": "CC(=O)Oc1ccccc1C(=O)O | -7.2 | 1.1 | H-bond to Ser195\\nCC(C)Cc1ccc(cc1)C(C)C(=O)O | -8.9 | 0.8 | deep pocket binding\\nCn1cnc2c1c(=O)n(c(=O)n2C)C | -6.1 | 1.9 |",
  "thread_id": "aa761d2c-ecbc-4da0-abe4-189c2d77eede"
}
```

**Docking Data Format:**
```
SMILES | binding_affinity_kcal_mol | rmsd_angstrom | optional_notes
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "reply": "Top Pick: CC(C)Cc1ccc(cc1)C(C)C(=O)O...",
    "thread_id": "aa761d2c-ecbc-4da0-abe4-189c2d77eede",
    "processing_time_ms": 5392
  }
}
```

**Interpretation Rules:**
- ΔG more negative = stronger binding
- RMSD < 2 Å = reliable pose; > 2 Å = uncertain binding mode
- Best candidate = strongest binder that also passes Lipinski Ro5

---

### Upload CSV for Batch Analysis

Process multiple molecules asynchronously.

```http
POST /api/chemistry/csv/upload
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: multipart/form-data

file: [Choose File] molecules.csv
analysis_type: full
```

**Analysis Types:**

| Type | What It Runs | Speed |
|------|-------------|-------|
| `full` | Properties + drug-likeness + ADMET | Slow |
| `quick` | Lipinski pass/fail + QED only | Fast |
| `admet` | ADMET profile only | Medium |
| `classify` | Drug-likeness classification only | Fast |

**Required CSV Columns:**
- `smiles` — SMILES string (required)
- `name` — compound name or ID (optional, auto-generated if missing)

**Example CSV:**
```csv
name,smiles
Aspirin,CC(=O)Oc1ccccc1C(=O)O
Ibuprofen,CC(C)Cc1ccc(cc1)C(C)C(=O)O
Caffeine,Cn1cnc2c1c(=O)n(c(=O)n2C)C
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "job_id": "0ec86cee-6ce8-4e91-95f4-ad807f7ee283",
    "id": 1,
    "status": "queued",
    "total_rows": 4
  }
}
```

**Limits:** Maximum 100 rows per upload. Split larger datasets into batches.

---

### Check CSV Job Status

Poll for processing progress.

```http
GET /api/chemistry/csv/status/{job_id}
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK - Running):**
```json
{
  "success": true,
  "data": {
    "job_id": "0ec86cee-6ce8-4e91-95f4-ad807f7ee283",
    "status": "running",
    "total": 4,
    "completed": 3,
    "failed_rows": 2,
    "progress_percent": 75
  }
}
```

**Status Values:**
| Value | Meaning |
|-------|---------|
| `queued` | Job is waiting to start |
| `running` | Actively processing rows |
| `done` | All rows finished — results are ready |
| `failed` | Job-level error (row errors are inside results) |

**Polling:** Poll every 5–10 seconds. Once status == "done", call GET /csv/results/{job_id}.

---

### Download CSV Results

Get completed analysis as CSV file download.

```http
GET /api/chemistry/csv/results/{job_id}
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```
Content-Type: text/csv
Content-Disposition: attachment; filename="results_{job_id}.csv"

row,name,smiles,status,error,analysis
1,Aspirin,CC(=O)Oc1ccccc1C(=O)O,success,,"Full analysis text..."
2,Ibuprofen,CC(C)Cc1ccc(cc1)C(C)C(=O)O,failed,"Quota exceeded",...
```

**Output CSV Columns:**
- `row` — original row number in the uploaded file
- `name` — compound name
- `smiles` — input SMILES string
- `status` — success or failed
- `analysis` — full agent analysis text
- `error` — error message (empty if status is success)

---

### List User CSV Jobs

```http
GET /api/chemistry/csv/jobs
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "job_id": "0ec86cee-6ce8-4e91-95f4-ad807f7ee283",
      "filename": "molecules.csv",
      "analysis_type": "full",
      "status": "done",
      "total_rows": 4,
      "completed_rows": 4,
      "failed_rows": 3,
      "progress_percent": 100,
      "created_at": "2026-05-29T12:00:00Z"
    }
  ]
}
```

---

### Delete CSV Job

Remove completed job and free storage.

```http
DELETE /api/chemistry/csv/jobs/{job_id}
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Job deleted successfully"
}
```

**Notes:**
- Call this after successfully downloading results to keep memory usage low
- Trying to delete a non-existent job returns 404

---

### Get User Analysis History

Retrieve all past analyses with pagination.

```http
GET /api/chemistry/history?type=smiles
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `type` | string | Filter: `smiles`, `compare`, `docking`, `chat` |
| `page` | integer | Page number |

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "type": "smiles",
        "input_data": "CC(=O)Oc1ccccc1C(=O)O",
        "response": "Analysis text...",
        "status": "success",
        "created_at": "2026-05-29T12:00:00Z",
        "thread": {
          "id": 1,
          "thread_id": "aa761d2c-ecbc-4da0-abe4-189c2d77eede",
          "title": "New Conversation"
        }
      }
    ],
    "per_page": 20,
    "total": 42
  }
}
```

---

## 🔍 Chemical Search Endpoints

### Retrieval-Only Search

Query the chemical database for similar compounds (no explanations):

```http
POST /chemical-search
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json

{
  "smiles": "CC(=O)Oc1ccccc1C(=O)O",
  "top_k": 10
}
```

**Response (202 Accepted):**
```json
{
  "success": true,
  "job_id": "chem-search-abc-123",
  "status": "pending",
  "type": "retrieval_only",
  "check_url": "/api/chemical-search/chem-search-abc-123/status"
}
```

### Full RAG Search

Query with LLM-generated explanations:

```http
POST /chemical-search/full-rag
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json

{
  "smiles": "CC(=O)Oc1ccccc1C(=O)O",
  "top_k": 5
}
```

**Response (202 Accepted):**
```json
{
  "success": true,
  "job_id": "chem-rag-xyz-789",
  "status": "pending",
  "type": "full_rag",
  "check_url": "/api/chemical-search/chem-rag-xyz-789/status"
}
```

### Get Search Results

```http
GET /chemical-search/{job_id}/status
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK - Processing):**
```json
{
  "success": true,
  "job_id": "chem-search-abc-123",
  "status": "processing"
}
```

**Response (200 OK - Completed):**
```json
{
  "success": true,
  "job_id": "chem-search-abc-123",
  "status": "completed",
  "query": {
    "smiles": "CC(=O)Oc1ccccc1C(=O)O",
    "top_k": 10
  },
  "compounds": [
    {
      "rank": 1,
      "smiles": "CC(=O)Oc1ccccc1C(=O)O",
      "name": "Aspirin",
      "cid": 2244,
      "similarity": 1.0,
      "explanation": "This compound is aspirin itself, a salicylic acid acetyl ester...",
      "image_url": "https://cdn.example.com/compounds/aspirin.png"
    },
    {
      "rank": 2,
      "smiles": "CC(=O)Oc1ccccc1O",
      "name": "2-Acetoxybenzoic acid phenyl ester",
      "cid": 1234,
      "similarity": 0.95,
      "explanation": "Similar structural motif with acetyl group...",
      "image_url": "https://cdn.example.com/compounds/1234.png"
    }
  ],
  "metadata": {
    "completed_at": "2026-05-29T12:32:15Z",
    "search_time_ms": 145
  }
}
```

### Get Compound Images

```http
GET /chemical-search/{job_id}/images
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```json
{
  "success": true,
  "job_id": "chem-search-abc-123",
  "image_urls": [
    "https://cdn.example.com/compounds/aspirin.png",
    "https://cdn.example.com/compounds/1234.png"
  ],
  "total_images": 2
}
```

---

## Docking API

### POST `/api/docking/submit`

- Auth required
- Content type: `multipart/form-data`
- Required fields:
  - `protein_name` (string)
  - `protein_file` (file)
  - `center_x` (numeric)
  - `center_y` (numeric)
  - `center_z` (numeric)
  - `box_size_x` (numeric)
  - `box_size_y` (numeric)
  - `box_size_z` (numeric)
### VERY IMPORTANT  
### Optional fields:   
  - `ligand_name` (string)
  - `exhaustiveness` (integer)
  - `n_poses` (integer)
### Must include exactly one of:
  - `ligand_file` (file)
  - `ligand_smiles` (string) 

#### Example curl request

```bash
curl -X POST "{base_url}/api/docking/submit" \\
  -H "Authorization: Bearer {token}" \\
  -F "protein_name=EGFR" \\
  -F "protein_file=@protein.pdbqt" \\
  -F "ligand_name=Erlotinib" \\
  -F "ligand_smiles=CC1=CC(=O)NC2=C1C=CC=C2" \\
  -F "center_x=10.0" \\
  -F "center_y=15.0" \\
  -F "center_z=20.0" \\
  -F "box_size_x=25.0" \\
  -F "box_size_y=25.0" \\
  -F "box_size_z=25.0" \\
  -F "exhaustiveness=8" \\
  -F "n_poses=5"
```

#### Success response

```json
{
  "success": true,
  "message": "Docking Job Successfully Queued",
  "data": {
    "job_id": 123,
    "status": "pending"
  }
}
```


---

### GET `/api/docking/{id}`

- Auth required
- Path parameter:
  - `id` (integer)

#### Success response (pending or completed)

```json
{
  "success": true,
  "message": "Job details retrieved successfully",
  "data": {
    "job_id": 14,
    "status": "completed",
    "inputs": {
      "protein": "EGFR",
      "ligand": "Erlotinib"
    },
    "created_at": "2026-04-21T18:29:45+00:00",
    "results": {
      "vina_scores": [0, 0.001, 0.002],
      "download_url": "{base_url}/api/docking/download/{job_id}"
    }
  }
}
```

If the job is not completed, the `results` block may be omitted.


---

### GET `/api/docking/history`

- Auth required
- Query parameters:
  - `per_page` (integer, optional, default 15)

#### Response

```json
{
  "success": true,
  "message": "Docking history retrieved successfully",
  "data": {
    "data": [
      {
        "job_id": 14,
        "status": "completed",
        "inputs": {
          "protein": "EGFR",
          "ligand": "Erlotinib"
        },
        "created_at": "2026-04-21T18:29:45+00:00",
        "results": {
          "vina_scores": [0, 0.001, 0.002],
          "download_url": "{base_url}/api/docking/download/{job_id}"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 2,
      "total": 3,
      "last_page": 2,
      "has_more": true
    }
  }
}
```

---

### GET `/api/docking/download/{id}`

- Auth required
- Path parameter:
  - `id` (integer)
- Returns a file download for the completed docking result.
- Content disposition filename: `docking_result_{id}.pdbqt`


---

## Convert SMILES API

### GET `/api/convert-smiles/history`

- Auth required
- Query parameters:
  - `per_page` (integer, optional, default 15)

#### Response

```json
{
  "success": true,
  "message": "Conversion history retrieved successfully",
  "data": {
    "items": [
      {
        "job_id": 16,
        "status": "completed",
        "smiles": "CCN1CC(CCN2CCOCC2)C(c2ccccc2)(c2ccccc2)Cl=0",
        "created_at": "2026-04-21T18:43:58+00:00",
        "results": {
          "download_url": "{base_url}/api/convert-smiles/download/{job_id}"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 3,
      "total": 13,
      "last_page": 5,
      "has_more": true
    }
  }
}
```

---

### POST `/api/convert-smiles/convert`

- Auth required
- Content type: `application/json`
- Request body:
  - `ligand_smiles` (string, required)

#### Example curl request

```bash
curl -X POST "{base_url}/api/convert-smiles/convert" \\
  -H "Authorization: Bearer {token}" \\
  -H "Content-Type: application/json" \\
  -d '{"ligand_smiles":"CCN1CC(CCN2CCOCC2)C(c2ccccc2)(c2ccccc2)Cl=0"}'
```

#### Success response

```json
{
  "success": true,
  "message": "SMILES converted to PDBQT successfully",
  "data": {
    "job_id": 16,
    "download_url": "{base_url}/api/convert-smiles/download/{job_id}",
    "smiles": "CCN1CC(CCN2CCOCC2)C(c2ccccc2)(c2ccccc2)Cl=0"
  }
}
```


---

### GET `/api/convert-smiles/download/{id}`

- Auth required
- Path parameter:
  - `id` (integer)
- Returns a file download for the converted PDBQT.
- Content disposition filename: `converted_ligand_{id}.pdbqt`



---

## 👥 User Management Endpoints

### Get Profile

```http
GET /user/profile
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "John Researcher",
    "email": "john@pharmaai.io",
    "email_verified_at": "2026-05-29T10:00:00Z",
    "profile": {
      "institution": "MIT",
      "research_focus": "drug_discovery",
      "bio": "Computational chemist..."
    },
    "created_at": "2026-05-01T00:00:00Z"
  }
}
```

### Update Profile

```http
POST /user/update-profile
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json

{
  "name": "John Researcher, PhD",
  "profile": {
    "institution": "Stanford",
    "research_focus": "drug_discovery",
    "bio": "Senior researcher..."
  }
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "user": {...}
}
```

---


## 🏆 Awards & Scientists Endpoints

### List Awards

```http
GET /awards?page=1&per_page=10
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Awards retrieved successfully",
  "data": {
    "results": [
      {
        "id": 1,
        "name": "Nobel Prize in Physiology or Medicine",
        "category": "Medicine",
        "images": ["https://..."],
        "short_description": "The Nobel Prize in Physiology or Medicine is the world's most prestigious award...",
        "scientists_count": 9,
        "scientists": [
          {
            "id": 14,
            "name": "Alexander Fleming",
            "nationality": "British",
            "birth_year": null,
            "death_year": null,
            "field": "Microbiology",
            "images": ["https://..."],
            "bio": null,
            "short_bio": null,
            "impact": null
          }
        ]
      }
    ],
    "pagination": {
      "currentPage": 1,
      "totalPages": 1,
      "totalResults": 10,
      "perPage": 10,
      "hasNextPage": false,
      "hasPrevPage": false
    }
  }
}
```

---

### Get Award Details

```http
GET /awards/{award_id}
```

**Example:** `GET /awards/2`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Award retrieved successfully",
  "data": {
    "id": 2,
    "name": "Lasker Award",
    "category": "Medical Research",
    "images": ["https://..."],
    "short_description": "The Lasker Award is often called the 'American Nobel Prize'...",
    "scientists": [
      {
        "id": 17,
        "name": "Tu Youyou",
        "nationality": "Chinese",
        "birth_year": null,
        "death_year": null,
        "field": "Pharmaceutical Chemistry",
        "images": ["https://..."],
        "bio": null,
        "short_bio": null,
        "impact": null
      }
    ]
  }
}
```

---

### Get Award Scientists

```http
GET /awards/{award_id}/scientists
```

**Example:** `GET /awards/2/scientists`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Scientists retrieved successfully",
  "data": [
    {
      "id": 17,
      "name": "Tu Youyou",
      "nationality": "Chinese",
      "birth_year": null,
      "death_year": null,
      "field": "Pharmaceutical Chemistry",
      "images": ["https://..."],
      "bio": null,
      "short_bio": null,
      "impact": null
    }
  ]
}
```

---

### List Scientists

```http
GET /scientists?page=1&per_page=50
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Scientists retrieved successfully",
  "data": {
    "results": [
      {
        "id": 14,
        "name": "Alexander Fleming",
        "nationality": "British",
        "birth_year": null,
        "death_year": null,
        "field": "Microbiology",
        "images": ["https://..."],
        "bio": null,
        "short_bio": null,
        "impact": null
      }
    ],
    "pagination": {
      "currentPage": 1,
      "totalPages": 1,
      "totalResults": 24,
      "perPage": 50,
      "hasNextPage": false,
      "hasPrevPage": false
    }
  }
}
```

---

### Get Scientist Details

```http
GET /scientists/{scientist_id}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Scientist retrieved successfully",
  "data": {
    "id": 14,
    "name": "Alexander Fleming",
    "nationality": "British",
    "birth_year": null,
    "death_year": null,
    "field": "Microbiology",
    "images": ["https://..."],
    "bio": "Full biography text...",
    "short_bio": "Short biography...",
    "impact": "Scientific impact description...",
    "awards": [
      {
        "id": 1,
        "name": "Nobel Prize in Physiology or Medicine",
        "year": 1945
      }
    ]
  }
}
```

---

### Get Scientist Awards

```http
GET /scientists/{scientist_id}/awards
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Awards retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Nobel Prize in Physiology or Medicine",
      "category": "Medicine",
      "year": 1945,
      "images": ["https://..."]
    }
  ]
}
```

---

## 📰 News Endpoints

### Get News Feed

```http
GET /news?page=1&per_page=10
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Articles retrieved successfully",
  "data": {
    "results": [
      {
        "id": 13,
        "title": "AAPS National Biotechnology Conference",
        "summary": "11 May 2026 - 14 May 2026 - All Day Sheraton San Diego...",
        "source": "European Pharmaceutical Review",
        "url": "https://...",
        "published_at": "2026-05-11T07:00:00+00:00"
      }
    ],
    "pagination": {
      "currentPage": 1,
      "totalPages": 44,
      "totalResults": 434,
      "perPage": 10,
      "hasNextPage": true,
      "hasPrevPage": false
    }
  }
}
```

---

### Get News with Pagination

```http
GET /news?page=24&per_page=10
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Articles retrieved successfully",
  "data": {
    "results": [
      {
        "id": 21,
        "title": "EU kicks off one-year pilot to expedite multinational trials",
        "summary": "The European Union unveiled details of a pilot project...",
        "source": "Endpoints News",
        "url": "https://...",
        "published_at": "2026-01-23T19:24:15+00:00"
      }
    ],
    "pagination": {
      "currentPage": 24,
      "totalPages": 44,
      "totalResults": 434,
      "perPage": 10,
      "hasNextPage": true,
      "hasPrevPage": true
    }
  }
}
```

---

### Refresh News

```http
GET /news/refresh
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "News refreshed successfully",
  "data": {
    "new_articles": 5,
    "total_articles": 439
  }
}
```

---

### Get News Categories

```http
GET /news/categories
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Categories retrieved successfully",
  "data": [
    "chemistry",
    "pharma",
    "biotech",
    "medicine",
    "research",
    "clinical_trials"
  ]
}
```

---

### Save Article

```http
POST /news/{article_id}/save
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Article saved successfully",
  "data": {
    "saved_article_id": 123,
    "article_id": 13,
    "title": "AAPS National Biotechnology Conference",
    "saved_at": "2026-05-29T12:00:00Z"
  }
}
```

---

### Share Article

```http
POST /news/{article_id}/share
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json

{
  "share_with": "colleague@pharmaai.io",
  "message": "Check out this interesting article!"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Article shared successfully",
  "data": {
    "share_id": 456,
    "article_id": 13,
    "shared_with": "colleague@pharmaai.io",
    "shared_at": "2026-05-29T12:00:00Z"
  }
}
```

---

### Get Saved Articles

```http
GET /news/saved
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Saved articles retrieved successfully",
  "data": {
    "results": [
      {
        "id": 123,
        "article_id": 13,
        "title": "AAPS National Biotechnology Conference",
        "summary": "11 May 2026 - 14 May 2026...",
        "source": "European Pharmaceutical Review",
        "saved_at": "2026-05-29T12:00:00Z"
      }
    ],
    "pagination": {
      "currentPage": 1,
      "totalPages": 1,
      "totalResults": 5,
      "perPage": 10,
      "hasNextPage": false,
      "hasPrevPage": false
    }
  }
}
```

---

### Unsave Article

```http
DELETE /news/saved/{saved_article_id}
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Article unsaved successfully"
}
```
'''

## ❌ Error Handling

### Error Response Format

All errors follow this standard format:

```json
{
  "success": false,
  "message": "Human-readable error message",
  "error_code": "SPECIFIC_ERROR_CODE",
  "details": {
    "field_errors": {...}
  },
  "timestamp": "2026-05-29T12:30:45Z"
}
```

### HTTP Status Codes

| Code | Meaning | Example |
|------|---------|---------|
| **200** | OK | Request successful |
| **201** | Created | Resource created |
| **202** | Accepted | Async job queued |
| **400** | Bad Request | Invalid parameters |
| **401** | Unauthorized | Missing/invalid token |
| **403** | Forbidden | Insufficient permissions |
| **404** | Not Found | Resource not found |
| **422** | Unprocessable | Validation failed |
| **429** | Too Many Requests | Rate limit exceeded |
| **500** | Server Error | Internal error |
| **502** | Bad Gateway | Upstream service down |
| **503** | Service Unavailable | System maintenance |

### Common Error Codes

| Code | Description |
|------|-------------|
| `INVALID_SMILES` | Given SMILES string is invalid |
| `JOB_NOT_FOUND` | Job ID doesn't exist |
| `SERVICE_UNAVAILABLE` | AI service not responding |
| `AUTHENTICATION_FAILED` | Invalid credentials |
| `INSUFFICIENT_QUOTA` | User quota exceeded |
| `UNSUPPORTED_OPERATION` | Feature not available |
| `QUOTA_EXCEEDED` | LLM API daily quota reached |
| `THREAD_NOT_FOUND` | Thread ID doesn't exist or unauthorized |
| `JOB_NOT_COMPLETE` | Results requested before job completion |
| `CSV_TOO_LARGE` | Exceeds 100 row limit |
| `MISSING_SMILES` | smiles field not provided |

### Example Error Response

```http
POST /ai/run
Authorization: Bearer invalid_token

HTTP 401 Unauthorized
Content-Type: application/json

{
  "success": false,
  "message": "Unauthenticated",
  "error_code": "AUTHENTICATION_FAILED",
  "timestamp": "2026-05-29T12:30:45Z"
}
```

---

## 🚦 Rate Limiting

### Rate Limit Headers

All responses include rate limit information:

```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 987
X-RateLimit-Reset: 1716954645
```

### Limits by Endpoint Category

| Category | Limit | Window |
|----------|-------|--------|
| **Auth** | 10 requests | 15 minutes |
| **AI Jobs** | 100 requests | 1 hour |
| **Search** | 500 requests | 1 hour |
| **General** | 1000 requests | 1 hour |

### AI Agent Rate Limits

| Endpoint | Limit | Window |
|----------|-------|--------|
| `/chat` | 20 requests | Per day (upstream quota) |
| `/analyze/smiles` | 20 requests | Per day (upstream quota) |
| `/analyze/compare` | 20 requests | Per day (upstream quota) |
| `/analyze/docking` | 20 requests | Per day (upstream quota) |
| `/csv/upload` | 10 uploads | Per day |
| All others | 1000 requests | 1 hour |

**Note:** AI-powered endpoints are limited by upstream Google Gemini free tier (20 requests/day). Use `analysis_type: quick` to reduce consumption.

### Handling Rate Limits

When rate limited (HTTP 429):

```json
{
  "success": false,
  "message": "Rate limit exceeded",
  "retry_after": 120,
  "error_code": "RATE_LIMIT_EXCEEDED"
}
```

**Retry-After Header:** Seconds to wait before retry

---

## 💡 Integration Examples

### Example 1: Complete ADMET Prediction Workflow

```bash
#!/bin/bash

BASE_URL="http://localhost:8080/api"
TOKEN="your_access_token_here"
SMILES="CC(=O)Oc1ccccc1C(=O)O"

# Step 1: Submit job
echo "1. Submitting ADMET job..."
JOB_RESPONSE=$(curl -s -X POST "$BASE_URL/ai/run" \\
  -H "Authorization: Bearer $TOKEN" \\
  -H "Content-Type: application/json" \\
  -d "{\\"job_type\\": \\"admet_prediction\\", \\"parameters\\": {\\"smiles\\": \\"$SMILES\\"}}")

JOB_ID=$(echo $JOB_RESPONSE | jq -r '.job_id')
echo "Job ID: $JOB_ID"

# Step 2: Poll for completion
echo "2. Waiting for results..."
while true; do
  STATUS_RESPONSE=$(curl -s -X GET "$BASE_URL/ai/status/$JOB_ID" \\
    -H "Authorization: Bearer $TOKEN")
  
  STATUS=$(echo $STATUS_RESPONSE | jq -r '.status')
  
  if [ "$STATUS" == "completed" ]; then
    echo "Job completed!"
    echo $STATUS_RESPONSE | jq '.results'
    break
  elif [ "$STATUS" == "failed" ]; then
    echo "Job failed!"
    echo $STATUS_RESPONSE | jq '.error'
    exit 1
  else
    echo "Status: $STATUS, waiting..."
    sleep 5
  fi
done

# Step 3: Download results
echo "3. Downloading full results..."
curl -X GET "$BASE_URL/ai/download/full/$JOB_ID" \\
  -H "Authorization: Bearer $TOKEN" \\
  -o results.zip

echo "✓ Results saved to results.zip"
```

### Example 2: Chemical Similarity Search

```python
import requests
import json
import time

class AILIXIRClient:
    def __init__(self, base_url, token):
        self.base_url = base_url
        self.token = token
        self.headers = {
            "Authorization": f"Bearer {token}",
            "Content-Type": "application/json"
        }
    
    def search_similar_compounds(self, smiles, top_k=10, use_rag=True):
        """Search for compounds similar to query SMILES"""
        
        endpoint = "full-rag" if use_rag else "retrieval-only"
        url = f"{self.base_url}/chemical-search/{endpoint}"
        
        response = requests.post(url, headers=self.headers, json={
            "smiles": smiles,
            "top_k": top_k
        })
        
        if response.status_code != 202:
            raise Exception(f"Failed to submit search: {response.text}")
        
        job_id = response.json()["job_id"]
        print(f"Search submitted: {job_id}")
        
        # Poll for results
        while True:
            status_url = f"{self.base_url}/chemical-search/{job_id}/status"
            status_response = requests.get(status_url, headers=self.headers)
            status_data = status_response.json()
            
            if status_data["status"] == "completed":
                return status_data["compounds"]
            elif status_data["status"] == "failed":
                raise Exception(f"Search failed: {status_data.get('error')}")
            
            print(f"Status: {status_data['status']}, waiting...")
            time.sleep(2)

# Usage
client = AILIXIRClient("http://localhost:8080/api", "your_token_here")
results = client.search_similar_compounds("CC(=O)Oc1ccccc1C(=O)O", top_k=5)

for compound in results:
    print(f"Rank {compound['rank']}: {compound['name']} (similarity: {compound['similarity']:.2f})")
    if 'explanation' in compound:
        print(f"  Explanation: {compound['explanation'][:100]}...")
```

### Example 3: Health Monitoring

```javascript
// Node.js / JavaScript example
const axios = require('axios');

async function checkSystemHealth() {
  const baseURL = 'http://localhost:8080/api';
  
  try {
    // Check overall system
    const healthResponse = await axios.get(`${baseURL}/health`);
    console.log('System Health:', healthResponse.data);
    
    // Check all AI services
    const servicesResponse = await axios.get(`${baseURL}/ai-services/health`);
    
    const allHealthy = servicesResponse.data.success;
    const services = servicesResponse.data.services;
    
    console.log('\\nAI Services Status:');
    Object.entries(services).forEach(([name, status]) => {
      const indicator = status.status === 'healthy' ? '✓' : '✗';
      console.log(`${indicator} ${name}: ${status.status}`);
    });
    
    return {
      healthy: allHealthy,
      timestamp: new Date()
    };
  } catch (error) {
    console.error('Health check failed:', error.message);
  }
}

// Run every 60 seconds
setInterval(checkSystemHealth, 60000);
```

### Example 4: AI Agent Drug Discovery Workflow

```bash
#!/bin/bash

BASE_URL="http://localhost:8080/api"
TOKEN="your_access_token_here"

# Step 1: Create thread
echo "1. Creating thread..."
THREAD_RESPONSE=$(curl -s -X POST "$BASE_URL/chemistry/thread" \\
  -H "Authorization: Bearer $TOKEN")

THREAD_ID=$(echo $THREAD_RESPONSE | jq -r '.data.thread_id')
echo "Thread ID: $THREAD_ID"

# Step 2: Analyze SMILES
echo "2. Analyzing Aspirin..."
curl -s -X POST "$BASE_URL/chemistry/analyze/smiles" \\
  -H "Authorization: Bearer $TOKEN" \\
  -H "Content-Type: application/json" \\
  -d "{\\"smiles\\": \\"CC(=O)Oc1ccccc1C(=O)O\\", \\"thread_id\\": \\"$THREAD_ID\\"}" | jq '.data.reply'

# Step 3: Compare with Ibuprofen
echo "3. Comparing molecules..."
curl -s -X POST "$BASE_URL/chemistry/analyze/compare" \\
  -H "Authorization: Bearer $TOKEN" \\
  -H "Content-Type: application/json" \\
  -d "{\\"smiles\\": [\\"CC(=O)Oc1ccccc1C(=O)O\\", \\"CC(C)Cc1ccc(cc1)C(C)C(=O)O\\"], \\"thread_id\\": \\"$THREAD_ID\\"}" | jq '.data.reply'

# Step 4: Chat follow-up
echo "4. Follow-up question..."
curl -s -X POST "$BASE_URL/chemistry/chat" \\
  -H "Authorization: Bearer $TOKEN" \\
  -H "Content-Type: application/json" \\
  -d "{\\"message\\": \\"Which molecule has better CNS penetration?\\", \\"thread_id\\": \\"$THREAD_ID\\"}" | jq '.data.reply'

echo "✓ Workflow complete!"
```

### Example 5: Flutter Integration

```dart
class ChemistryApiService {
  final String baseUrl = 'http://your-domain.com/api/chemistry';
  String? token;

  Future<Map<String, dynamic>> analyzeSmiles(String smiles, {String? threadId}) async {
    final response = await http.post(
      Uri.parse('$baseUrl/analyze/smiles'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'smiles': smiles,
        'thread_id': threadId,
      }),
    );
    return jsonDecode(response.body);
  }

  Future<Map<String, dynamic>> chat(String message, {String? threadId}) async {
    final response = await http.post(
      Uri.parse('$baseUrl/chat'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'message': message,
        'thread_id': threadId,
      }),
    );
    return jsonDecode(response.body);
  }

  Future<Map<String, dynamic>> createThread() async {
    final response = await http.post(
      Uri.parse('$baseUrl/thread'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
    );
    return jsonDecode(response.body);
  }
}
```

---

## 📞 Support

For API issues or questions:

1. **Check Logs:** `docker compose logs -f laravel`
2. **Test Endpoint:** Use provided curl examples
3. **Version:** Confirm you're using latest API version
4. **Rate Limits:** Check `X-RateLimit-*` headers
5. **Contact:** pharma-support@ailixir.io

---

**Created:** May 29, 2026 | **Version:** 2.1.0 | **Last Updated:** May 29, 2026
'''

with open('/mnt/agents/output/AILIXIR_API_Reference_v2.1.md', 'w', encoding='utf-8') as f:
    f.write(content)

print("File saved successfully!")
print(f"Total lines: {len(content.splitlines())}")
print(f"Total characters: {len(content)}")
