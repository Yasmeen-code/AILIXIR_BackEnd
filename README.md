# 🧬 AILIXIR — Production-Grade AI Drug Discovery Backend

![Status](https://img.shields.io/badge/Status-Production--Ready-brightgreen?style=flat-square)
![Version](https://img.shields.io/badge/Version-2.0-blue?style=flat-square)
![Python](https://img.shields.io/badge/Python-3.10%2B-blue?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-8.3-indigo?style=flat-square)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=flat-square)

Modular microservices backend combining **Laravel orchestration** and **3 AI services** for scalable drug discovery, ADMET prediction, and chemical similarity search.

---

## 📚 Documentation

Start here for your use case:

| Guide | Purpose |
|-------|---------|
| **[QUICK_START.md](./QUICK_START.md)** | Get running in 5 minutes (Docker) |
| **[ARCHITECTURE.md](./ARCHITECTURE.md)** | System design and data flows |
| **[DOCKER.md](./DOCKER.md)** | Container setup and deployment |
| **[PRODUCTION_GUIDE.md](./PRODUCTION_GUIDE.md)** | Deployment, scaling, monitoring |
| **[TROUBLESHOOTING.md](./TROUBLESHOOTING.md)** | Common issues and fixes |
| **[API_REFERENCE.md](./API_REFERENCE.md)** | Complete endpoint documentation |

**Service READMEs:**
- [ADMET Inference](./ai_apps/ADMIT/README.md) — MPNN drug property prediction
- [Drug Repurposing](./ai_apps/Drug%20Reporposing/README.md) — Virtual screening pipeline
- [Chemical RAG](./ai_apps/chemical-rag-system/README.md) — FAISS-IVF search engine

---

## 📋 Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [Architecture](#architecture)
- [Quick Start](#quick-start)
- [Services](#services)
- [API](#api)
- [Deployment](#deployment)
- [Contributing](#contributing)

---

## 🎯 Overview

**AILIXIR** is an enterprise-grade backend platform for AI-driven drug discovery. It provides:

- **Microservices Architecture** — Independent AI services (Python/FastAPI) + orchestration (Laravel)
- **Production-Ready** — Docker containerized, load-balanced, monitored
- **Scalable** — Horizontal scaling for all components, optimized for GPU
- **Complete Pipeline** — Disease targets → protein sequences → drug screening → ADMET prediction → chemical search

**Use Cases:** Pharmaceutical research, biotech screening, computational drug discovery, academic chemistry research.

---

## ✨ Key Features

| Feature | Component | Capability |
|---------|-----------|-----------|
| **ADMET Prediction** | ADMET Service | 5-property MPNN models (Absorption, Distribution, Metabolism, Excretion, Toxicity) |
| **Virtual Screening** | Drug Repurposing | DeepPurpose AI binding affinity prediction |
| **Chemical Search** | Chemical RAG | FAISS-IVF vector search for 1M+ compounds + LLM explanations |
| **Orchestration** | Laravel API | REST endpoints, job queuing, authentication, result aggregation |
| **Persistence** | MariaDB | Job tracking, results storage, user management |
| **Async Processing** | Queue Worker | Background job execution for long-running pipelines |

---

## 🏗️ Architecture

```
                         CLIENT APPS
                       (Web, Mobile, CLI)
                              │
                              ▼
        ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
        ┃   LARAVEL API (Orchestration)    ┃
        ┃   Authentication • Job Dispatch   ┃
        ┃   Result Aggregation              ┃
        ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
            │               │              │
     ┌──────┴──────┐  ┌─────┴─────┐  ┌───┴───────┐
     ▼            ▼  ▼             ▼  ▼           ▼
  ┌────────┐ ┌────────┐  ┌────────────────┐  ┌─────────┐
  │ Queue  │ │ MySQL  │  │  AI Services   │  │         │
  │ Worker │ │        │  ├────────────────┤  │  Storage│
  └────────┘ └────────┘  │ • ADMET        │  └─────────┘
                         │ • Drug Reposit │
                         │ • Chemical RAG │
                         └────────────────┘
        
        All services: Docker network 'ailixir' with persistent volumes
```

**Data Flow:** Clients → Laravel → AI services → Database → Results cached

See [ARCHITECTURE.md](./ARCHITECTURE.md) for detailed diagrams and component descriptions.

## 🚀 Quick Start

### Docker (Recommended — 5 minutes)

```bash
git clone <repo-url>
cd ailixir-backend

docker compose build --parallel
docker compose up -d

# Verify
docker compose ps

# Access: http://localhost:8080/api/documentation
```

### Local Development

See detailed setup in [DOCKER.md](./DOCKER.md#local-development-setup) for per-service configuration.

**Prerequisites:**
- Docker & Docker Compose v2+ (recommended)
- OR: PHP 8.2+, Python 3.10+, MariaDB 11+

---

## 🔧 Services

| Service | Language | Port | Purpose |
|---------|----------|------|---------|
| **Laravel API** | PHP 8.3 | 8080 | REST API, orchestration, authentication |
| **ADMET** | Python 3.11 | 8002 | MPNN models for drug properties |
| **Drug Repurposing** | Python 3.10 | 8001 | DeepPurpose virtual screening |
| **Chemical RAG** | Python 3.11 | 5000 | FAISS search + LLM explanations |
| **Queue Worker** | PHP 8.3 | — | Background job processor |
| **MariaDB** | SQL | 3306 | Persistent storage |

**API Documentation:**
- OpenAPI/Swagger: http://localhost:8080/api/documentation
- Per-service docs: http://localhost:[port]/docs (FastAPI services)

---

## 📡 API

### Core Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/health` | GET | System health status |
| `/api/ai-services/health` | GET | All AI services status |
| `/api/ai-services/test/admet` | POST | Test ADMET prediction |
| `/api/ai-services/test/chemical-search` | POST | Test chemical similarity |

### Example Request

```bash
curl -X POST http://localhost:8080/api/ai-services/test/admet \
  -H "Content-Type: application/json" \
  -d '{"smiles":"c1ccccc1","batch_size":32}'
```

See [API_REFERENCE.md](./API_REFERENCE.md) for complete documentation and all endpoints.

---

## � Deployment

### Docker (Production)

```bash
docker compose -f docker-compose.yml build --parallel
docker compose -f docker-compose.yml up -d
```

Environment variables: [docker/laravel.env](./docker/laravel.env)

See [PRODUCTION_GUIDE.md](./PRODUCTION_GUIDE.md) for:
- Kubernetes deployment configs
- Scaling recommendations
- Monitoring setup
- Security hardening
- Performance tuning

### Environment Variables

Key variables (see [docker/laravel.env](./docker/laravel.env)):

```ini
APP_ENV=production
DB_HOST=mysql
ADMET_AI_URL=http://admet:8000
DRUG_REPURPOSING_URL=http://drug-repurposing:8000
CHEMICAL_AI_URL=http://chemical-rag:5000
AI_INTEGRATION_ROUTES_ENABLED=true
```

---

## 🤝 Contributing

### Development Workflow

1. Clone repository and set up Docker environment
2. Create feature branch from `main`
3. Make changes and test locally
4. Update relevant documentation
5. Submit pull request with clear description

### Code Standards

- **PHP:** PSR-12 (Laravel conventions)
- **Python:** PEP 8 with type hints
- **Commits:** Conventional format (`feat:`, `fix:`, `docs:`)
- **Documentation:** Update all affected README files

### Testing

```bash
# Laravel tests
docker compose exec laravel php artisan test

# Python service tests
cd ai_apps/Drug\ Reporposing
pytest test_api.py -v
```

See [CONTRIBUTING.md](./CONTRIBUTING.md) for detailed guidelines.

---

## ❓ Support & Troubleshooting

### Common Issues

| Problem | Solution |
|---------|----------|
| **Service won't start** | Check logs: `docker compose logs -f <service>` |
| **Port already in use** | Change port in `docker-compose.yml` |
| **Out of memory** | Increase Docker desktop memory or reduce services |
| **FAISS index slow** | First run caches index (~3-5 min, then <1 sec) |

**✓ See [TROUBLESHOOTING.md](./TROUBLESHOOTING.md) for comprehensive troubleshooting guide.**

---

## 📋 Project Structure

```
ailixir-backend/
├── app/                      # Laravel application
├── ai_apps/                  # AI microservices
│   ├── ADMIT/                # ADMET training & inference
│   ├── Drug Reporposing/     # Drug repurposing pipeline
│   └── chemical-rag-system/  # Chemical search & RAG
├── routes/                   # API routes
├── config/                   # Configuration files
├── database/                 # Migrations & seeders
├── docker/                   # Docker configs
├── storage/                  # File storage
├── docker-compose.yml        # Orchestration
└── Dockerfile                # Laravel container
```

---

## 📄 License

**Proprietary** — All rights reserved. See [LICENSE](./LICENSE) for details.

---

## 🔗 Links

**Documentation:**
- [System Architecture](./ARCHITECTURE.md)
- [Docker Setup](./DOCKER.md)
- [Production Guide](./PRODUCTION_GUIDE.md)
- [API Reference](./API_REFERENCE.md)
- [Troubleshooting](./TROUBLESHOOTING.md)

**Service READMEs:**
- [ADMET Inference](./ai_apps/ADMIT/README.md)
- [Drug Repurposing](./ai_apps/Drug%20Reporposing/README.md)
- [Chemical RAG](./ai_apps/chemical-rag-system/README.md)

**External:**
- [Laravel](https://laravel.com/docs)
- [FastAPI](https://fastapi.tiangolo.com/)
- [Docker Compose](https://docs.docker.com/compose/)
- [PyTorch](https://pytorch.org/)

---

## 📞 Support

Questions or issues? Check:
1. [TROUBLESHOOTING.md](./TROUBLESHOOTING.md) — Common solutions
2. [ARCHITECTURE.md](./ARCHITECTURE.md) — System design
3. Service READMEs — Component-specific help
4. Contact: Omar Fadlalla & Development Team

---

**Last Updated:** May 2026 | **Version:** 2.0 | **Status:** Production Ready ✅

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
