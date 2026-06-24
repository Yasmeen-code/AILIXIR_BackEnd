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
- [AI Agent Endpoints (Chemistry AI)](#ai-agent-endpoints-chemistry-ai)
- [Chemical Search Endpoints](#chemical-search-endpoints)
- [Docking API](#docking-api)
- [Drug Repurposing API](#drug-repurposing-api)
- [Convert SMILES API](#convert-smiles-api)
- [User Management Endpoints](#user-management-endpoints)
- [Awards And Scientists API](#awards-and-scientists-api)
- [News API](#news-api)
- [Admet Prediction API](#admet-prediction-api)
- [AI Generation API](#ai-generation-api)
- [MD Simulation API](#md-simulation-api)
- [Error Handling](#error-handling)
- [Rate Limiting](#rate-limiting)
- [Integration Examples](#integration-examples)
- [Support](#support)

---

## Overview

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

## Authentication

### Overview

AILIXIR uses **Laravel Sanctum** for API authentication. Most endpoints require a valid JWT bearer token.

### Types of Tokens

| Token Type        | Purpose                      | Duration      |
| ----------------- | ---------------------------- | ------------- |
| **Access Token**  | Query user data, submit jobs | Session-based |
| **API Token**     | Long-lived access (optional) | 365 days      |
| **Refresh Token** | Renew access after expiry    | 14 days       |

### Authentication Header

All authenticated requests must include:

```http
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### Token Generation

Tokens are issued after successful login or registration. Store securely (never in localStorage for sensitive apps).

---

## Base URLs

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

## Common Patterns

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

## Health & Status

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

## Authentication Endpoints

### Register User

```http
POST /user/register
Content-Type: application/json

{
  "name": "Yasmeen Ahmed",
  "email": "yasmeen@test.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response (201 Created):**

```json
{
    "success": true,
    "message": "Registered successfully. Please check your email for OTP verification code.",
    "data": {
        "email": "yasmeen@test.com"
    }
}
```

---

### Login

```http
POST /user/login
Content-Type: application/json

{
  "email": "yasmeen@test.com",
  "password": "password123"
}
```

**Response (200 OK):**

```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "token": "55|F4V06Yis3FcP35oGoEE2cwmNv3yTiVCpMEx9vYjya891dcb2",
        "user": {
            "id": 80,
            "name": "yasmeen564",
            "email": "salehyasmeen080@gmail.com",
            "email_verified_at": null,
            "last_otp_sent_at": null,
            "role": "normal",
            "created_at": "2026-05-25T00:21:51.000000Z",
            "updated_at": "2026-05-30T00:02:06.000000Z",
            "email_verification_otp_expires_at": null,
            "is_verified": true,
            "password_reset_otp_expires_at": null
        }
    }
}
```

---

### Verify Email

After account creation, verify email to activate account:

```http
POST /user/verify-email
Content-Type: application/json

{
  "email": "yasmeen@test.com",
  "otp": "123456"
}
```

**Response (200 OK):**

```json
{
    "success": true,
    "message": "Email verified successfully",
    "data": {
        "token": "54|dxcTq6KRZQfOESfbKcqEoXV4znbkU8IKCND1nX2H085c2309",
        "user": {
            "id": 80,
            "name": "yasmeen564",
            "email": "salehyasmeen080@gmail.com",
            "email_verified_at": null,
            "last_otp_sent_at": null,
            "role": "normal",
            "created_at": "2026-05-25T00:21:51.000000Z",
            "updated_at": "2026-05-30T00:02:06.000000Z",
            "email_verification_otp_expires_at": null,
            "is_verified": true,
            "password_reset_otp_expires_at": null
        }
    }
}
```

---

### Resend Verification Email

```http
POST /user/resend-verification
Content-Type: application/json

{
  "email": "yasmeen@test.com"
}
```

**Response (200 OK):**

```json
{
    "success": true,
    "message": "OTP resent successfully",
    "data": {
        "email": "yasmeen111@example.com"
    }
}
```

---

### Forgot Password

```http
POST /user/forgot-password
Content-Type: application/json

{
  "email": "yasmeen@test.com"
}
```

**Response (200 OK):**

```json
{
    "success": true,
    "message": "OTP sent successfully",
    "data": {
        "email": "salehyasmeen080@gmail.com"
    }
}
```

---

### Reset Password

```http
POST /user/reset-password
Content-Type: application/json

{
  "email": "yasmeen@test.com",
  "otp": "123456",
  "password": "new_password123",
  "password_confirmation": "new_password123"
}
```

**Response (200 OK):**

```json
{
    "success": true,
    "message": "Password reset successfully"
}
```

---

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

## '''

## AI Integration Endpoints

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
            "available_models": ["MPNN_CNN_BindingDB"]
        }
    }
}
```

---

## AI Service Endpoints

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

## AI Agent Endpoints (Chemistry AI)

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
        "thread_id": "3a148307-b97f-4910-88db-03eca6477419",
        "id": 2,
        "created_at": "2026-05-25T18:34:15.000000Z"
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
            "id": 2,
            "thread_id": "3a148307-b97f-4910-88db-03eca6477419",
            "title": "New Conversation",
            "last_used_at": "2026-05-25T18:34:15.000000Z",
            "created_at": "2026-05-25T18:34:15.000000Z"
        },
        {
            "id": 1,
            "thread_id": "0455ef45-faa0-4e82-b808-db1c574ad175",
            "title": "New Conversation",
            "last_used_at": "2026-05-25T18:21:23.000000Z",
            "created_at": "2026-05-24T14:54:10.000000Z"
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

| Type       | What It Runs                       | Speed  |
| ---------- | ---------------------------------- | ------ |
| `full`     | Properties + drug-likeness + ADMET | Slow   |
| `quick`    | Lipinski pass/fail + QED only      | Fast   |
| `admet`    | ADMET profile only                 | Medium |
| `classify` | Drug-likeness classification only  | Fast   |

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

| Parameter | Type    | Description                                    |
| --------- | ------- | ---------------------------------------------- |
| `type`    | string  | Filter: `smiles`, `compare`, `docking`, `chat` |
| `page`    | integer | Page number                                    |

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

## Chemical Search Endpoints

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
    "query": {
        "smiles": "CN1C=NC2=C1C(=O)N(C(=O)N2C)",
        "top_k": 3
    },
    "compounds": [
        {
            "rank": 1,
            "smiles": "CN1C=NC2=C1C(=O)NC(=O)N2C",
            "name": "Compound_5429",
            "cid": "5429",
            "similarity": 1,
            "explanation": null,
            "image_url": "https://unsteady-chlorine-imaginary.ngrok-free.dev/static/images/6096554304416497818.png"
        },
        {
            "rank": 2,
            "smiles": "CN1C=NC2=C1C(=O)N(C(=O)N2C)C",
            "name": "Compound_2519",
            "cid": "2519",
            "similarity": 0.9922,
            "explanation": null,
            "image_url": "https://unsteady-chlorine-imaginary.ngrok-free.dev/static/images/3925926930136341582.png"
        },
        {
            "rank": 3,
            "smiles": "CN1C=NC2=C1C(=O)N(C(=O)N2)C",
            "name": "Compound_4687",
            "cid": "4687",
            "similarity": 0.9922,
            "explanation": null,
            "image_url": "https://unsteady-chlorine-imaginary.ngrok-free.dev/static/images/5189886293630140187.png"
        }
    ],
    "metadata": {
        "total_results": 3,
        "search_time_ms": 443.08,
        "similarity_metric": "Tanimoto",
        "fingerprint": "Morgan (2048, radius=2)",
        "source": "retrieval"
    }
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
    "query": {
        "smiles": "C1=CC=C(C(=C1)C(=O)O)",
        "top_k": 2
    },
    "compounds": [
        {
            "rank": 1,
            "smiles": "C1=CC=C(C=C1)C(=O)O",
            "name": "Compound_243",
            "cid": "243",
            "similarity": 1,
            "explanation": "These compounds are structurally identical, as indicated by the similarity score of 1.000. Both share the same core scaffold of a benzene ring (C1=CC=C(C=C1)) with a carboxylic acid group (C(=O)O) attached to it. The molecular formulas (C7H6O2) and heavy atom counts (9) are identical, confirming no atom substitutions or differences in structure. The only apparent difference is the SMILES representation, which is",
            "image_url": "https://unsteady-chlorine-imaginary.ngrok-free.dev/static/images/2500971538592982398.png"
        },
        {
            "rank": 2,
            "smiles": "C1=CC=C(C=C1)[13C](=O)O",
            "name": "Compound_19759",
            "cid": "19759",
            "similarity": 1,
            "explanation": "These compounds are structurally identical, as indicated by the similarity score of 1.000. Both share the same core scaffold of a benzene ring (C1=CC=C(C=C1)) with a carboxylic acid group (C(=O)O) attached. The only difference is that the match compound has a ^13C isotope substitution at the carboxylic acid carbon, which does not alter the molecular structure or connectivity. Both compounds have the same molecular formula (C7H6O",
            "image_url": "https://unsteady-chlorine-imaginary.ngrok-free.dev/static/images/8561718417946188610.png"
        }
    ],
    "metadata": {
        "total_results": 2,
        "search_time_ms": 7975.89,
        "similarity_metric": "Tanimoto",
        "fingerprint": "Morgan (2048, radius=2)",
        "source": "full_rag"
    }
}
```

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

#### Response (completed)

```json
{
    "success": true,
    "message": "Job details retrieved successfully",
    "data": {
        "id": 5,
        "status": "completed",
        "protein": "EGFR",
        "ligand": "Erlotinib",
        "created_at": "2026-06-17T05:45:31+00:00",
        "download_url": "{base_url}/api/docking/download/5",
        "scores": [
            {"affinity": 0, "inter": 0, "intra": -2.031, "torsions": 0, "unbound": -2.031},
            {"affinity": 0, "inter": 0, "intra": -2.031, "torsions": 0, "unbound": -2.031},
            {"affinity": 0.001, "inter": 0, "intra": -2.031, "torsions": 0, "unbound": -2.031}
        ],
        "error": null
    }
}
```

If the job is not completed, `scores` will be an empty array.

#### Response (failed)

```json
{
    "success": true,
    "message": "Job details retrieved successfully",
    "data": {
        "id": 7,
        "status": "failed",
        "protein": "EGFR",
        "ligand": "Erlotinib",
        "created_at": "2026-06-17T05:45:31+00:00",
        "download_url": "{base_url}/api/docking/download/7",
        "scores": [],
        "error": "\n\nPDBQT parsing error: Unknown or inappropriate tag found in flex residue or ligand.\n > ATOM      1  N   UNL     1       8.304 191.693  26.328  0.00  0.00    +0.000 N \n"
    }
}
```

#### Error response (not found)

```json
{
    "success": false,
    "message": "Docking job not found or unauthorized",
    "data": null
}
```

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
        "results": [
            {
                "id": 5,
                "status": "completed",
                "protein": "EGFR",
                "ligand": "Erlotinib",
                "created_at": "2026-06-17T05:45:31+00:00",
                "download_url": "{base_url}/api/docking/download/5",
                "scores": [
                    {"affinity": 0, "inter": 0, "intra": -2.031, "torsions": 0, "unbound": -2.031},
                    {"affinity": 0, "inter": 0, "intra": -2.031, "torsions": 0, "unbound": -2.031},
                    {"affinity": 0.001, "inter": 0, "intra": -2.031, "torsions": 0, "unbound": -2.031}
                ],
                "error": null
            }
        ],
        "pagination": {
            "currentPage": 1,
            "totalPages": 2,
            "totalResults": 3,
            "perPage": 2,
            "hasNextPage": true,
            "hasPrevPage": false
        }
    }
}
```

---

### GET `/api/docking/download/{id}`

- Path parameter:
    - `id` (integer)
- Query parameter (instead of Bearer header for browser link clicks):
    - `token` (string, required) — Sanctum token
- Accepts `Authorization: Bearer` header or `?token=` query parameter.
- Returns a file download for the completed docking result (multi-model PDBQT with all poses).
- Content disposition filename: `docking_result_{id}.pdbqt`

---

## Drug Repurposing API

### POST `/api/drug-repurposing/targets`

- Auth required
- Content type: `application/json`
- Request body:
    - `disease_name` (string, required)
    - `top_n` (integer, optional, default 10, range 1–100)

#### Example curl request

```bash
curl -X POST "{base_url}/api/drug-repurposing/targets" \\
  -H "Authorization: Bearer {token}" \\
  -H "Content-Type: application/json" \\
  -d '{"disease_name": "Type 2 Diabetes", "top_n": 10}'
```

#### Success response

```json
{
    "success": true,
    "message": "Target lookup queued successfully",
    "data": {
        "job_id": 15,
        "status": "pending"
    }
}
```

---

### GET `/api/drug-repurposing/targets/{id}`

- Auth required
- Path parameter:
    - `id` (integer)

#### Success response (completed)

```json
{
    "success": true,
    "message": "Target lookup history retrieved successfully",
    "data": {
        "id": 4,
        "input": {
            "disease_name": "Type 2 Diabetes",
            "top_n": 10
        },
        "output": {
            "disease": "Type 2 Diabetes",
            "disease_id": "EFO_0001360",
            "total_targets": 10,
            "targets": [
                {"symbol": "KCNJ11", "name": "potassium inwardly rectifying channel subfamily J member 11", "score": 0.8651, "sequence": null, "uniprot_id": "Q14654", "pdb_ids": ["2UKM", "2UGY", "2UUG"]},
                {"symbol": "ABCC8", "name": "ATP binding cassette subfamily C member 8", "score": 0.8648, "sequence": null, "uniprot_id": "Q09428", "pdb_ids": []},
                {"symbol": "GCK", "name": "glucokinase", "score": 0.8612, "sequence": null, "uniprot_id": "P35557", "pdb_ids": ["1V4S", "3F9M", "4ISE"]},
                {"symbol": "PPARG", "name": "peroxisome proliferator activated receptor gamma", "score": 0.8486, "sequence": null, "uniprot_id": "P37231", "pdb_ids": ["7AEX", "7AEW", "7AEV"]},
                {"symbol": "INSR", "name": "insulin receptor", "score": 0.7887, "sequence": null, "uniprot_id": "P06213", "pdb_ids": ["2HR7", "3EKN", "4IBM"]},
                {"symbol": "HNF1B", "name": "HNF1 homeobox B", "score": 0.7846, "sequence": null, "uniprot_id": "P35680", "pdb_ids": []},
                {"symbol": "HNF1A", "name": "HNF1 homeobox A", "score": 0.7796, "sequence": null, "uniprot_id": "P20823", "pdb_ids": []},
                {"symbol": "HNF4A", "name": "hepatocyte nuclear factor 4 alpha", "score": 0.7763, "sequence": null, "uniprot_id": "P41235", "pdb_ids": ["7D1C", "7D1D"]},
                {"symbol": "WFS1", "name": "wolframin ER transmembrane glycoprotein", "score": 0.7695, "sequence": null, "uniprot_id": "O76024", "pdb_ids": []},
                {"symbol": "GLP1R", "name": "glucagon like peptide 1 receptor", "score": 0.7667, "sequence": null, "uniprot_id": "P43220", "pdb_ids": []}
            ]
        },
        "status": "completed"
    }
}
```

If the job is not completed, the `output` field will be `null`.

---

### GET `/api/drug-repurposing/targets/history`

- Auth required
- Query parameters:
    - `per_page` (integer, optional, default 15)

#### Response

```json
{
    "success": true,
    "message": "Target lookup history retrieved successfully",
    "data": {
        "data": [
            {
                "id": 4,
                "input": {
                    "disease_name": "Type 2 Diabetes",
                    "top_n": 10
                },
                "output": {
                    "disease": "Type 2 Diabetes",
                    "disease_id": "EFO_0001360",
                    "total_targets": 10,
                    "targets": [
                        {"symbol": "KCNJ11", "name": "potassium inwardly rectifying channel subfamily J member 11", "score": 0.8651, "sequence": null, "uniprot_id": "Q14654", "pdb_ids": ["2UKM", "2UGY", "2UUG"]},
                        {"symbol": "GCK", "name": "glucokinase", "score": 0.8612, "sequence": null, "uniprot_id": "P35557", "pdb_ids": ["1V4S", "3F9M", "4ISE"]}
                    ]
                },
                "status": "completed",
                "created_at": "2026-06-03T07:31:42.000000Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 15,
            "total": 3,
            "last_page": 1,
            "has_more": false
        }
    }
}
```

---

### POST `/api/drug-repurposing/screen`

- Auth required
- Content type: `application/json`
- Request body:
    - `disease_name` (string, required)
    - `known_drugs` (array of strings, optional)
    - `min_score` (numeric, optional, range 0–1)
    - `top_n_targets` (integer, optional, range 1–100)

#### Example curl request

```bash
curl -X POST "{base_url}/api/drug-repurposing/screen" \\
  -H "Authorization: Bearer {token}" \\
  -H "Content-Type: application/json" \\
  -d '{
    "disease_name": "Type 2 Diabetes",
    "known_drugs": ["Metformin", "Insulin"],
    "min_score": 0.5,
    "top_n_targets": 10
  }'
```

#### Success response

```json
{
    "success": true,
    "message": "Screening queued successfully",
    "data": {
        "job_id": 22,
        "status": "pending"
    }
}
```

---

### GET `/api/drug-repurposing/screen/{id}`

- Auth required
- Path parameter:
    - `id` (integer)

#### Success response (completed)

```json
{
    "success": true,
    "message": "Screening history retrieved successfully",
    "data": {
        "id": 22,
        "input": {
            "disease_name": "Type 2 Diabetes",
            "min_score": 0.5,
            "top_n_targets": 10,
            "known_drugs": ["Metformin", "Insulin"]
        },
        "output": {
            "disease_name": "Type 2 Diabetes",
            "total_targets_found": 10,
            "total_drugs_screened": 200,
            "total_pairs_evaluated": 2000,
            "top_candidates": [
                {"drug_name": "Drug_CHEMBL1754", "smiles": "CC1=C(C=C(C=C1)NC(=O)C2=CC=C(C=C2)Cl)Cl", "target_symbol": "KCNJ11", "uniprot_id": "Q14654", "binding_score": 0.9821, "rank": 1, "status": "Potential Discovery"},
                {"drug_name": "Drug_CHEMBL1754", "smiles": "CC1=C(C=C(C=C1)NC(=O)C2=CC=C(C=C2)Cl)Cl", "target_symbol": "ABCC8", "uniprot_id": "Q09428", "binding_score": 0.9765, "rank": 2, "status": "Potential Discovery"},
                {"drug_name": "Drug_CHEMBL1754", "smiles": "CC1=C(C=C(C=C1)NC(=O)C2=CC=C(C=C2)Cl)Cl", "target_symbol": "GCK", "uniprot_id": "P35557", "binding_score": 0.9712, "rank": 3, "status": "Potential Discovery"},
                {"drug_name": "Drug_CHEMBL1754", "smiles": "CC1=C(C=C(C=C1)NC(=O)C2=CC=C(C=C2)Cl)Cl", "target_symbol": "PPARG", "uniprot_id": "P37231", "binding_score": 0.9689, "rank": 4, "status": "Potential Discovery"},
                {"drug_name": "Drug_CHEMBL1754", "smiles": "CC1=C(C=C(C=C1)NC(=O)C2=CC=C(C=C2)Cl)Cl", "target_symbol": "INSR", "uniprot_id": "P06213", "binding_score": 0.9634, "rank": 5, "status": "Potential Discovery"}
            ],
            "warnings": []
        },
        "status": "completed"
    }
}
```

If the job is not completed, the `output` field will be `null`.

---

### GET `/api/drug-repurposing/screen/history`

- Auth required
- Query parameters:
    - `per_page` (integer, optional, default 15)

#### Response

```json
{
    "success": true,
    "message": "Screening history retrieved successfully",
    "data": {
        "data": [
            {
                "id": 22,
                "input": {
                    "disease_name": "Type 2 Diabetes",
                    "min_score": 0.5,
                    "top_n_targets": 10,
                    "known_drugs": ["Metformin", "Insulin"]
                },
                "output": {
                    "disease_name": "Type 2 Diabetes",
                    "total_targets_found": 10,
                    "total_drugs_screened": 200,
                    "total_pairs_evaluated": 2000,
                    "top_candidates": [
                        {"drug_name": "Drug_CHEMBL1754", "smiles": "CC1=C(C=C(C=C1)NC(=O)C2=CC=C(C=C2)Cl)Cl", "target_symbol": "KCNJ11", "uniprot_id": "Q14654", "binding_score": 0.9821, "rank": 1, "status": "Potential Discovery"},
                        {"drug_name": "Drug_CHEMBL1754", "smiles": "CC1=C(C=C(C=C1)NC(=O)C2=CC=C(C=C2)Cl)Cl", "target_symbol": "ABCC8", "uniprot_id": "Q09428", "binding_score": 0.9765, "rank": 2, "status": "Potential Discovery"}
                    ],
                    "warnings": []
                },
                "status": "completed",
                "created_at": "2026-06-02T21:35:24.000000Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 15,
            "total": 5,
            "last_page": 1,
            "has_more": false
        }
    }
}
```

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

## User Management Endpoints

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

## Awards And Scientists API

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

## News API

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

## Admet Prediction API

### POST `/api/admet/predict`

Predict ADMET properties for chemical compounds from SMILES strings or file uploads.

**Authentication:** Bearer token required

**Input Options:**

| Option   | Type   | Description                                |
| -------- | ------ | ------------------------------------------ |
| `smiles` | string | Comma-separated SMILES strings (max 6)     |
| `file`   | file   | CSV or TXT file with SMILES (max 100 rows) |

**Example 1: JSON Input**

```bash
curl -X POST /api/admet/predict \\
  -H "Authorization: Bearer {token}" \\
  -H "Content-Type: application/json" \\
  -d '{"smiles": "c1ccccc1, CCO, CCC"}'
```

**Example 2: File Upload Input (CSV or TXT)**

```bash
curl -X POST /api/admet/predict \\
  -H "Authorization: Bearer {token}" \\
  -F "file=@/path/to/file.csv"
```

**Response (200 OK):**

```json
{
    "success": true,
    "message": "ADMET predictions generated successfully",
    "data": [
        {
            "smiles": "c1ccccc1",
            "absorption": -2.9907076358795166,
            "distribution": 0.8750113248825073,
            "metabolism": -0.11453431844711304,
            "excretion": 7.360866546630859,
            "toxicity": 0.8492187261581421
        },
        {
            "smiles": "CCO",
            "absorption": -3.7663559913635254,
            "distribution": 0.8921501040458679,
            "metabolism": -0.2787337601184845,
            "excretion": 20.92142677307129,
            "toxicity": -0.6471661329269409
        },
        {
            "smiles": "CCC",
            "absorption": -3.09336519241333,
            "distribution": 1.2096238136291504,
            "metabolism": -0.17122718691825867,
            "excretion": 20.524127960205078,
            "toxicity": -0.25303196907043457
        }
    ]
}
```

'''

## AI Generation API

### POST `/api/ai/generation/run`

**Authentication:** Bearer token required

**Input Options:**

| Option          | Type    | Description                                                                 |
| --------------- | ------- | --------------------------------------------------------------------------- |
| `num_molecules` | integer | Number of molecules to generate                                             |
| `return_top_k`  | integer | Number of top molecules to return                                           |
| `docking_mode`  | string  | Docking mode: "all" or "off" or "top_k"                                     |
| `dock_top_k`    | integer | Number of top molecules to dock is only required when docking mode is top_k |

**Example:**

```bash
curl -X POST /api/ai/generation/run \\
  -H "Authorization: Bearer {token}" \\
  -H "Content-Type: application/json" \\
  -d '{"num_molecules": 5, "return_top_k": 5, "docking_mode": "all", "dock_top_k": 5}'
```

**Response (200 OK):**

```json
{
    "success": true,
    "message": "Generation job started successfully",
    "job_id": "gen_20260602_174612_90c1b1",
    "status": "running",
    "preset": "egfr_generator",
    "num_molecules": 5,
    "return_top_k": 5,
    "docking_mode": "all",
    "dock_top_k": 5,
    "created_at": "2026-06-02 17:46:10"
}
```

---

**GET `/api/ai/generation/status/{job_id}`**

**Authentication:** Bearer token required

**Example:**

```bash
curl -X GET /api/ai/generation/status/gen_20260602_174612_90c1b1 \\
  -H "Authorization: Bearer {token}"
```

**Response (200 OK):**

```json
{
    "success": true,
    "job_id": "gen_20260602_174612_90c1b1",
    "status": "completed",
    "preset": "egfr_generator",
    "num_molecules": 5,
    "return_top_k": 5,
    "docking_mode": "all",
    "dock_top_k": 5,
    "created_at": "2026-06-02 17:46:10"
}
```

---

**GET `/api/ai/generation/jobs/{job_id}/results`**

**Authentication:** Bearer token required

**Example:**

```bash
curl -X GET /api/ai/generation/jobs/gen_20260602_174612_90c1b1/results \\
  -H "Authorization: Bearer {token}"
```

**Response (200 OK):**

```json
{
    "success": true,
    "job_id": "gen_20260602_174612_90c1b1",
    "status": "completed",
    "preset": "egfr_generator",
    "num_molecules": 5,
    "return_top_k": 5,
    "docking_mode": "all",
    "dock_top_k": 5,
    "summary": {
        "num_requested": 5,
        "num_generated": 5,
        "num_valid": 5,
        "num_returned": 5,
        "num_docked": 5
    },
    "files": {
        "csv": {
            "filename": "generated_results.csv"
        },
        "json": {
            "filename": "generated_results.json"
        }
    },
    "ligands": [
        {
            "SMILES": "CCN1CCN(CCC(=O)Nc2ccc3nncc(-c4ccc5ncncc5c4)c3c2)C1",
            "SMILES_state": 1,
            "NLL": 8.24,
            "valid": true,
            "canonical_smiles": "CCN1CCN(CCC(=O)Nc2ccc3nncc(-c4ccc5ncncc5c4)c3c2)C1",
            "mw": 427.5120000000002,
            "logp": 3.1636000000000006,
            "tpsa": 87.14,
            "hbd": 1,
            "hba": 7,
            "rot_bonds": 6,
            "qed": 0.505688822717197,
            "sa_score": 2.715858141741272,
            "pred_pAff_mean": 10.734132766723633,
            "docking_score": -8.91,
            "docking_status": "completed",
            "rank": 1
        },
        {
            "SMILES": "Cn1cnc2ccc(Nc3ncnc4ccc(NC(=O)CCN5CCC5)cc34)cc21",
            "SMILES_state": 1,
            "NLL": 5.32,
            "valid": true,
            "canonical_smiles": "Cn1cnc2ccc(Nc3ncnc4ccc(NC(=O)CCN5CCC5)cc34)cc21",
            "mw": 401.4740000000003,
            "logp": 3.2944000000000013,
            "tpsa": 87.96999999999998,
            "hbd": 2,
            "hba": 6,
            "rot_bonds": 6,
            "qed": 0.5154635202823702,
            "sa_score": 2.404394987645352,
            "pred_pAff_mean": 9.775605201721191,
            "docking_score": -8.27,
            "docking_status": "completed",
            "rank": 2
        },
        {
            "SMILES": "CN1CCCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cnccc5c4)c3c2)C1",
            "SMILES_state": 1,
            "NLL": 5.95,
            "valid": true,
            "canonical_smiles": "CN1CCCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cnccc5c4)c3c2)C1",
            "mw": 426.5240000000002,
            "logp": 3.768600000000002,
            "tpsa": 74.25,
            "hbd": 1,
            "hba": 6,
            "rot_bonds": 5,
            "qed": 0.523710400747619,
            "sa_score": 2.669701840595609,
            "pred_pAff_mean": 9.587095260620115,
            "docking_score": -10.25,
            "docking_status": "completed",
            "rank": 3
        },
        {
            "SMILES": "CN1CCCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cnnnc5c4)c3c2)C1",
            "SMILES_state": 1,
            "NLL": 8.32,
            "valid": true,
            "canonical_smiles": "CN1CCCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cnnnc5c4)c3c2)C1",
            "mw": 428.5000000000002,
            "logp": 2.5586,
            "tpsa": 100.03,
            "hbd": 1,
            "hba": 8,
            "rot_bonds": 5,
            "qed": 0.5177099598248629,
            "sa_score": 2.917413951888572,
            "pred_pAff_mean": 8.804740905761719,
            "docking_score": -10.15,
            "docking_status": "completed",
            "rank": 4
        },
        {
            "SMILES": "O=C(CN1CCNCC1)Nc1ccc2nncc(-c3ccc4cnncc4c3)c2c1",
            "SMILES_state": 1,
            "NLL": 8.41,
            "valid": true,
            "canonical_smiles": "O=C(CN1CCNCC1)Nc1ccc2nncc(-c3ccc4cnncc4c3)c2c1",
            "mw": 399.45800000000014,
            "logp": 2.0836999999999994,
            "tpsa": 95.93,
            "hbd": 2,
            "hba": 7,
            "rot_bonds": 4,
            "qed": 0.5423758682727866,
            "sa_score": 2.665545251407164,
            "pred_pAff_mean": 7.531160354614258,
            "docking_score": -9.5,
            "docking_status": "completed",
            "rank": 5
        }
    ],
    "created_at": "2026-06-02 17:46:10"
}
```

---

### GET `/api/ai/generation/history`

**Authentication:** Bearer token required

**Example:**

```bash
curl -X GET http://localhost:8080/api/ai/generation/history \
  -H "Authorization: Bearer {token}"
```

**Response:**

```json
{
    "success": true,
    "message": "Generation job history retrieved successfully",
    "data": {
        "results": [
            {
                "id": 5,
                "user_id": 1,
                "job_id": "gen_20260602_185419_15fc7b",
                "status": "completed",
                "preset": "egfr_generator",
                "num_molecules": 3,
                "return_top_k": 3,
                "docking_mode": "all",
                "dock_top_k": 3,
                "summary": {
                    "num_requested": 3,
                    "num_generated": 3,
                    "num_valid": 3,
                    "num_returned": 3,
                    "num_docked": 3
                },
                "files": {
                    "csv": {
                        "filename": "generated_results.csv",
                        "relative_url": "/files/jobs/gen_20260602_185419_15fc7b/generated_results.csv",
                        "download_url": "https://superplausibly-nonflowering-keiko.ngrok-free.dev/files/jobs/gen_20260602_185419_15fc7b/generated_results.csv"
                    },
                    "json": {
                        "filename": "generated_results.json",
                        "relative_url": "/files/jobs/gen_20260602_185419_15fc7b/generated_results.json",
                        "download_url": "https://superplausibly-nonflowering-keiko.ngrok-free.dev/files/jobs/gen_20260602_185419_15fc7b/generated_results.json"
                    }
                },
                "created_at": "2026-06-02T18:54:16.000000Z",
                "updated_at": "2026-06-02T18:56:01.000000Z",
                "ligands": [
                    {
                        "SMILES": "CN1CCCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cncnc5c4)c3c2)C1",
                        "SMILES_state": 1,
                        "NLL": 4.14,
                        "valid": true,
                        "canonical_smiles": "CN1CCCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cncnc5c4)c3c2)C1",
                        "mw": 427.5120000000002,
                        "logp": 3.1636000000000006,
                        "tpsa": 87.14,
                        "hbd": 1,
                        "hba": 7,
                        "rot_bonds": 5,
                        "qed": 0.5234216428527144,
                        "sa_score": 2.7645745083533857,
                        "pred_pAff_mean": 10.726750373840332,
                        "docking_score": -9.16,
                        "docking_status": "completed",
                        "rank": 1
                    },
                    {
                        "SMILES": "O=C(CCN1CCCC1)Nc1ccc2c(Nc3cccc(Cl)c3)ncnc2c1",
                        "SMILES_state": 1,
                        "NLL": 6.06,
                        "valid": true,
                        "canonical_smiles": "O=C(CCN1CCCC1)Nc1ccc2c(Nc3cccc(Cl)c3)ncnc2c1",
                        "mw": 395.89400000000006,
                        "logp": 4.451200000000003,
                        "tpsa": 70.15,
                        "hbd": 2,
                        "hba": 5,
                        "rot_bonds": 6,
                        "qed": 0.6447750051881624,
                        "sa_score": 2.11252799494409,
                        "pred_pAff_mean": 8.844084739685059,
                        "docking_score": -8.23,
                        "docking_status": "completed",
                        "rank": 2
                    },
                    {
                        "SMILES": "CN1CCC2C1CCN2CCC(=O)Nc1ccc2ncnc(Nc3ccc4ncncc4c3)c2c1",
                        "SMILES_state": 1,
                        "NLL": 22.74,
                        "valid": true,
                        "canonical_smiles": "CN1CCC2C1CCN2CCC(=O)Nc1ccc2ncnc(Nc3ccc4ncncc4c3)c2c1",
                        "mw": 468.5650000000001,
                        "logp": 3.4236000000000013,
                        "tpsa": 99.17,
                        "hbd": 2,
                        "hba": 8,
                        "rot_bonds": 6,
                        "qed": 0.4441562846144389,
                        "sa_score": 3.462932738106919,
                        "pred_pAff_mean": 8.693540573120117,
                        "docking_score": -9.2,
                        "docking_status": "completed",
                        "rank": 3
                    }
                ]
            },
            {
                "id": 4,
                "user_id": 1,
                "job_id": "gen_20260602_185402_e9b887",
                "status": "running",
                "preset": "egfr_generator",
                "num_molecules": 5,
                "return_top_k": 5,
                "docking_mode": "all",
                "dock_top_k": 5,
                "summary": null,
                "files": null,
                "created_at": "2026-06-02T18:54:00.000000Z",
                "updated_at": "2026-06-02T18:54:00.000000Z",
                "ligands": null
            },
            {
                "id": 3,
                "user_id": 1,
                "job_id": "gen_20260602_174612_90c1b1",
                "status": "completed",
                "preset": "egfr_generator",
                "num_molecules": 5,
                "return_top_k": 5,
                "docking_mode": "all",
                "dock_top_k": 5,
                "summary": {
                    "num_requested": 5,
                    "num_generated": 5,
                    "num_valid": 5,
                    "num_returned": 5,
                    "num_docked": 5
                },
                "files": {
                    "csv": {
                        "filename": "generated_results.csv",
                        "relative_url": "/files/jobs/gen_20260602_174612_90c1b1/generated_results.csv",
                        "download_url": "https://superplausibly-nonflowering-keiko.ngrok-free.dev/files/jobs/gen_20260602_174612_90c1b1/generated_results.csv"
                    },
                    "json": {
                        "filename": "generated_results.json",
                        "relative_url": "/files/jobs/gen_20260602_174612_90c1b1/generated_results.json",
                        "download_url": "https://superplausibly-nonflowering-keiko.ngrok-free.dev/files/jobs/gen_20260602_174612_90c1b1/generated_results.json"
                    }
                },
                "created_at": "2026-06-02T17:46:10.000000Z",
                "updated_at": "2026-06-02T17:51:19.000000Z",
                "ligands": [
                    {
                        "SMILES": "CCN1CCN(CCC(=O)Nc2ccc3nncc(-c4ccc5ncncc5c4)c3c2)C1",
                        "SMILES_state": 1,
                        "NLL": 8.24,
                        "valid": true,
                        "canonical_smiles": "CCN1CCN(CCC(=O)Nc2ccc3nncc(-c4ccc5ncncc5c4)c3c2)C1",
                        "mw": 427.5120000000002,
                        "logp": 3.1636000000000006,
                        "tpsa": 87.14,
                        "hbd": 1,
                        "hba": 7,
                        "rot_bonds": 6,
                        "qed": 0.505688822717197,
                        "sa_score": 2.715858141741272,
                        "pred_pAff_mean": 10.734132766723633,
                        "docking_score": -8.91,
                        "docking_status": "completed",
                        "rank": 1
                    },
                    {
                        "SMILES": "Cn1cnc2ccc(Nc3ncnc4ccc(NC(=O)CCN5CCC5)cc34)cc21",
                        "SMILES_state": 1,
                        "NLL": 5.32,
                        "valid": true,
                        "canonical_smiles": "Cn1cnc2ccc(Nc3ncnc4ccc(NC(=O)CCN5CCC5)cc34)cc21",
                        "mw": 401.4740000000003,
                        "logp": 3.2944000000000013,
                        "tpsa": 87.96999999999998,
                        "hbd": 2,
                        "hba": 6,
                        "rot_bonds": 6,
                        "qed": 0.5154635202823702,
                        "sa_score": 2.404394987645352,
                        "pred_pAff_mean": 9.775605201721191,
                        "docking_score": -8.27,
                        "docking_status": "completed",
                        "rank": 2
                    },
                    {
                        "SMILES": "CN1CCCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cnccc5c4)c3c2)C1",
                        "SMILES_state": 1,
                        "NLL": 5.95,
                        "valid": true,
                        "canonical_smiles": "CN1CCCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cnccc5c4)c3c2)C1",
                        "mw": 426.5240000000002,
                        "logp": 3.768600000000002,
                        "tpsa": 74.25,
                        "hbd": 1,
                        "hba": 6,
                        "rot_bonds": 5,
                        "qed": 0.523710400747619,
                        "sa_score": 2.669701840595609,
                        "pred_pAff_mean": 9.587095260620115,
                        "docking_score": -10.25,
                        "docking_status": "completed",
                        "rank": 3
                    },
                    {
                        "SMILES": "CN1CCCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cnnnc5c4)c3c2)C1",
                        "SMILES_state": 1,
                        "NLL": 8.32,
                        "valid": true,
                        "canonical_smiles": "CN1CCCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cnnnc5c4)c3c2)C1",
                        "mw": 428.5000000000002,
                        "logp": 2.5586,
                        "tpsa": 100.03,
                        "hbd": 1,
                        "hba": 8,
                        "rot_bonds": 5,
                        "qed": 0.5177099598248629,
                        "sa_score": 2.917413951888572,
                        "pred_pAff_mean": 8.804740905761719,
                        "docking_score": -10.15,
                        "docking_status": "completed",
                        "rank": 4
                    },
                    {
                        "SMILES": "O=C(CN1CCNCC1)Nc1ccc2nncc(-c3ccc4cnncc4c3)c2c1",
                        "SMILES_state": 1,
                        "NLL": 8.41,
                        "valid": true,
                        "canonical_smiles": "O=C(CN1CCNCC1)Nc1ccc2nncc(-c3ccc4cnncc4c3)c2c1",
                        "mw": 399.45800000000014,
                        "logp": 2.0836999999999994,
                        "tpsa": 95.93,
                        "hbd": 2,
                        "hba": 7,
                        "rot_bonds": 4,
                        "qed": 0.5423758682727866,
                        "sa_score": 2.665545251407164,
                        "pred_pAff_mean": 7.531160354614258,
                        "docking_score": -9.5,
                        "docking_status": "completed",
                        "rank": 5
                    }
                ]
            },
            {
                "id": 2,
                "user_id": 1,
                "job_id": "gen_20260602_162928_00f1a6",
                "status": "completed",
                "preset": "egfr_generator",
                "num_molecules": 5,
                "return_top_k": 5,
                "docking_mode": "all",
                "dock_top_k": 5,
                "summary": {
                    "num_requested": 5,
                    "num_generated": 5,
                    "num_valid": 5,
                    "num_returned": 5,
                    "num_docked": 5
                },
                "files": {
                    "csv": {
                        "filename": "generated_results.csv",
                        "relative_url": "/files/jobs/gen_20260602_162928_00f1a6/generated_results.csv",
                        "download_url": "https://abcd-1234.ngrok-free.app/files/jobs/gen_20260602_162928_00f1a6/generated_results.csv"
                    },
                    "json": {
                        "filename": "generated_results.json",
                        "relative_url": "/files/jobs/gen_20260602_162928_00f1a6/generated_results.json",
                        "download_url": "https://abcd-1234.ngrok-free.app/files/jobs/gen_20260602_162928_00f1a6/generated_results.json"
                    }
                },
                "created_at": "2026-06-02T16:29:26.000000Z",
                "updated_at": "2026-06-02T16:45:06.000000Z",
                "ligands": [
                    {
                        "SMILES": "CN1CCCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cncnc5c4)c3c2)C1",
                        "SMILES_state": 1,
                        "NLL": 5.95,
                        "valid": true,
                        "canonical_smiles": "CN1CCCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cncnc5c4)c3c2)C1",
                        "mw": 427.5120000000002,
                        "logp": 3.1636000000000006,
                        "tpsa": 87.14,
                        "hbd": 1,
                        "hba": 7,
                        "rot_bonds": 5,
                        "qed": 0.5234216428527144,
                        "sa_score": 2.7645745083533857,
                        "pred_pAff_mean": 10.726750373840332,
                        "docking_score": -9.17,
                        "docking_status": "completed",
                        "rank": 1
                    },
                    {
                        "SMILES": "CN1CCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cncnc5c4)c3c2)CC1",
                        "SMILES_state": 1,
                        "NLL": 5.14,
                        "valid": true,
                        "canonical_smiles": "CN1CCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cncnc5c4)c3c2)CC1",
                        "mw": 427.5120000000002,
                        "logp": 2.8160000000000007,
                        "tpsa": 87.14,
                        "hbd": 1,
                        "hba": 7,
                        "rot_bonds": 5,
                        "qed": 0.5240018720180243,
                        "sa_score": 2.567665997715085,
                        "pred_pAff_mean": 10.2472505569458,
                        "docking_score": -9.04,
                        "docking_status": "completed",
                        "rank": 2
                    },
                    {
                        "SMILES": "CN1CCCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cnccc5c4)c3c2)C1",
                        "SMILES_state": 1,
                        "NLL": 5.85,
                        "valid": true,
                        "canonical_smiles": "CN1CCCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cnccc5c4)c3c2)C1",
                        "mw": 426.5240000000002,
                        "logp": 3.768600000000002,
                        "tpsa": 74.25,
                        "hbd": 1,
                        "hba": 6,
                        "rot_bonds": 5,
                        "qed": 0.523710400747619,
                        "sa_score": 2.669701840595609,
                        "pred_pAff_mean": 9.5870943069458,
                        "docking_score": -9.18,
                        "docking_status": "completed",
                        "rank": 3
                    },
                    {
                        "SMILES": "CN1CCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cnccc5c4)c3c2)CC1",
                        "SMILES_state": 1,
                        "NLL": 7.45,
                        "valid": true,
                        "canonical_smiles": "CN1CCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cnccc5c4)c3c2)CC1",
                        "mw": 426.5240000000002,
                        "logp": 3.421000000000002,
                        "tpsa": 74.25,
                        "hbd": 1,
                        "hba": 6,
                        "rot_bonds": 5,
                        "qed": 0.5271519273668187,
                        "sa_score": 2.4727933299573124,
                        "pred_pAff_mean": 9.139262199401855,
                        "docking_score": -9.59,
                        "docking_status": "completed",
                        "rank": 4
                    },
                    {
                        "SMILES": "CCN1CCN(CCC(=O)Nc2ccc3nncc(-c4cccc(F)c4)c3c2)C1",
                        "SMILES_state": 1,
                        "NLL": 7.31,
                        "valid": true,
                        "canonical_smiles": "CCN1CCN(CCC(=O)Nc2ccc3nncc(-c4cccc(F)c4)c3c2)C1",
                        "mw": 393.4660000000002,
                        "logp": 3.359500000000001,
                        "tpsa": 61.36,
                        "hbd": 1,
                        "hba": 5,
                        "rot_bonds": 6,
                        "qed": 0.6958924258182049,
                        "sa_score": 2.483388547894851,
                        "pred_pAff_mean": 8.095303535461426,
                        "docking_score": -8.79,
                        "docking_status": "completed",
                        "rank": 5
                    }
                ]
            },
            {
                "id": 1,
                "user_id": 1,
                "job_id": "gen_20260602_161105_95a4f3",
                "status": "completed",
                "preset": "egfr_generator",
                "num_molecules": 5,
                "return_top_k": 5,
                "docking_mode": "off",
                "dock_top_k": 0,
                "summary": {
                    "num_requested": 5,
                    "num_generated": 5,
                    "num_valid": 5,
                    "num_returned": 5,
                    "num_docked": 0
                },
                "files": {
                    "csv": {
                        "filename": "generated_results.csv",
                        "relative_url": "/files/jobs/gen_20260602_161105_95a4f3/generated_results.csv",
                        "download_url": "https://abcd-1234.ngrok-free.app/files/jobs/gen_20260602_161105_95a4f3/generated_results.csv"
                    },
                    "json": {
                        "filename": "generated_results.json",
                        "relative_url": "/files/jobs/gen_20260602_161105_95a4f3/generated_results.json",
                        "download_url": "https://abcd-1234.ngrok-free.app/files/jobs/gen_20260602_161105_95a4f3/generated_results.json"
                    }
                },
                "created_at": "2026-06-02T16:11:02.000000Z",
                "updated_at": "2026-06-02T16:45:52.000000Z",
                "ligands": [
                    {
                        "SMILES": "CN1CCCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cncnc5c4)c3c2)C1",
                        "SMILES_state": 1,
                        "NLL": 5.97,
                        "valid": true,
                        "canonical_smiles": "CN1CCCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cncnc5c4)c3c2)C1",
                        "mw": 427.5120000000002,
                        "logp": 3.1636000000000006,
                        "tpsa": 87.14,
                        "hbd": 1,
                        "hba": 7,
                        "rot_bonds": 5,
                        "qed": 0.5234216428527144,
                        "sa_score": 2.7645745083533857,
                        "pred_pAff_mean": 10.726750373840332,
                        "docking_score": null,
                        "docking_status": "not_run",
                        "rank": 1
                    },
                    {
                        "SMILES": "O=C(CCN1CCCCC1)Nc1ccc2nncc(-c3ccc4cncnc4c3)c2c1",
                        "SMILES_state": 1,
                        "NLL": 8.21,
                        "valid": true,
                        "canonical_smiles": "O=C(CCN1CCCCC1)Nc1ccc2nncc(-c3ccc4cncnc4c3)c2c1",
                        "mw": 412.4970000000002,
                        "logp": 4.054500000000003,
                        "tpsa": 83.9,
                        "hbd": 1,
                        "hba": 6,
                        "rot_bonds": 5,
                        "qed": 0.532283447216262,
                        "sa_score": 2.5088336367127653,
                        "pred_pAff_mean": 9.917120933532717,
                        "docking_score": null,
                        "docking_status": "not_run",
                        "rank": 2
                    },
                    {
                        "SMILES": "CNCCCC(=O)Nc1ccc2nncc(-c3cccc4cncnc34)c2c1",
                        "SMILES_state": 1,
                        "NLL": 8.3,
                        "valid": true,
                        "canonical_smiles": "CNCCCC(=O)Nc1ccc2nncc(-c3cccc4cncnc34)c2c1",
                        "mw": 372.4320000000001,
                        "logp": 3.178100000000001,
                        "tpsa": 92.69,
                        "hbd": 2,
                        "hba": 6,
                        "rot_bonds": 6,
                        "qed": 0.5051251070006001,
                        "sa_score": 2.5602974133305807,
                        "pred_pAff_mean": 9.24955940246582,
                        "docking_score": null,
                        "docking_status": "not_run",
                        "rank": 3
                    },
                    {
                        "SMILES": "CCC(=O)Nc1cccc(-c2cnnc3ccc(NC(=O)CCN4CCCN(C)C4)cc23)c1",
                        "SMILES_state": 1,
                        "NLL": 6.61,
                        "valid": true,
                        "canonical_smiles": "CCC(=O)Nc1cccc(-c2cnnc3ccc(NC(=O)CCN4CCCN(C)C4)cc23)c1",
                        "mw": 446.5550000000004,
                        "logp": 3.568900000000003,
                        "tpsa": 90.45999999999998,
                        "hbd": 2,
                        "hba": 6,
                        "rot_bonds": 7,
                        "qed": 0.5767061490837626,
                        "sa_score": 2.580768170904193,
                        "pred_pAff_mean": 8.717927932739258,
                        "docking_score": null,
                        "docking_status": "not_run",
                        "rank": 4
                    },
                    {
                        "SMILES": "CCC(=O)Nc1cccc(-c2cnnc3ccc(NC(=O)CCCN)cc23)c1",
                        "SMILES_state": 1,
                        "NLL": 7.34,
                        "valid": true,
                        "canonical_smiles": "CCC(=O)Nc1cccc(-c2cnnc3ccc(NC(=O)CCCN)cc23)c1",
                        "mw": 377.4480000000002,
                        "logp": 3.322700000000001,
                        "tpsa": 110,
                        "hbd": 3,
                        "hba": 5,
                        "rot_bonds": 7,
                        "qed": 0.5849389128589325,
                        "sa_score": 2.2821504292780475,
                        "pred_pAff_mean": 7.681546688079834,
                        "docking_score": null,
                        "docking_status": "not_run",
                        "rank": 5
                    }
                ]
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

## ligands export

### POST `/api/ai/ligands/export`

**Authentication:** Bearer token required

**Input Options:**

| Name     | Type   | Description                            |
| -------- | ------ | -------------------------------------- |
| `smiles` | string | SMILES strings                         |
| `format` | string | Supported formats: pdbqt or pdb or sdf |

**Example:**

```bash
curl -X POST http://localhost:8080/api/ai/ligands/export \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"smiles": "CCO", "format": "pdbqt"}'
```

**Response:**

```json
{
    "success": true,
    "data": {
        "job_id": "lig_20260602_184722_a19639",
        "status": "completed",
        "smiles": "CCO",
        "format": "pdbqt",
        "filename": "ligand_3d.pdbqt",
        "created_at": "2026-06-02 18:47:22"
    }
}
```

---

## ligands download files or Generation files

### GET `/api/ai/files/{job_id}/{filename}`

**Authentication:** Bearer token required

**Example ligands download:**

```bash
curl -X GET http://localhost:8080/api/ai/files/lig_20260602_181751_0227c4/ligand_3d.pdbqt \
  -H "Authorization: Bearer {token}"
```

**Response:**

```pdbqt
REMARK SMILES CCC
REMARK SMILES IDX 1 1 2 2 3 3
REMARK H PARENT
ROOT
ATOM      1  C   UNL     1       1.223  -0.144  -0.355  1.00  0.00     0.003 C
ATOM      2  C   UNL     1       0.031  -0.177   0.596  1.00  0.00    -0.007 C
ATOM      3  C   UNL     1      -1.247   0.279  -0.101  1.00  0.00     0.003 C
ENDROOT
TORSDOF 0

```

**Example Generation files download:**

```bash
curl -X GET http://127.0.0.1:8000/api/ai/files/gen_20260602_185419_15fc7b/generated_results.csv \
  -H "Authorization: Bearer {token}"
```

**Response:**

```csv
SMILES,SMILES_state,NLL,valid,canonical_smiles,mw,logp,tpsa,hbd,hba,rot_bonds,qed,sa_score,pred_pAff_mean,docking_score,docking_status
CN1CCCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cncnc5c4)c3c2)C1,1,4.14,True,CN1CCCN(CCC(=O)Nc2ccc3nncc(-c4ccc5cncnc5c4)c3c2)C1,427.5120000000002,3.1636000000000006,87.14,1,7,5,0.5234216428527144,2.7645745083533857,10.726750373840332,-9.16,completed
O=C(CCN1CCCC1)Nc1ccc2c(Nc3cccc(Cl)c3)ncnc2c1,1,6.06,True,O=C(CCN1CCCC1)Nc1ccc2c(Nc3cccc(Cl)c3)ncnc2c1,395.89400000000006,4.451200000000003,70.15,2,5,6,0.6447750051881624,2.11252799494409,8.844084739685059,-8.23,completed
CN1CCC2C1CCN2CCC(=O)Nc1ccc2ncnc(Nc3ccc4ncncc4c3)c2c1,1,22.74,True,CN1CCC2C1CCN2CCC(=O)Nc1ccc2ncnc(Nc3ccc4ncncc4c3)c2c1,468.5650000000001,3.4236000000000013,99.17,2,8,6,0.4441562846144389,3.462932738106919,8.693540573120117,-9.2,completed
```

---

## MD Simulation API

Molecular Dynamics simulation of protein-ligand complexes via an external OpenMM service. All endpoints require authentication.

### POST `/api/md-simulation/process`

Submit a new MD simulation job.

**Authentication:** Bearer token required

**Content-Type:** `multipart/form-data`

**Required fields:**

| Field | Type | Description |
|---|---|---|
| `protein` | file | Protein PDB file |
| `ligand` | file | Ligand PDB file |

**Optional fields:**

| Field | Type | Default | Description |
|---|---|---|---|
| `force_field` | string | `ff19SB` | `ff19SB` or `ff14SB` |
| `net_charge` | integer | `0` | Ligand net formal charge |
| `box_size` | float | `12.0` | Solvation box size (Å) |
| `ion_type` | string | `NaCl` | `NaCl` or `KCl` |
| `salt_conc` | float | `0.15` | Salt concentration (M) |
| `remove_waters` | boolean | `true` | Strip crystal waters |
| `add_hydrogens` | boolean | `true` | Add H to ligand |
| `equil_time_ns` | float | `5.0` | Equilibration time (ns) |
| `sim_time_ns` | float | `0.1` | Production time per stride (ns) |
| `n_strides` | integer | `1` | Number of production strides |
| `temperature_k` | float | `298.0` | Temperature (K) |
| `pressure_bar` | float | `1.0` | Pressure (bar) |
| `dt_fs` | integer | `2` | Integration timestep (fs) |

**Example:**

```bash
curl -X POST /api/md-simulation/process \
  -H "Authorization: Bearer {token}" \
  -F "protein=@protein.pdb" \
  -F "ligand=@ligand.pdb" \
  -F "sim_time_ns=1.0" \
  -F "temperature_k=310"
```

**Response (202 Accepted):**

```json
{
    "success": true,
    "message": "MD Simulation job submitted successfully",
    "data": {
        "remote_job_id": "a1b2c3d4",
        "status": "processing",
        "created_at": "2026-06-22 12:00:00"
    }
}
```

---

### GET `/api/md-simulation/status/{remoteJobId}`

Poll job status. Syncs the local status from the remote service on each call.

**Authentication:** Bearer token required

**Response (200 OK — processing):**

```json
{
    "success": true,
    "message": "Status retrieved",
    "data": {
        "remote_job_id": "a1b2c3d4",
        "status": "processing",
        "remote_status": "Step 2/7 — Building GAFF2 topology and solvated system",
        "protein": "4w52.pdb",
        "ligand": "ligand.pdb",
        "result_meta": null,
        "analysis_meta": null,
        "error_message": null,
        "created_at": "2026-06-22 12:00:00"
    }
}
```

**Response (200 OK — completed):**

```json
{
    "success": true,
    "message": "Status retrieved",
    "data": {
        "remote_job_id": "a1b2c3d4",
        "status": "completed",
        "remote_status": "Success: MD Pipeline Completed",
        "protein": "4w52.pdb",
        "ligand": "ligand.pdb",
        "result_meta": {
            "download_url": "/download/a1b2c3d4",
            "download_analysis_url": "/download_analysis/a1b2c3d4"
        },
        "analysis_meta": null,
        "error_message": null,
        "created_at": "2026-06-22 12:00:00"
    }
}
```

**Response (200 OK — failed):**

```json
{
    "success": true,
    "message": "Status retrieved",
    "data": {
        "remote_job_id": "a1b2c3d4",
        "status": "failed",
        "remote_status": "Failed: antechamber failed...",
        "error_message": "antechamber failed: unable to assign parameters",
        ...
    }
}
```

---

### GET `/api/md-simulation/download/{remoteJobId}`

Download the simulation results ZIP (streamed from the remote service).

**Authentication:** Bearer token required

**Response (200 OK):** Binary ZIP attachment — `{job_id}_Results.zip`

---

### POST `/api/md-simulation/analyze/{remoteJobId}`

Run post-simulation analysis (RMSD, RMSF, RoG, PCA, etc.) on a completed job.

**Authentication:** Bearer token required

**Content-Type:** `application/json`

**Optional body fields:**

| Field | Type | Default | Description |
|---|---|---|---|
| `rmsd_mask` | string | `@CA` | Atom selection mask for RMSD |
| `cc_mask` | string | `@CA` | Atom selection mask for cross-correlation |
| `skip` | integer | `1` | Frame stride for analysis |
| `dpi` | integer | `300` | DPI for output plots |
| `threshold` | float | `0.3` | ProLIF interaction threshold |

**Example:**

```bash
curl -X POST /api/md-simulation/analyze/a1b2c3d4 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"rmsd_mask": "@CA", "dpi": 150}'
```

**Response (200 OK):**

```json
{
    "success": true,
    "message": "Analysis triggered successfully",
    "data": {
        "download_url": "/download_analysis/a1b2c3d4",
        "outputs": ["rmsd", "rmsf", "radgyr", "2d_rmsd", "pca", "cross_corr", "interaction_e", "prolif"]
    }
}
```

---

### GET `/api/md-simulation/download-analysis/{remoteJobId}`

Download the analysis results ZIP (streamed from the remote service).

**Authentication:** Bearer token required

**Response (200 OK):** Binary ZIP attachment — `{job_id}_Analysis.zip`

---

### GET `/api/md-simulation/history`

List all MD simulation jobs for the authenticated user.

**Authentication:** Bearer token required

**Query Parameters:**

| Parameter | Type | Default | Description |
|---|---|---|---|
| `per_page` | integer | `15` | Results per page |

**Response (200 OK):**

```json
{
    "success": true,
    "message": "MD Simulation history retrieved",
    "data": {
        "results": [
            {
                "remote_job_id": "x9y8z7w6",
                "status": "completed",
                "input_params": {
                    "sim_time_ns": "5.0",
                    "temperature_k": "310"
                },
                "protein_original_name": "1ake.pdb",
                "ligand_original_name": "stl.pdb",
                "result_meta": {
                    "download_url": "/download/x9y8z7w6",
                    "download_analysis_url": "/download_analysis/x9y8z7w6"
                },
                "analysis_meta": {
                    "download_url": "/download_analysis/x9y8z7w6",
                    "outputs": ["rmsd", "rmsf", "radgyr"]
                },
                "error_message": null,
                "created_at": "2026-06-22 14:30:00",
                "updated_at": "2026-06-22 18:45:00"
            }
        ],
        "pagination": {
            "currentPage": 1,
            "totalPages": 1,
            "totalResults": 3,
            "perPage": 15,
            "hasNextPage": false,
            "hasPrevPage": false
        }
    }
}
```

---

'''

## Error Handling

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

| Code    | Meaning             | Example                  |
| ------- | ------------------- | ------------------------ |
| **200** | OK                  | Request successful       |
| **201** | Created             | Resource created         |
| **202** | Accepted            | Async job queued         |
| **400** | Bad Request         | Invalid parameters       |
| **401** | Unauthorized        | Missing/invalid token    |
| **403** | Forbidden           | Insufficient permissions |
| **404** | Not Found           | Resource not found       |
| **422** | Unprocessable       | Validation failed        |
| **429** | Too Many Requests   | Rate limit exceeded      |
| **500** | Server Error        | Internal error           |
| **502** | Bad Gateway         | Upstream service down    |
| **503** | Service Unavailable | System maintenance       |

### Common Error Codes

| Code                    | Description                             |
| ----------------------- | --------------------------------------- |
| `INVALID_SMILES`        | Given SMILES string is invalid          |
| `JOB_NOT_FOUND`         | Job ID doesn't exist                    |
| `SERVICE_UNAVAILABLE`   | AI service not responding               |
| `AUTHENTICATION_FAILED` | Invalid credentials                     |
| `INSUFFICIENT_QUOTA`    | User quota exceeded                     |
| `UNSUPPORTED_OPERATION` | Feature not available                   |
| `QUOTA_EXCEEDED`        | LLM API daily quota reached             |
| `THREAD_NOT_FOUND`      | Thread ID doesn't exist or unauthorized |
| `JOB_NOT_COMPLETE`      | Results requested before job completion |
| `CSV_TOO_LARGE`         | Exceeds 100 row limit                   |
| `MISSING_SMILES`        | smiles field not provided               |

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

## Rate Limiting

### Rate Limit Headers

All responses include rate limit information:

```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 987
X-RateLimit-Reset: 1716954645
```

### Limits by Endpoint Category

| Category    | Limit         | Window     |
| ----------- | ------------- | ---------- |
| **Auth**    | 10 requests   | 15 minutes |
| **AI Jobs** | 100 requests  | 1 hour     |
| **Search**  | 500 requests  | 1 hour     |
| **General** | 1000 requests | 1 hour     |

### AI Agent Rate Limits

| Endpoint           | Limit         | Window                   |
| ------------------ | ------------- | ------------------------ |
| `/chat`            | 20 requests   | Per day (upstream quota) |
| `/analyze/smiles`  | 20 requests   | Per day (upstream quota) |
| `/analyze/compare` | 20 requests   | Per day (upstream quota) |
| `/analyze/docking` | 20 requests   | Per day (upstream quota) |
| `/csv/upload`      | 10 uploads    | Per day                  |
| All others         | 1000 requests | 1 hour                   |

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

## Integration Examples

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
const axios = require("axios");

async function checkSystemHealth() {
    const baseURL = "http://localhost:8080/api";

    try {
        // Check overall system
        const healthResponse = await axios.get(`${baseURL}/health`);
        console.log("System Health:", healthResponse.data);

        // Check all AI services
        const servicesResponse = await axios.get(
            `${baseURL}/ai-services/health`,
        );

        const allHealthy = servicesResponse.data.success;
        const services = servicesResponse.data.services;

        console.log("\\nAI Services Status:");
        Object.entries(services).forEach(([name, status]) => {
            const indicator = status.status === "healthy" ? "✓" : "✗";
            console.log(`${indicator} ${name}: ${status.status}`);
        });

        return {
            healthy: allHealthy,
            timestamp: new Date(),
        };
    } catch (error) {
        console.error("Health check failed:", error.message);
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

## Support

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
