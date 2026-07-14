# AILIXIR Hugging Face Spaces Deployment Guide

## Overview

The AILIXIR platform is deployed across **5 Hugging Face Spaces**:

| Space | Purpose | URL |
|-------|---------|-----|
| `ailixir-api` | Backend API (Laravel + SQLite) | https://huggingface.co/spaces/Ailixir-AI-Team/ailixir-api |
| `ailixir-admet` | ADMET Prediction Service | https://huggingface.co/spaces/shdwRow/ailixir-admet |
| `ailixir-drug-repurposing` | Drug Repurposing Service | https://huggingface.co/spaces/RottenShadow/ailixir-drug-repurposing |
| `ailixir-chemical-rag` | Chemical RAG Service | https://huggingface.co/spaces/RottenShadow/ailixir-chemical-rag |
| `ailixir-generation` | Molecular Generation Service | https://huggingface.co/spaces/shdwRow/ailixir-generation |

---

## 1. Accessing the Spaces

Each Space builds a Docker container from its respective `Dockerfile`. Once built and running, the services are accessible at:

```
https://RottenShadow-{space-name}.hf.space
```

For example:
- **Backend API:** `https://Ailixir-AI-Team-ailixir-api.hf.space`
- **ADMET:** `https://shdwRow-ailixir-admet.hf.space`
- **Drug Repurposing:** `https://RottenShadow-ailixir-drug-repurposing.hf.space`
- **Chemical RAG:** `https://RottenShadow-ailixir-chemical-rag.hf.space`
- **Generation:** `https://shdwRow-ailixir-generation.hf.space`

---

## 2. Space Lifecycle

### Wake-up / Sleep
Spaces on the **free tier** (CPU Basic) go to sleep after **15 minutes of inactivity**. When you access a sleeping Space's URL, it will take **30–120 seconds** to wake up.

To keep a Space awake, you can:
- Use a **cron job** to ping the health endpoint every 10 minutes
- Upgrade to a **paid hardware tier** (CPU Upgrade or GPU)
- Set a **longer sleep timeout** in the Space settings

### Health Check
Each Space exposes a `/health` endpoint. You can check if a service is running:

  ```bash
  curl https://shdwRow-ailixir-admet.hf.space/health
  ```

Expected response: `{"status": "healthy", ...}`

---

## 3. Environment Variables (Secrets)

Each Space requires certain environment variables. These are set as **Space Secrets** in the Hugging Face Space settings.

### 3.1 Backend Space: `ailixir-api`

These are **required** secrets — set them in:  
https://huggingface.co/spaces/Ailixir-AI-Team/ailixir-api/settings

| Secret | Value | Description |
|--------|-------|-------------|
| `APP_KEY` | *(generate one)* | Laravel app key. Run: `php artisan key:generate --show` |
| `APP_ENV` | `production` | Application environment |
| `APP_DEBUG` | `false` | Debug mode (keep false in production) |
| `DB_CONNECTION` | `sqlite` | Database engine |
| `SESSION_DRIVER` | `database` | Session storage |
| `CACHE_STORE` | `database` | Cache storage |
| `QUEUE_CONNECTION` | `database` | Queue driver |
| `RUN_MODE` | `hf` | **Must be set to `hf`** to enable supervisor mode |
| `AI_INTEGRATION_ROUTES_ENABLED` | `true` | Enable AI proxy routes |
| `CHEMICAL_AI_URL` | `https://RottenShadow-ailixir-chemical-rag.hf.space` | Chemical RAG service URL |
| `ADMET_AI_URL` | `https://shdwRow-ailixir-admet.hf.space` | ADMET service URL |
| `AI_ADMET_SERVICE_URL` | `https://shdwRow-ailixir-admet.hf.space` | ADMET service URL (alias) |
| `DRUG_REPURPOSING_URL` | `https://RottenShadow-ailixir-drug-repurposing.hf.space` | Drug repurposing service URL |
| `AI_SERVICE_URL` | `https://RottenShadow-ailixir-drug-repurposing.hf.space` | Drug repurposing URL (alias) |
| `GENERATION_SERVICE_URL` | `https://shdwRow-ailixir-generation.hf.space` | Generation service URL |
| `MAIL_MAILER` | `log` | Mail driver (log to console) |
| `CLOUDINARY_URL` | `cloudinary://dummy_key:dummy_secret@dummy_cloud` | Cloudinary (can be dummy) |
| `CLOUDINARY_CLOUD_NAME` | `dummy_cloud` | Cloudinary cloud name |
| `CLOUDINARY_API_KEY` | `dummy_key` | Cloudinary API key |
| `CLOUDINARY_API_SECRET` | `dummy_secret` | Cloudinary API secret |
| `LOG_CHANNEL` | `stderr` | Log output channel |
| `LOG_LEVEL` | `info` | Log level |

**How to generate APP_KEY:**
```bash
# If you have PHP locally:
php artisan key:generate --show

# If not, use any online base64 generator to create a random 32-byte base64 string
# Format: base64:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
```

### 3.2 ADMET Space: `ailixir-admet` (owner: shdwRow)

**Space settings:** https://huggingface.co/spaces/shdwRow/ailixir-admet/settings

| Secret | Value | Description |
|--------|-------|-------------|
| `PYTHONUNBUFFERED` | `1` | Python unbuffered output |
| `LOG_LEVEL` | `INFO` | Logging level |

### 3.3 Drug Repurposing Space: `ailixir-drug-repurposing`

| Secret | Value | Description |
|--------|-------|-------------|
| `HOST` | `0.0.0.0` | Bind address |
| `PORT` | `7860` | Port (must match Dockerfile) |
| `DEBUG` | `False` | Debug mode |
| `LOG_LEVEL` | `INFO` | Log level |
| `USE_MOCK_MODEL` | `False` | Use mock model (for testing) |
| `USE_MOCK_DRUGS` | `False` | Use mock drug list (for testing) |

### 3.4 Chemical RAG Space: `ailixir-chemical-rag`

| Secret | Value | Description |
|--------|-------|-------------|
| `PYTHONUNBUFFERED` | `1` | Python unbuffered output |
| `API_PORT` | `7860` | API port |
| `API_HOST` | `0.0.0.0` | Bind address |

### 3.5 Generation Space: `ailixir-generation` (owner: shdwRow)

**Space settings:** https://huggingface.co/spaces/shdwRow/ailixir-generation/settings

| Secret | Value | Description |
|--------|-------|-------------|
| `PUBLIC_BASE_URL` | `https://shdwRow-ailixir-generation.hf.space` | Public URL of the service |
| `REINVENT_DEVICE` | `cpu` | Device for REINVENT (CPU-only on HF free tier) |
| `DEEPPURPOSE_URL` | *(leave empty)* | DeepPurpose service URL (CPU-only version) |
| `ADGPU_BIN` | *(leave empty)* | AutoDock-GPU binary (not available on CPU) |

---

## 4. Setting Secrets in HF Spaces

1. Go to the Space's settings page:  
   `https://huggingface.co/spaces/RottenShadow/{space-name}/settings`

2. Scroll down to **"Repository Secrets"** section

3. Click **"New secret"**

4. Enter the key-value pair and click **"Add"**

5. The Space will automatically **rebuild** with the new secrets

---

## 5. API Endpoints

Once all spaces are running, the backend API is available at:

```
https://Ailixir-AI-Team-ailixir-api.hf.space/api
```

### Authentication
```bash
# Register
curl -X POST https://Ailixir-AI-Team-ailixir-api.hf.space/api/user/register \
  -H "Content-Type: application/json" \
  -d '{"name":"User","email":"user@example.com","password":"password123"}'

# Login
curl -X POST https://Ailixir-AI-Team-ailixir-api.hf.space/api/user/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'

# Response includes Bearer token
```

### AI Service Proxies (via Backend)
All AI endpoints are proxied through the backend:

```bash
# ADMET prediction
curl -X POST https://Ailixir-AI-Team-ailixir-api.hf.space/api/admet/predict \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"smiles": "c1ccccc1"}'

# Chemical search
curl -X POST https://Ailixir-AI-Team-ailixir-api.hf.space/api/chemical-search \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"smiles": "CC(=O)O"}'

# Health check (no auth needed)
curl https://Ailixir-AI-Team-ailixir-api.hf.space/api/ai-services/health
```

---

## 6. Auto-Sync from GitHub

The repository has **5 GitHub Actions workflows** that automatically sync code pushes to HF Spaces:

| Workflow | Triggers On | Syncs To |
|----------|-------------|----------|
| `sync-hf-all.yml` | Push to `main` | All 5 spaces (matrix job) |

### ❗ PREREQUISITE: Set `HF_TOKEN` as a GitHub Secret

For the auto-sync to work, you **must** add both HF tokens as GitHub Actions secrets:

1. Go to: https://github.com/RottenShadow/AILIXIR_BackEnd/settings/secrets/actions
2. Click **"New repository secret"**
3. **Name:** `HF_TOKEN` — **Value:** RottenShadow account token (for `RottenShadow` spaces)
4. **Name:** `HF_TOKEN_SHDWROW` — **Value:** shdwRow account token (for `shdwRow` spaces)
5. **Name:** `HF_TOKEN_AILIXIR` — **Value:** Ailixir-AI-Team account token (for `ailixir-api`)

Without these, the auto-sync workflows will fail with an authentication error.

---

## 7. Troubleshooting

### Space stuck at `BUILDING`
- Large Docker images can take **15–45 minutes** to build on HF free tier
- Check the build logs: Space page → **"Factory"** or **"Builder"** tab
- Common issues: missing files, Dockerfile errors, out of memory

### Space shows `NO_APP_FILE`
- The Dockerfile is missing from the root of the Space repo
- Ensure `Dockerfile` exists at the root (not in a subdirectory)

### Space shows `CONFIG_ERROR`
- Missing or invalid `README.md` YAML frontmatter
- Ensure the `README.md` has the correct format:
  ```yaml
  ---
  title: Space Name
  emoji: 🧬
  colorFrom: blue
  colorTo: green
  sdk: docker
  app_port: 7860
  ---
  ```

### API returns 500 errors
- Check the Space logs: Space page → **"Factory"** tab
- Verify all required environment secrets are set
- For the backend, ensure `APP_KEY` is a valid Laravel key

### Service not reachable from backend
- Verify the AI service URL in the backend's secrets is correct
- The URLs should use the format: `https://RottenShadow-{name}.hf.space` (or `https://shdwRow-{name}.hf.space` for ADMET/Generation)
- Test the AI service directly first:
  ```bash
curl https://shdwRow-ailixir-admet.hf.space/health
  ```

### Space keeps going to sleep
- Free tier spaces sleep after 15 minutes of inactivity
- Use a cron job (e.g., GitHub Actions scheduled workflow) to ping every 10 minutes
- Or upgrade to a paid hardware tier (starting at $5/month)

---

## 8. Upgrading Hardware

To upgrade a Space from CPU Basic to a paid tier:

1. Go to the Space settings
2. Under **"Hardware"**, select a tier:
   - `cpu-upgrade` — 4 vCPU, 16GB RAM ($5/month)
   - `cpu-basic` — 2 vCPU, 16GB RAM (free)
   - `t4-small` — NVIDIA T4 GPU ($20/month, for generation service)
3. Save — the Space will rebuild

**Note:** The generation service (`ailixir-generation`) can benefit from a GPU upgrade for AutoDock-GPU docking.
