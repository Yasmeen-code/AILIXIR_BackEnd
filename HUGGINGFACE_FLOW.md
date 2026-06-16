# AILIXIR Hugging Face Spaces — Migration Flow

> **Base commit:** `fd94aa0` — pre-HF, Docker Compose with MySQL+Redis

---

## 1. Why Hugging Face Spaces?

| Need | Solution |
|------|----------|
| Free hosting for 5 microservices | 5 HF Spaces (2 accounts to keep more awake) |
| No MySQL/Redis infrastructure | SQLite for storage, `database` queue driver |
| Simple CI/CD | GitHub Actions auto-syncs code to each HF Space |
| Binary model files | Git LFS + Xet storage on HF |

---

## 2. Architecture Decisions

### SQLite over MySQL
- **Why:** HF Spaces provide ephemeral storage; SQLite has zero dependencies
- **Trade-off:** No concurrent write scaling; fine for single-user/small-team usage
- **DB Location:** `database/ailixir.sqlite` (mounted in the container)
- **Migrations:** Run automatically via `entrypoint.sh` on startup

### Two HF Accounts
- **`RottenShadow`** (main): Backend API, Chemical RAG, Drug Repurposing
- **`shdwRow`** (secondary): ADMET, Generation
- **Why:** HF Spaces free tier puts unused spaces to sleep; spreading across 2 accounts keeps more services warm

### Queue: `sync` driver
- **Why:** No need for Redis/DB-backed queue worker; jobs run inline
- **Note:** For production scale, switch back to `database` and run a queue worker

### Binary Model Files: Git LFS + Xet
- **Why:** HF rejects large files in raw Git; Xet provides efficient binary storage
- **Tracked:** `*.ckpt`, `*.pt`, `*.pkl`

---

## 3. Per-Service Details

### Backend API (`RottenShadow/ailixir-api`)
- **Tech:** Laravel 12 + PHP 8.3 CLI
- **Port:** 7860 (HF default)
- **Dockerfile:** Multi-stage (composer → vite → runtime)
- **Entrypoint:** Sources `RUN_MODE=hf` → starts supervisord (laravel serve + queue worker)
- **Fixes applied:**
  - `config/view.php` — explicit compiled path (fixes 500 on `/`)
  - `bootstrap/app.php` — `trustProxies(at: '*')` (fixes mixed-content CSS)
  - `config/scribe.php` — `docs_url` moved to `/scribe-docs`
  - `/docs` route — serves rendered `API.md` (curl examples, responses, grouped)
  - `QUEUE_CONNECTION=sync` — no worker needed

### ADMET Prediction (`shdwRow/ailixir-admet`)
- **Tech:** FastAPI + PyTorch
- **Port:** 7860
- **Model:** `best_model.ckpt` (LFS-tracked)
- **Sync:** Xet-enabled checkout in workflow

### Chemical RAG (`RottenShadow/ailixir-chemical-rag`)
- **Tech:** FastAPI + ChromaDB
- **Port:** 7860

### Drug Repurposing (`RottenShadow/ailixir-drug-repurposing`)
- **Tech:** FastAPI + DeepPurpose
- **Port:** 7860
- **Special:** `Dockerfile.hf` with mock-mode fallback

### Generation (`shdwRow/ailixir-generation`)
- **Tech:** FastAPI + REINVENT4 + RDKit + DeepPurpose (optional)
- **Port:** 7860
- **Fixes applied:**
  - **REINVENT4** — git cloned and `install.py cpu` in `Dockerfile.hf`
  - **libXrender** — `libxrender1 libxext6` added (RDKit image rendering)
  - **DeepPurpose** — optional install; integrated `/reinvent_predict` route on same port 7860
  - **Graceful fallback** — if DeepPurpose unavailable, enrichment returns null affinity values
  - **Enrichment** — catches connection errors, sets `pred_pAff_mean` to `None`

---

## 4. Sync Workflow

**File:** `.github/workflows/sync-hf-all.yml`

### Trigger
- Push to `main` branch
- Manual dispatch via GitHub UI

### Jobs (5 total)

| Job | HF Space | Account | Token | Notes |
|-----|----------|---------|-------|-------|
| `backend` | `ailixir-api` | RottenShadow | `HF_TOKEN` | Excludes `ai_apps/`, `database/ailixir.sqlite`, `storage/` |
| `drug-repurposing` | `ailixir-drug-repurposing` | RottenShadow | `HF_TOKEN` | Renames `Dockerfile.hf` → `Dockerfile` |
| `admet` | `ailixir-admet` | shdwRow | `HF_TOKEN_SHDWROW` | LFS + Xet for `.ckpt` |
| `chemical-rag` | `ailixir-chemical-rag` | RottenShadow | `HF_TOKEN` | |
| `generation` | `ailixir-generation` | shdwRow | `HF_TOKEN_SHDWROW` | LFS + Xet for `.ckpt`, `.pt`, `.pkl` |

### How each job works
1. Clone the HF Space repo (shallow, depth 1)
2. `rsync -av --delete` — sync files from main repo, delete files removed in source
3. Write `.env` with all required vars
4. Rename `Dockerfile.hf` → `Dockerfile` if present
5. Setup LFS/Xet for model files (ADMET, Generation)
6. Commit and push to HF Space

### Key exclusions (backend job)
```
--exclude=ai_apps
--exclude=.git
--exclude=.env
--exclude=.dockerignore
--exclude=public/imgs
--exclude=storage
--exclude=Diagrams
--exclude=database/ailixir.sqlite
```

### Required GitHub Secrets
| Secret | Used by |
|--------|---------|
| `HF_TOKEN` | backend, drug-repurposing, chemical-rag |
| `HF_TOKEN_SHDWROW` | admet, generation |
| `GOOGLE_CLIENT_ID` | backend .env |
| `GOOGLE_CLIENT_SECRET` | backend .env |

---

## 5. Environment Variables (`.env` written by sync)

```
RUN_MODE=hf
APP_KEY=base64:...
AI_INTEGRATION_ROUTES_ENABLED=true
APP_URL=https://RottenShadow-ailixir-api.hf.space
APP_DEBUG=true
QUEUE_CONNECTION=sync
ADMET_AI_URL=https://shdwRow-ailixir-admet.hf.space
CHEMICAL_AI_URL=https://RottenShadow-ailixir-chemical-rag.hf.space
DRUG_REPURPOSING_URL=https://RottenShadow-ailixir-drug-repurposing.hf.space
GENERATION_SERVICE_URL=https://shdwRow-ailixir-generation.hf.space
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=https://RottenShadow-ailixir-api.hf.space/api/user/auth/google
CLOUDINARY_CLOUD_NAME=dummy_cloud
CLOUDINARY_API_KEY=dummy_key
CLOUDINARY_API_SECRET=dummy_secret
```

---

## 6. Database Protection

The sync workflow **excludes** `database/ailixir.sqlite` from rsync and runs `git checkout HEAD -- database/ailixir.sqlite` before commit to prevent the live database from being pushed to git.

On HF Spaces, the database file persists across restarts (HF Spaces keep filesystem between restarts) but is **reset** when the Space rebuilds (code push).

---

## 7. Known Issues & Future Work

| Issue | Status | Resolution |
|-------|--------|------------|
| DeepPurpose on Python 3.10 | Unstable | Falls back to null affinity predictions; could switch to conda base image |
| Google OAuth secrets | Not set | Needs `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` added to GitHub secrets |
| Space sleep on free tier | Ongoing | Two-account setup mitigates; upgrade to HF Pro for always-on |
| Cloudinary credentials | Dummy values | Set real Cloudinary keys for image upload features |
| ADMET model files in Xet | Working | Large `.ckpt` files stored via HF Xet |
| Docs route `/docs` | Working | Serves rendered `API.md` with all examples and grouping |

---

## 8. Commit History (cleaned)

```
c49c94f Add HF Spaces sync workflow with two-account setup, DB protection
30f0a11 Generation HF: REINVENT4, DeepPurpose, libXrender, graceful fallback
a342297 AI microservices HF: ports 8000→7860, Dockerfiles, README_HF
af62962 Backend HF fixes: trustProxies, view cache path, docs route with API.md
fd80b74 Add HF Spaces environment config, deployment guide, and changelog
b4a5d77 Foundation: migrate MySQL→SQLite, add supervisor, port 7860, APP_KEY auto-gen
fd94aa0 Limit Docker Compose build parallelism in CI (original base)
```
