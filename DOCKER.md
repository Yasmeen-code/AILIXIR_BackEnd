# AILIXIR Docker Stack

Monorepo stack: **Laravel API** + **ADMET** + **Drug Repurposing** + **Chemical RAG**.

## Requirements

- Docker 24+ with Compose v2
- ~16 GB RAM recommended (PyTorch + ADMET models)
- Model artifacts present locally:
  - `ai_apps/ADMIT/admet_inference/models/*/best_model.ckpt`
  - `ai_apps/Drug Reporposing/save_folder/pretrained_models/...`

## Quick start

```bash
# From repository root
docker compose build
docker compose up -d
```

| Service | Host URL |
|---------|----------|
| Laravel API | http://localhost:8080 |
| ADMET | http://localhost:8002 |
| Drug Repurposing | http://localhost:8001 |
| Chemical RAG | http://localhost:5000 |

## Laravel → AI integration endpoints

Enabled when `AI_INTEGRATION_ROUTES_ENABLED=true` (set in `docker/laravel.env`).

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/ai-services/health` | Ping all AI `/health` endpoints |
| POST | `/api/ai-services/test/admet` | Proxy ADMET `/predict` |
| POST | `/api/ai-services/test/chemical-search` | Proxy Chemical RAG retrieval |
| GET | `/api/ai-services/test/drug-repurposing` | Health + model status |

Example:

```bash
curl http://localhost:8080/api/ai-services/health
curl -X POST http://localhost:8080/api/ai-services/test/admet \
  -H "Content-Type: application/json" \
  -d '{"smiles":"c1ccccc1"}'
```

## Images

- **Laravel**: multi-stage (`composer` + `node` + `php:8.3-cli-alpine`), runs as `www-data`
- **ADMET / Drug Repurposing**: existing multi-stage Python slim images, non-root UID 1000
- **Chemical RAG**: multi-stage `python:3.11-slim`, non-root `raguser`
- **MySQL**: `mariadb:11.4`

## CI

GitHub Actions workflow: `.github/workflows/integration-test.yml`

Builds the full stack and tests Laravel proxy endpoints against all AI services.

## Environment

Copy and adjust `docker/laravel.env` for container-specific values. For local (non-Docker) development, set URLs in `.env` using `.env.example` as reference.
