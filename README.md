# AILIXIR BackEnd — Production README

Status: Production-ready documentation (May 2026)

Overview
--------
AILIXIR is a modular backend for AI-driven drug discovery. It combines a Laravel-based orchestration API and multiple AI microservices implemented in Python (FastAPI). The system provides:

- ADMET inference (MPNN models)
- Drug repurposing pipeline (DeepPurpose)
- Chemical retrieval & RAG (FAISS + LLM integration)
- Laravel API for authentication, orchestration and long-running jobs

This README provides a consolidated, production-focused reference covering architecture, setup (Docker and local), how to run services, environment variables, API usage, project layout, and troubleshooting.

Architecture
------------
High-level components (see `docker-compose.yml`):

- `laravel` — Laravel application (PHP) serving orchestration endpoints and web API. Default host port: `8080` → container `8000`.
- `queue` — Laravel queue worker (PHP) for background jobs.
- `mysql` — MariaDB database.
- `admet` — ADMET inference service (FastAPI). Default host port: `8002` → container `8000`.
- `drug-repurposing` — Drug repurposing API (FastAPI). Default host port: `8001` → container `8000`.
- `chemical-rag` — Chemical retrieval + RAG service (FastAPI + FAISS). Default host port: `5000` → container `5000`.

Services run inside a single Docker network (`ailixir`) and share volumes for persistence (MySQL data, Laravel storage, chemical-rag data).

Quick Docker Start (production-like)
-----------------------------------
Prerequisites

- Docker & Docker Compose (v2.x)
- 8GB+ RAM recommended for local multi-service runs (some AI services require more memory)

Commands

```bash
# from repository root
docker compose build --parallel
docker compose up -d

# check containers
docker compose ps

# watch logs
docker compose logs -f laravel
docker compose logs -f admet
```

Service ports (defaults are set via environment variables in `docker-compose.yml`):

- Laravel: http://localhost:8080 (container 8000)
- ADMET: http://localhost:8002 (container 8000)
- Drug Repurposing: http://localhost:8001 (container 8000)
- Chemical RAG: http://localhost:5000 (container 5000)

Local (non-container) development
----------------------------------
General guidance: each AI service has its own requirements and a `requirements.txt`. Use virtual environments, install dependencies, and run with `uvicorn`.

Laravel (local)

1. Install PHP 8.1+/8.3 and Composer.
2. Copy `docker/laravel.env` to `.env` and set secrets (do not commit `.env`).
3. Install PHP dependencies:

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8000
```

ADMET inference (local)

```bash
cd ai_apps/ADMIT/admet_inference
python -m venv .venv
source .venv/bin/activate    # Windows: .venv\Scripts\activate
pip install -r requirements.txt
# ensure pretrained model files are present under models/ or configured path
uvicorn app.main:app --host 0.0.0.0 --port 8000 --reload
```

Drug Repurposing (local)

```bash
cd ai_apps/Drug\ Reporposing
python -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --host 0.0.0.0 --port 8000 --reload
```

Chemical RAG (local)

```bash
cd ai_apps/chemical-rag-system/chemical-rag-system
python -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
# First run may ingest/build FAISS index (minutes); run_server.py performs auto-detection
python run_server.py
```

How to run individual services (Docker)
-------------------------------------
You can start services individually if you don't need the full stack. Example:

```bash
docker compose up -d admet
docker compose logs -f admet

docker compose up -d drug-repurposing
docker compose logs -f drug-repurposing
```

Environment variables
---------------------
Where to look:

- `docker/laravel.env` — Laravel environment template used by the container.
- Service-specific config files: check `ai_apps/*` directories (examples: `app/config.py`, `config.py`, `docker/` folders).

Common variables (examples — verify per-service files):

```env
# Laravel
APP_NAME=AILIXIR
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=ailixir
DB_USERNAME=ailixir
DB_PASSWORD=secret

# Service host mapping (docker-compose overrides via env)
LARAVEL_HTTP_PORT=8080
ADMET_HTTP_PORT=8002
DRUG_REPURPOSING_HTTP_PORT=8001
CHEMICAL_RAG_HTTP_PORT=5000

# Service URLs used by Laravel (internal network names/ports)
ADMET_AI_URL=http://admet:8000
DRUG_REPURPOSING_URL=http://drug-repurposing:8000
CHEMICAL_RAG_URL=http://chemical-rag:5000
```

Note: do NOT commit secret values. Use `.env` files for local development and secret stores in production.

APIs & Common Endpoints
-----------------------
Each microservice exposes documentation (FastAPI auto-generated docs) and health endpoints. Examples below assume default host ports.

- Laravel API (orchestration)
  - Base: `http://localhost:8080`
  - Routes: API routes are defined under `routes/` (check [routes/api.php](routes/api.php)).

- ADMET Service
  - Health: `GET http://localhost:8002/health`
  - Docs (Swagger): `http://localhost:8002/docs`
  - Predict endpoints: see `ai_apps/ADMIT/admet_inference/README.md` for exact paths (batch and single predictions).

- Drug Repurposing
  - Health: `GET http://localhost:8001/health`
  - Docs (Swagger): `http://localhost:8001/docs`
  - Main pipeline endpoints: `/api/v1/disease-targets`, `/api/v1/screen`, `/api/v1/drug-library` (see service README).

- Chemical RAG
  - Health: `GET http://localhost:5000/health`
  - Retrieval endpoint: `POST /search/retrieval-only`
  - Full RAG endpoint: `POST /search/full-rag` (with `explain=true` for LLM explanations)
  - Docs: visit `http://localhost:5000/docs` when running.

Project layout (short)
----------------------
Top-level layout (high-level):

```
AILIXIR_BackEnd/
├── app/                      # Laravel app (controllers, models, jobs, providers)
├── ai_apps/                  # AI microservices (ADMIT, Drug Reporposing, chemical-rag-system)
├── config/                   # Project config
├── database/                 # Migrations and seeders
├── docker/                   # Docker helper files and env templates
├── routes/                   # Laravel route definitions
├── docker-compose.yml       # Service orchestration
├── Dockerfile                # Laravel Dockerfile used by compose
└── README.md                 # This file
```

Where to find microservice documentation:

- `ai_apps/ADMIT/admet_inference/README.md` — ADMET training/inference and deployment notes
- `ai_apps/Drug Reporposing/README.md` — Drug Repurposing quick start, API and pipeline
- `ai_apps/chemical-rag-system/README.md` — Chemical RAG architecture, endpoints and FAISS details

Testing & CI
------------
- Laravel tests: `docker compose exec laravel php artisan test` or `vendor/bin/phpunit` locally.
- AI service tests: check `ai_apps/*/test_*.py` or `test_*.py` files. Example: `ai_apps/Drug Reporposing/test_api.py`.

Troubleshooting
---------------
- Container fails to start
  - Check logs: `docker compose logs -f <service>`
  - Rebuild with no cache: `docker compose build --no-cache --parallel`
- Database migrations fail
  - Ensure `mysql` is healthy and accessible: `docker compose ps` and `docker compose logs -f mysql`
  - Run migrations manually: `docker compose exec laravel php artisan migrate`
- AI service model loading errors
  - Confirm model files exist under service `models/` or configured path.
  - Inspect service logs to see missing-file or package errors.
- Python dependency / incompatible versions
  - Use the `requirements.txt` in each `ai_apps/*` folder.
  - For GPU vs CPU PyTorch/torchaudio compatibility, prefer pinned versions in the service `requirements.txt`.

Security & Deployment notes
--------------------------
- Use environment secret stores (Kubernetes Secrets, Vault, or encrypted CI secrets) in production instead of `.env` files.
- Run Laravel behind an HTTPS-terminating proxy (Nginx) and restrict access to internal AI services.
- Limit container resources in production orchestrators (Docker Swarm, Kubernetes) per `docker-compose.yml` `deploy.resources` suggestions.

Contributing & Changes
----------------------
- Keep service-specific documentation inside each `ai_apps/*` README. Changes to model code, endpoints or Dockerfiles should update the corresponding README.
- Add integration tests when adding new endpoints and update `QUICK_START.md` and `PRODUCTION_GUIDE.md` if deployment changes.

Where to look next
------------------
- ADMET inference: `ai_apps/ADMIT/admet_inference/README.md`
- Drug repurposing: `ai_apps/Drug Reporposing/README.md`
- Chemical RAG: `ai_apps/chemical-rag-system/README.md`
- Docker helper and example env: `docker/laravel.env`

- Architecture diagrams and per-component details: `ARCHITECTURE.md`

Contact / Support
-----------------
If you need more detail, I can:

1. Audit and standardize all `ai_apps/*` README files (make them consistent and production-ready).
2. Generate a short `QUICK_START.md` with copy-paste commands for a new developer.
3. Create example `.env.example` files for each service.

Tell me which of the three you'd like next and I'll proceed.

License
-------
See the repository `LICENSE` file for licensing information.
# 🚀 AILIXIR Backend - Integrated AI Drug Discovery Platform

**Status**: ✅ Production Ready | **Version**: 2.0.0 | **Last Updated**: 2026

A comprehensive microservices backend combining **Laravel**, **FastAPI**, and **PyTorch** for AI-driven drug discovery, including ADMET prediction, drug repurposing, and chemical retrieval systems.

---

## 📋 Quick Navigation

- **🏃 [Quick Start](./QUICK_START.md)** - Get running in 5 minutes
- **🐳 [Docker Setup](./DOCKER.md)** - Containerized deployment guide
- **🔧 [Production Guide](./PRODUCTION_GUIDE.md)** - Deployment & scaling
- **🧪 [API Testing](./ai_apps/Drug%20Reporposing/API_TESTING_GUIDE.md)** - Test all endpoints

---

## 🎯 System Overview

AILIXIR is an integrated backend combining four specialized AI services:

| Service | Purpose | Stack | Status |
|---------|---------|-------|--------|
| **[ADMET Inference](./ai_apps/ADMIT/admet_inference/README.md)** | Drug ADMET property prediction | PyTorch + FastAPI (Python 3.11) | ✅ Ready |
| **[Drug Repurposing](./ai_apps/Drug%20Reporposing/README.md)** | Identify therapeutic uses for existing drugs | DeepPurpose + FastAPI (Python 3.10) | ✅ Ready |
| **[Chemical RAG](./ai_apps/chemical-rag-system/README.md)** | Retrieve & generate chemical compounds | RDKit + FAISS + FastAPI (Python 3.11) | ✅ Ready |
| **[Laravel Backend](./app)** | REST API, authentication, job queue (PHP 8.3) | Laravel + Queue Worker | ✅ Ready |

---

## 🐳 Docker Architecture

All services run in isolated containers orchestrated by Docker Compose:

```yaml
Services:
├── laravel          (PHP 8.3-cli) - REST API & web server
├── queue            (PHP 8.3-cli) - Background job processor  
├── admet            (Python 3.11-slim) - ADMET predictions
├── drug-repurposing (Python 3.10-slim) - Drug repurposing AI
└── chemical-rag     (Python 3.11-slim) - Chemical retrieval
```

### 🔧 Recent Docker Fixes (v2.0)

We've resolved the following issues identified in CI/CD:

**✅ Fixed Issue #1: PyTorch Version Incompatibility**
- **Problem**: `torchaudio==0.15.2` did not exist on PyPI (version gap between 0.13.1 → 2.0.1)
- **Error**: "ERROR: Could not find a version that satisfies the requirement torchaudio==0.15.2"
- **Solution**: Updated to `torchaudio==2.0.1` (compatible with torch==2.0.1 and Python 3.10)
- **File**: `ai_apps/Drug Reporposing/requirements.txt` (lines 24-26)
- **Status**: ✅ Verified in container builds

**✅ Fixed Issue #2: Dockerfile Casing Warnings**
- **Problem**: FromAsCasing linter warnings on mixed-case `as`/`FROM` keywords
- **Solution**: Updated multi-stage build syntax to `FROM python:X-slim AS builder`
- **Files Updated**:
  - `ai_apps/ADMIT/admet_inference/Dockerfile` (line 4)
  - `ai_apps/Drug Reporposing/docker/Dockerfile` (line 1)
- **Status**: ✅ All Dockerfiles now pass style checks

---

## 📦 Installation & Deployment

### Prerequisites
- Docker & Docker Compose (v2.0+)
- 4GB+ available disk space
- Git (for cloning)

### Quick Start (5 minutes)

```bash
# Clone repository
git clone <repo-url>
cd AILIXIR_BackEnd

# Build all containers (with fixes applied)
docker compose build --parallel

# Start all services
docker compose up -d

# Verify services are running
docker compose ps
```

### Accessing Services

| Service | URL | Purpose |
|---------|-----|---------|
| Laravel API | `http://localhost:8000` | REST API endpoints |
| ADMET API | `http://localhost:8001/docs` | ADMET predictions |
| Drug Repurposing | `http://localhost:8002/docs` | Drug repurposing |
| Chemical RAG | `http://localhost:8003/docs` | Chemical retrieval |

---

### How to run each service

Notes: service ports and exact names are configured in `docker-compose.yml`. The examples below cover the common docker-compose setup and the local (non-container) way to run each service.

- **Laravel (API + Web)**
   - Docker: `docker compose up -d laravel` then `docker compose exec laravel php artisan migrate`.
   - Local: install PHP 8.1+/8.3, Composer deps (`composer install`), configure `.env` (see `docker/laravel.env`), then `php artisan serve --host=0.0.0.0 --port=8000`.

- **ADMET Inference (FastAPI)**
   - Docker: `docker compose up -d admet` (service name may be `admet` or `admet-inference` in compose). Use `docker compose logs -f admet` to watch startup and model loading.
   - Local: from `ai_apps/ADMIT/admet_inference` create a venv, install `-r requirements.txt`, ensure models/ folder exists, then `uvicorn app.main:app --host 0.0.0.0 --port 8001 --reload`.

- **Drug Repurposing (FastAPI)**
   - Docker: `docker compose up -d drug-repurposing` (or the name from `docker-compose.yml`).
   - Local: from `ai_apps/Drug Reporposing` create venv, install `-r requirements.txt`, then `uvicorn app.main:app --host 0.0.0.0 --port 8002 --reload`.

- **Chemical RAG (FastAPI + FAISS)**
   - Docker: `docker compose up -d chemical-rag` (or the service name in compose). On first run the service may ingest/build the FAISS index (minutes) — check logs.
   - Local: from `ai_apps/chemical-rag-system` create venv, `pip install -r requirements.txt`, then `python run_server.py` or `uvicorn app.main:app --host 0.0.0.0 --port 8003`.

If a service is mapped to a different host port in `docker-compose.yml`, use `docker compose ps` to confirm the published ports.

### Environment variables

Where to look:
- Laravel env template: [docker/laravel.env](docker/laravel.env)
- Service-specific `.env` or config files are found in each `ai_apps/*` folder (check `config.py`, `app/config.py`, or service `docker` folders).

Common variables (examples — confirm in each service):
```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=ailixir
DB_USERNAME=root
DB_PASSWORD=secret

AI_ADMET_URL=http://admet:8001
AI_DRUG_REPURPOSING_URL=http://drug-repurposing:8002
AI_CHEMICAL_RAG_URL=http://chemical-rag:8003
```

For local development, prefer using local `.env` files (not checked into git) and the service README in each `ai_apps` folder for any extra keys (e.g., HuggingFace API key, PubChem credentials, or model paths).

## 📚 Service Documentation

Each service has comprehensive documentation:

1. **ADMET Inference** → [Service README](./ai_apps/ADMIT/admet_inference/README.md)
   - MPNN model architecture
   - Batch prediction API
   - Performance benchmarks
   - Troubleshooting guide

2. **Drug Repurposing** → [Service README](./ai_apps/Drug%20Reporposing/README.md)
   - DeepPurpose model specifications
   - Drug-target binding prediction
   - API testing guide
   - Implementation notes

3. **Chemical RAG** → [Service README](./ai_apps/chemical-rag-system/README.md)
   - FAISS-IVF vector index
   - 1M+ compound library
   - Retrieval-augmented generation
   - RDKit chemistry engine

4. **Laravel Backend** → [API Documentation](./routes/api.php)
   - Authentication & authorization
   - Orchestration endpoints
   - Job queue management
   - Database schema

---

## 🗂️ Project Structure

```
AILIXIR_BackEnd/
├── app/                          # Laravel application
│   ├── Http/Controllers/        # API endpoints
│   ├── Models/                  # Database models
│   └── Jobs/                    # Background jobs
├── ai_apps/                     # AI microservices
│   ├── ADMIT/                   # ADMET prediction service
│   ├── Drug Reporposing/        # Drug repurposing pipeline
│   └── chemical-rag-system/     # Chemical retrieval system
├── config/                       # Configuration files
├── database/                     # Migrations & seeders
├── docker/                       # Docker configuration
├── routes/                       # API route definitions
├── storage/                      # Persistent data
├── docker-compose.yml          # Service orchestration
├── Dockerfile                   # Laravel container
└── README.md                    # This file
```

---

## 🚀 Common Tasks

### Build Containers
```bash
docker compose build --parallel
```

### View Logs
```bash
docker compose logs -f <service-name>
# Examples:
docker compose logs -f admet
docker compose logs -f drug-repurposing
docker compose logs -f laravel
```

### Run Migrations
```bash
docker compose exec laravel php artisan migrate
```

### Access Service Shells
```bash
# Laravel shell
docker compose exec laravel php artisan tinker

# Python service shell
docker compose exec admet python
```

### Stop Services
```bash
docker compose down
```

---

## 🧪 Testing

### Run API Tests
```bash
cd ai_apps/Drug\ Reporposing
jupyter notebook api_test_notebook.ipynb
```

### Run Unit Tests
```bash
docker compose exec laravel php artisan test
```

### Verify Container Health
```bash
docker compose ps
# All services should show "Up" status
```

---

## 📊 Known Issues & Resolutions

| Issue | Resolution | Status |
|-------|-----------|--------|
| `torchaudio==0.15.2` not found on PyPI | Updated to `torchaudio==2.0.1` | ✅ Fixed |
| Dockerfile casing warnings (linter) | Changed `as` to `AS` in multi-stage builds | ✅ Fixed |
| Drug Repurposing build timeouts | Added parallel build flag (`--parallel`) | ✅ Fixed |

---

## 🔐 Environment Configuration

Key variables configured in `docker/laravel.env`:

```env
APP_NAME=AILIXIR
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_NAME=ailixir

AI_ADMET_URL=http://admet:8001
AI_DRUG_REPURPOSING_URL=http://drug-repurposing:8002
AI_CHEMICAL_RAG_URL=http://chemical-rag:8003

```

---

## 📖 Additional Resources

- **[Quick Start Guide](./QUICK_START.md)** - 60-second setup
- **[Production Deployment](./PRODUCTION_GUIDE.md)** - Scaling & optimization
- **[Docker Documentation](./DOCKER.md)** - Container configuration
- **[Implementation Summary](./ai_apps/Drug%20Reporposing/IMPLEMENTATION_SUMMARY.md)** - Technical details
- **[API Test Guide](./ai_apps/Drug%20Reporposing/API_TESTING_GUIDE.md)** - Testing endpoints

---

## 🆘 Troubleshooting

### Container Build Failures
```bash
# Clear Docker cache and rebuild
docker compose build --no-cache --parallel

# Check individual service logs
docker compose logs <service-name>
```

### Python Service Issues
- Ensure Python versions match: `python3.10` for Drug Repurposing, `3.11` for others
- Verify virtual environments created: `/opt/venv` inside containers

### API Connection Errors
- Verify all services are running: `docker compose ps`
- Check service health: `docker compose logs <service>`
- Test connectivity: `curl http://localhost:8000/api`

---

## 📄 License

Licensed under the MIT License. See LICENSE file for details.

---

## 👥 Support

For issues, feature requests, or questions:
- Check individual service documentation
- Review Docker logs: `docker compose logs -f`
- Consult [Production Guide](./PRODUCTION_GUIDE.md) for deployment issues
