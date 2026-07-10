# Changelog: SQLite Migration & Hugging Face Spaces Deployment

> **Date:** 2026-06-09  
> **Branch:** `feature/hugging-face-deploy`  
> **Commits:** `fd94aa0..1cbbcaa` (3 commits)

---

## Overview

Migrated AILIXIR from **MySQL/MariaDB** to **SQLite** and deployed the entire platform to **5 Hugging Face Spaces** with GitHub-to-HF auto-sync.

---

## Part 1: SQLite Database Migration

### Migration Fixes

**4 migration files were modified** to remove MySQL-specific syntax:

| File | Problem | Fix |
|------|---------|-----|
| `database/migrations/2026_04_20_135553_create_admets_table.php` | `useCurrentOnUpdate()` — MySQL-only | Replaced manual timestamps with `$table->timestamps()` |
| `database/migrations/2026_04_20_044726_create_screening_results_table.php` | `output` column not nullable, causing `->change()` failure | Changed `json('output')` to `json('output')->nullable()` |
| `database/migrations/2026_04_20_055149_create_target_lookups_table.php` | Same as above | Same fix |
| `database/migrations/2026_04_21_155344_add_status_to_screening_tables.php` | Used `->change()` which requires MySQL-like `ALTER TABLE MODIFY` | Rewritten to only add `status` column, removed all `->change()` calls |

### Configuration Changes

- **`config/database.php`** — SQLite connection configured with:
  - `journal_mode` = `wal` (Write-Ahead Logging for concurrent reads)
  - `busy_timeout` = `5000` (5 second wait on locked DB)
  - `transaction_mode` = `IMMEDIATE` (prevents "database is locked" errors)

### Environment Files Updated

| File | Before | After |
|------|--------|-------|
| `.env.example` | `DB_CONNECTION=mysql` + host/port/user/pass | `DB_CONNECTION=sqlite`, `DB_DATABASE=database/ailixir.sqlite` |
| `.env.docker` | Same MySQL config | Same SQLite config |
| `docker/laravel.env` | Same MySQL config | Same SQLite config |

### `.gitignore`
Added `*.sqlite`, `*.sqlite-wal`, `*.sqlite-shm` to prevent accidental commits of database files.

---

## Part 2: Docker Infrastructure Changes

### `Dockerfile`
- **PHP extension**: `pdo_mysql` → `pdo_sqlite`
- **System packages**: Added `libsqlite3-dev`, `supervisor`
- **Ports**: Added `EXPOSE 7860` for Hugging Face Spaces
- **Database directory**: Creates `database/` directory during build
- Removed `COPY docker/laravel.env .env` (env vars now come from HF Space secrets)

### `docker/supervisord.conf` (new)
Runs two processes in a single container for HF Spaces:
- `[program:laravel]` — `php artisan serve --host=0.0.0.0 --port=7860`
- `[program:queue]` — `php artisan queue:work --sleep=3 --tries=3 --timeout=300`

### `docker/entrypoint.sh`
- Creates SQLite database file at runtime if it doesn't exist
- Checks `RUN_MODE=hf` environment variable — if set, starts supervisord instead of the default CMD
- Still supports Docker Compose mode (runs migrations, then exec's the passed CMD)

### `docker-compose.yml`
- **Removed** the entire `mysql:` service (was MariaDB 11.4)
- **Removed** `mysql-data` volume
- **Removed** `depends_on: mysql` from `laravel` and `queue` services
- **Removed** `DB_HOST: mysql` environment overrides
- AI microservice configs remain unchanged

### `.env.hf` (new)
Template file with all HF Space URLs pre-configured for the AI microservice URLs.

---

## Part 3: Hugging Face Spaces Deployment

### Spaces Created (5)

| Space | Type | Content |
|-------|------|---------|
| `ailixir-api` | Docker (Laravel) | Root repo (excludes `ai_apps/`, `Diagrams/`) |
| `ailixir-admet` | Docker (FastAPI) | `ai_apps/ADMIT/admet_inference/` |
| `ailixir-drug-repurposing` | Docker (FastAPI) | `ai_apps/Drug Reporposing/` |
| `ailixir-chemical-rag` | Docker (FastAPI) | `ai_apps/chemical-rag-system/chemical-rag-system/` |
| `ailixir-generation` | Docker (FastAPI) | `ai_apps/generation/` (CPU-only Dockerfile) |

### Space Configuration
- All spaces use `cpu-basic` hardware (free tier)
- Port configured to **7860** in each Dockerfile
- `README.md` with proper YAML frontmatter added to each space
- 15-minute sleep timeout for free tier cost savings

### Generation Service Adaptation
The generation service originally required NVIDIA CUDA + GPU. For HF Spaces (CPU-only), a **lightweight Dockerfile** was created that:
- Uses `python:3.10-slim` base (instead of `nvidia/cuda`)
- Installs only `fastapi`, `uvicorn`, `pydantic`, `requests`, `rdkit`
- Disables AutoDock-GPU and DeepPurpose (CPU-only)
- Provides basic molecule generation and docking scoring

---

## Part 4: GitHub-to-HF Auto-Sync

### 5 GitHub Actions Workflows

| File | Triggers | Syncs |
|------|----------|-------|
| `.github/workflows/sync-hf-backend.yml` | Push to `main`/`develop` (excl. `ai_apps/`) | Whole repo → `ailixir-api` |
| `.github/workflows/sync-hf-admet.yml` | Push to `ai_apps/ADMIT/admet_inference/` | Subdir → `ailixir-admet` |
| `.github/workflows/sync-hf-drug-repurposing.yml` | Push to `ai_apps/Drug Reporposing/` | Subdir → `ailixir-drug-repurposing` |
| `.github/workflows/sync-hf-chemical-rag.yml` | Push to `ai_apps/chemical-rag-system/` | Subdir → `ailixir-chemical-rag` |
| `.github/workflows/sync-hf-generation.yml` | Push to `ai_apps/generation/` | Subdir → `ailixir-generation` |

Each workflow:
1. Clones the target HF Space repo
2. Rsyncs the relevant files (excluding `.git`, `__pycache__`, etc.)
3. Commits with the GitHub commit SHA
4. Pushes to HF Space

### GitHub Actions Workflow Updates
- **`api-tests.yml`** — Removed MySQL wait loop and health check; removed MySQL log collection
- **`integration-test.yml`** — Same changes across all 4 job definitions

---

## Part 5: Files Modified/Created Summary

### Modified (15 files)
```
.config/database.php
.env.docker
.env.example
.gitignore
Dockerfile
docker-compose.yml
docker/entrypoint.sh
docker/laravel.env
.github/workflows/api-tests.yml
.github/workflows/integration-test.yml
database/migrations/2026_04_20_044726_create_screening_results_table.php
database/migrations/2026_04_20_055149_create_target_lookups_table.php
database/migrations/2026_04_20_135553_create_admets_table.php
database/migrations/2026_04_21_155344_add_status_to_screening_tables.php
database/ailixir.sqlite (new, empty)
```

### Created (9 files)
```
.env.hf
docker/supervisord.conf
.github/workflows/sync-hf-backend.yml
.github/workflows/sync-hf-admet.yml
.github/workflows/sync-hf-drug-repurposing.yml
.github/workflows/sync-hf-chemical-rag.yml
.github/workflows/sync-hf-generation.yml
HF_SPACES_GUIDE.md (this file)
CHANGELOG_SQLITE_HF.md (this file)
```

---

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────┐
│                    GitHub Repository                      │
│                     RottenShadow/                         │
│                   AILIXIR_BackEnd                         │
└────────────────────┬────────────────────────────────────┘
                     │ Push triggers
                     ▼
       ┌─────────────────────────────┐
       │   GitHub Actions (5 WFs)    │
       │   Auto-sync to HF Spaces    │
       └─────────────┬───────────────┘
                     │
     ┌───────────────┼───────────────────────┐
     │               │                       │
     ▼               ▼                       ▼
┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐
│ailixir-  │  │ailixir-  │  │ailixir-  │  │ailixir-  │  │ailixir-  │
│api       │  │admet     │  │drug-     │  │chemical- │  │generation│
│(Laravel) │  │(ADMET)   │  │repurpos. │  │rag       │  │(Molec.   │
│SQLite    │  │          │  │          │  │          │  │Gen.)     │
└────┬─────┘  └──────────┘  └──────────┘  └──────────┘  └──────────┘
     │
     │  HTTP proxying to AI microservices
      ├──→ https://shdwRow-ailixir-admet.hf.space
     ├──→ https://RottenShadow-ailixir-drug-repurposing.hf.space
     ├──→ https://RottenShadow-ailixir-chemical-rag.hf.space
      └──→ https://shdwRow-ailixir-generation.hf.space
```

---

## Remaining Steps (Manual)

1. **Set HF tokens as GitHub secrets** — Required for auto-sync workflows
   - Go to: https://github.com/RottenShadow/AILIXIR_BackEnd/settings/secrets/actions
   - Add secret: `HF_TOKEN` = `YOUR_ROTTENSHADOW_TOKEN`
   - Add secret: `HF_TOKEN_AILIXIR` = `YOUR_AILIXIR_AI_TEAM_TOKEN`

2. **Set backend Space secrets** — Required for `ailixir-api` to function
   - Go to: https://huggingface.co/spaces/Ailixir-AI-Team/ailixir-api/settings
   - Add all secrets listed in `HF_SPACES_GUIDE.md` section 3.1
   - **Minimum:** `APP_KEY`, `RUN_MODE=hf`, and all AI service URLs

3. **Wait for builds to complete** — Backend Docker image is large (~5GB) and takes 15–45 minutes to build on HF free tier

4. **Verify connectivity** — Test the health endpoints and ensure the backend can reach all AI services
