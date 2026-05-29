# 🧬 AILIXIR — Enterprise-Grade AI Drug Discovery Platform

![Status](https://img.shields.io/badge/Status-Production--Ready-brightgreen?style=flat-square)
![Version](https://img.shields.io/badge/Version-2.0-blue?style=flat-square)
![Python](https://img.shields.io/badge/Python-3.10%2B-blue?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-8.3-indigo?style=flat-square)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=flat-square)
![License](https://img.shields.io/badge/License-Proprietary-red?style=flat-square)

**AILIXIR** is an intelligent, scalable backend platform engineered for computational drug discovery and molecular analysis. Combining advanced AI microservices with enterprise-class orchestration, AILIXIR enables pharmaceutical researchers and biotech organizations to accelerate drug discovery pipelines through predictive analytics, virtual screening, and chemical similarity search at scale.

---

## 📚 Documentation Gateway

Choose your starting point based on your role:

| Role | Guide | Purpose |
|------|-------|---------|
| **👤 First-Time User** | [QUICK_START.md](./QUICK_START.md) | Launch AILIXIR in 5 minutes |
| **🏗️ Architect** | [ARCHITECTURE.md](./ARCHITECTURE.md) | Understand system design & components |
| **🐳 DevOps** | [DOCKER.md](./DOCKER.md) | Container orchestration & deployment |
| **📊 Developer** | [API.md](./API.md) | Complete API endpoint reference |
| **⚙️ System Admin** | [PRODUCTION_GUIDE.md](./PRODUCTION_GUIDE.md) | Deployment, scaling, & monitoring |
| **🔧 Troubleshooter** | [TROUBLESHOOTING.md](./TROUBLESHOOTING.md) | Solutions to common issues |

**Service Documentation:**
- [ADMET Inference](./ai_apps/ADMIT/README.md) — Multi-property MPNN prediction models
- [Drug Repurposing](./ai_apps/Drug%20Reporposing/README.md) — Deep learning virtual screening
- [Chemical RAG](./ai_apps/chemical-rag-system/README.md) — Vector-based molecular search engine

---

## 📋 Table of Contents

- [Overview](#overview)
- [Core Capabilities](#core-capabilities)
- [System Architecture](#system-architecture)
- [Quick Start](#quick-start)
- [Microservices](#microservices)
- [API Integration](#api-integration)
- [Deployment](#deployment)
- [Contributing](#contributing)

---

## 🎯 Overview

**AILIXIR** powers the full spectrum of computational drug discovery workflows:

- 🔬 **Molecular Property Prediction** — ADMET (Absorption, Distribution, Metabolism, Excretion, Toxicity)
- 🎯 **Virtual Drug Screening** — Rapid compound-target binding affinity assessment
- 🔍 **Chemical Intelligence** — FAISS-powered vector search across 1M+ molecular compounds
- 📡 **Unified Orchestration** — REST API gateway for seamless service integration
- 📦 **Persistent Pipeline** — Job queuing, result aggregation, and historical analysis

**Ideal For:**
- Pharmaceutical R&D organizations
- Biotech research institutions
- Academic computational chemistry groups
- Clinical decision support systems

---

## ✨ Core Capabilities

| Capability | Service | Technical Details |
|------------|---------|-------------------|
| **ADMET Prediction** | ADMET Service | Multi-task MPNN deep learning; 5 simultaneous property predictions (Absorption, Distribution, Metabolism, Excretion, Toxicity) |
| **Binding Affinity** | Drug Repurposing | DeepPurpose CNN-MPNN hybrid architecture; disease-target-compound screening pipelines |
| **Molecular Search** | Chemical RAG | FAISS-IVF vector indexing; 1M+ compounds; LLM-augmented similarity explanations |
| **Orchestration** | Laravel API | RESTful gateway; JWT authentication; asynchronous job processing; result aggregation |
| **Persistence** | MariaDB | Audit-ready job history; result versioning; user-centric data management |
| **Scalability** | Queue Worker | Distributed background processing; auto-retry mechanisms; dead-letter queue support |

---

## 🏗️ System Architecture

```
                       CLIENTS & APPLICATIONS
                   (Web Dashboard | CLI | Mobile)
                              │
                              ▼
        ┌──────────────────────────────────┐
        │   LARAVEL API ORCHESTRATION      │
        │  ├─ Authentication (JWT)         │
        │  ├─ Job Dispatch & Aggregation   │
        │  ├─ Result Caching & Versioning  │
        │  └─ Rate Limiting & Monitoring   │
        └──────────────────────────────────┘
          │              │              │
    ┌─────┴─┐     ┌─────┴─┐      ┌────┴────┐
    ▼       ▼     ▼       ▼      ▼         ▼
  ┌──────┐ ┌─────────┐ ┌─────────────┐ ┌──────────┐
  │Queue │ │ MariaDB │ │ AI Services │ │ Storage  │
  │Worker│ │ Cache   │ │ (GPU Ready) │ │ Manager  │
  └──────┘ └─────────┘ ├─────────────┤ └──────────┘
                       │ • ADMET     │
                       │ • Drug Scr. │
                       │ • Chem. RAG │
                       └─────────────┘
    
    🔗 Network: Docker bridge 'ailixir' (production-hardened)
    💾 Persistence: Named volumes with backup integration
```

**Request Lifecycle:** Client → Laravel Gateway → Service Router → AI Pipeline → Database → Results Cache → Response

See [ARCHITECTURE.md](./ARCHITECTURE.md) for detailed component specifications and data flow diagrams.

---

## 🚀 Quick Start

### Option 1: Docker (Recommended — 5 minutes)

```bash
# Clone and enter repository
git clone <repo-url>
cd AILIXIR_BackEnd

# Build all services in parallel
docker compose build --parallel

# Start the platform
docker compose up -d

# Verify deployment
docker compose ps

# Access API documentation
# Open: http://localhost:8080/api/documentation
```

### Option 2: Local Development

For detailed per-service setup instructions, see [DOCKER.md](./DOCKER.md#local-development).

**System Requirements:**
- Docker & Docker Compose v2.0+ (recommended)
- **OR** PHP 8.3, Python 3.10+, MariaDB 11+, CUDA 11+ (for GPU)

---

## 🔧 Microservices

| Service | Technology | Port | Purpose | GPU Required |
|---------|------------|------|---------|--------------|
| **Laravel API** | PHP 8.3 + Sanctum | 8080 | REST orchestration, authentication | ✗ |
| **ADMET** | Python 3.10 + PyTorch | 8002 | MPNN property prediction | ✓ (8GB+) |
| **Drug Repurposing** | Python 3.10 + DeepPurpose | 8001 | Virtual screening | ✓ (8GB+) |
| **Chemical RAG** | Python 3.11 + FAISS | 5000 | Molecular vector search | ✗ (CPU viable) |
| **Queue Worker** | PHP 8.3 + Laravel | — | Async job processing | ✗ |
| **MariaDB** | MariaDB 11.4 | 3306 | Persistent data store | ✗ |

**API Documentation:**
- Swagger/OpenAPI UI: `http://localhost:8080/api/documentation`
- Service Docs: `http://localhost:[PORT]/docs` (FastAPI services)
- Full Reference: [API.md](./API.md)

---

## 📡 API Integration

### Health Check

```bash
curl http://localhost:8080/api/health
```

**Response:**
```json
{
  "status": "healthy",
  "timestamp": "2026-05-29T12:30:45Z",
  "services": {
    "database": "connected",
    "admet": "ready",
    "drug_repurposing": "ready",
    "chemical_rag": "ready"
  }
}
```

### Test ADMET Prediction

```bash
curl -X POST http://localhost:8080/api/ai-services/test/admet \
  -H "Content-Type: application/json" \
  -d '{
    "smiles": "CC(=O)Oc1ccccc1C(=O)O",
    "batch_size": 32
  }'
```

### Test Chemical Similarity Search

```bash
curl -X POST http://localhost:8080/api/ai-services/test/chemical-search \
  -H "Content-Type: application/json" \
  -d '{
    "query_smiles": "CC(=O)Oc1ccccc1C(=O)O",
    "top_k": 10
  }'
```

**📘 See [API.md](./API.md) for all endpoints, request/response schemas, error codes, and integration examples.**

---

## 🌐 Deployment

### Docker Compose (Production)

```bash
# Build all images
docker compose build --parallel

# Start all services
docker compose up -d

# Monitor logs
docker compose logs -f

# Stop services
docker compose down
```

### Environment Configuration

Critical environment variables (see [docker/laravel.env](./docker/laravel.env)):

```ini
# Application
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:2fl+KtvkdphvQyE8h7qJ8xN0ZvY3mR5wX1pL9sT6uA4c=

# Database
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=ailixir
DB_USERNAME=ailixir
DB_PASSWORD=secret

# AI Services
ADMET_AI_URL=http://admet:8000
DRUG_REPURPOSING_URL=http://drug-repurposing:8000
CHEMICAL_AI_URL=http://chemical-rag:5000
```

**⚙️ For Kubernetes, scaling, monitors, and production hardening, see [PRODUCTION_GUIDE.md](./PRODUCTION_GUIDE.md)**

---

## 🤝 Contributing

### Development Workflow

1. **Fork & Clone:** Create a feature branch from `main`
2. **Develop:** Make changes following code standards
3. **Test:** Run test suites locally
4. **Document:** Update affected README files
5. **PR:** Submit pull request with clear description

### Code Standards

| Lang | Standard | Tools |
|------|----------|-------|
| **PHP** | PSR-12 | Laravel Pint, PHPStan |
| **Python** | PEP 8 | Black, Flake8, Mypy |
| **Commits** | Conventional | `feat:`, `fix:`, `docs:`, `test:` |

### Running Tests

```bash
# Laravel tests
docker compose exec laravel php artisan test

# Python service tests
docker compose exec admet pytest test/ -v

# Code quality
docker compose exec laravel php artisan pint --check
```

See [CONTRIBUTING.md](./CONTRIBUTING.md) for detailed guidelines.

---

## ❓ Troubleshooting & Support

### Common Issues

| Problem | Diagnosis | Solution |
|---------|-----------|----------|
| **Services failing to start** | Check logs | `docker compose logs -f <service>` |
| **Port conflicts** | Port already in use | Update `docker-compose.yml` port mappings |
| **Out of memory** | Resource exhaustion | Increase Docker memory allocation |
| **FAISS index slow (first run)** | Index building | Expected 3-5 min first run, <1 sec after caching |
| **GPU not detected** | CUDA missing | Install NVIDIA drivers and nvidia-docker |

**Complete troubleshooting guide:** [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)

---

## 📁 Project Structure

```
AILIXIR_BackEnd/
├── app/                         # Laravel core
│   ├── Http/Controllers/        # API endpoints
│   ├── Models/                  # Database schemas
│   ├── Jobs/                    # Background tasks
│   └── Services/                # Business logic
├── ai_apps/                     # AI microservices
│   ├── ADMIT/                   # ADMET prediction service
│   ├── Drug Reporposing/        # Drug-target screening
│   └── chemical-rag-system/     # Chemical similarity search
├── config/                      # Application configuration
├── database/                    # Migrations & seeders
├── routes/                      # API route definitions
├── docker/                      # Container configuration
├── docker-compose.yml          # Service orchestration
├── Dockerfile                  # Laravel container spec
└── [DOCUMENTATION_FILES]       # README, ARCHITECTURE, etc.
```

---

## 📄 License & Legal

**Proprietary Software** — All rights reserved.

This software is provided as-is for authorized users only. Unauthorized copying, modification, or distribution is prohibited. See [LICENSE](./LICENSE) for complete terms.

---

## 🔗 Quick Links

**Documentation:**

| Topic | Link |
|-------|------|
| Architecture | [ARCHITECTURE.md](./ARCHITECTURE.md) |
| Docker | [DOCKER.md](./DOCKER.md) |
| Production | [PRODUCTION_GUIDE.md](./PRODUCTION_GUIDE.md) |
| API Reference | [API.md](./API.md) |
| Troubleshooting | [TROUBLESHOOTING.md](./TROUBLESHOOTING.md) |

**Service READMEs:**

| Service | Link |
|---------|------|
| ADMET Inference | [README](./ai_apps/ADMIT/README.md) |
| Drug Repurposing | [README](./ai_apps/Drug%20Reporposing/README.md) |
| Chemical RAG | [README](./ai_apps/chemical-rag-system/README.md) |

**External Resources:**
- [Laravel Documentation](https://laravel.com/docs)
- [FastAPI Documentation](https://fastapi.tiangolo.com/)
- [PyTorch Documentation](https://pytorch.org/docs)
- [Docker Compose Reference](https://docs.docker.com/compose/reference/)

---

## 📞 Support & Feedback

**Issues or Questions?**

1. 📖 Check [TROUBLESHOOTING.md](./TROUBLESHOOTING.md) for common solutions
2. 🏗️ Review [ARCHITECTURE.md](./ARCHITECTURE.md) for system design context
3. 📚 Consult service-specific READMEs in `ai_apps/` folders
4. 💬 Contact: Development Team

---

**Last Updated:** May 29, 2026 | **Version:** 2.0.0 | **Status:** ✅ Production Ready
