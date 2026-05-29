# 📡 AILIXIR API Reference

**Version:** 2.0 | **Last Updated:** May 29, 2026 | **Status:** Production Ready

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
- [Chemical Search Endpoints](#chemical-search-endpoints)
- [Docking Endpoints](#docking-endpoints)
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

## 🔗 Docking Endpoints

### Submit Docking Job

```http
POST /docking/submit
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json

{
  "protein_id": "1ABC",
  "ligand_smiles": "CC(=O)Oc1ccccc1C(=O)O",
  "binding_pocket": {
    "center_x": 10.5,
    "center_y": 20.3,
    "center_z": 15.7
  }
}
```

**Response (202 Accepted):**
```json
{
  "success": true,
  "docking_id": "dock-abc-123-def",
  "status": "queued",
  "estimated_time_seconds": 300
}
```

### Convert SMILES

Convert SMILES string to molecular structure format:

```http
POST /docking/convert-smiles
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json

{
  "smiles": "CC(=O)Oc1ccccc1C(=O)O",
  "format": "pdb"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "format": "pdb",
  "data": "HEADER    ...",
  "file_url": "https://cdn.example.com/structures/temp-abc-123.pdb"
}
```

### Get Docking Status

```http
GET /docking/status/{docking_id}
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```json
{
  "success": true,
  "docking_id": "dock-abc-123-def",
  "status": "completed",
  "results": {
    "binding_affinity": -7.5,
    "rmsd": 1.23,
    "docking_time_seconds": 245
  }
}
```

### Download Docking Results

```http
GET /docking/download/{docking_id}
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```
Content-Type: application/zip
Content-Disposition: attachment; filename="docking-results.zip"

# Contains: poses.pdb, scoring.txt, ligand.pdbqt
```

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
GET /awards?page=1&per_page=20
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Nobel Prize in Chemistry 2025",
      "description": "...",
      "year": 2025
    }
  ],
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 150
    }
  }
}
```

### Get Award Details

```http
GET /awards/{award_id}
```

### Get Award Scientists

```http
GET /awards/{award_id}/scientists
```

### List Scientists

```http
GET /scientists?page=1&per_page=50
```

### Get Scientist Details

```http
GET /scientists/{scientist_id}
```

### Get Scientist Awards

```http
GET /scientists/{scientist_id}/awards
```

---

## 📰 News Endpoints

### Get News Feed

```http
GET /news?page=1&per_page=20&category=chemistry
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "New AI Model for Drug Discovery",
      "category": "chemistry",
      "published_at": "2026-05-29T10:00:00Z",
      "image_url": "https://..."
    }
  ]
}
```

### Refresh News

```http
GET /news/refresh
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### Get News Categories

```http
GET /news/categories
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### Save Article

```http
POST /news/{article_id}/save
Authorization: Bearer YOUR_ACCESS_TOKEN
```

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

### Get Saved Articles

```http
GET /news/saved
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### Unsave Article

```http
DELETE /news/saved/{saved_article_id}
Authorization: Bearer YOUR_ACCESS_TOKEN
```

---

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
JOB_RESPONSE=$(curl -s -X POST "$BASE_URL/ai/run" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"job_type\": \"admet_prediction\", \"parameters\": {\"smiles\": \"$SMILES\"}}")

JOB_ID=$(echo $JOB_RESPONSE | jq -r '.job_id')
echo "Job ID: $JOB_ID"

# Step 2: Poll for completion
echo "2. Waiting for results..."
while true; do
  STATUS_RESPONSE=$(curl -s -X GET "$BASE_URL/ai/status/$JOB_ID" \
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
curl -X GET "$BASE_URL/ai/download/full/$JOB_ID" \
  -H "Authorization: Bearer $TOKEN" \
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
    
    console.log('\nAI Services Status:');
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

---

## 📞 Support

For API issues or questions:

1. **Check Logs:** `docker compose logs -f laravel`
2. **Test Endpoint:** Use provided curl examples
3. **Version:** Confirm you're using latest API version
4. **Rate Limits:** Check `X-RateLimit-*` headers
5. **Contact:** pharma-support@ailixir.io

---

**Created:** May 29, 2026 | **Version:** 2.0.0 | **Last Updated:** May 29, 2026
