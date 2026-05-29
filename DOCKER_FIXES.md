# 🔧 Docker Build Fixes - AILIXIR v2.0

**Last Updated**: 2026 | **Status**: ✅ All Issues Resolved

This document outlines the Docker build failures discovered in CI/CD and the solutions applied.

---

## 🚨 Issues Resolved

### Issue #1: PyTorch Version Incompatibility (CRITICAL)

#### Problem
```
ERROR: Could not find a version that satisfies the requirement 
       torchaudio==0.15.2
```

#### Root Cause
- **File**: `ai_apps/Drug Reporposing/requirements.txt`
- **Line**: 24 (originally specified `torchaudio==0.15.2`)
- **Issue**: `torchaudio==0.15.2` does not exist on PyPI
- **Version Gap**: PyPI contains versions 0.11.0 through 2.11.0, but the 0.15.x and 1.x ranges were never released
- **Incompatibility**: The version was inconsistent with torch==2.0.1 and torchvision==0.15.2

#### Solution
Updated to compatible PyTorch audio library version:

**Before:**
```txt
torch==2.0.1
torchvision==0.15.2
torchaudio==0.15.2  # ❌ Does not exist
```

**After:**
```txt
torch==2.0.1
torchvision==0.15.2
torchaudio==2.0.1   # ✅ Compatible with torch 2.0.1
```

#### Files Changed
- `ai_apps/Drug Reporposing/requirements.txt` (line 24)

#### Verification
```bash
# These versions are now available and compatible
pip install torch==2.0.1 torchvision==0.15.2 torchaudio==2.0.1
```

---

### Issue #2: Dockerfile Casing Warnings (STYLE)

#### Problem
```
FromAsCasing: 'as' and 'FROM' keywords' casing do not match
```

#### Root Cause
- Docker Dockerfile linter enforces consistent keyword casing
- Multi-stage build syntax uses `FROM ... as builder` (mixed case)
- Should be `FROM ... AS builder` (uppercase AS)
- **Affected Files**: 2 of 4 Dockerfiles had this warning

#### Solution
Updated all multi-stage Dockerfile build syntax to use uppercase `AS`:

**Before:**
```dockerfile
FROM python:3.10-slim as builder  # ❌ Mixed case
```

**After:**
```dockerfile
FROM python:3.10-slim AS builder  # ✅ Proper casing
```

#### Files Changed
1. `ai_apps/ADMIT/admet_inference/Dockerfile` (line 4)
   - Changed: `FROM python:3.11-slim as builder` → `FROM python:3.11-slim AS builder`

2. `ai_apps/Drug Reporposing/docker/Dockerfile` (line 1)
   - Changed: `FROM python:3.10-slim as builder` → `FROM python:3.10-slim AS builder`

#### Status Note
- `ai_apps/chemical-rag-system/chemical-rag-system/Dockerfile` already had correct casing (`AS builder`)
- No changes were needed for this file

---

## 📊 Fix Summary

| Issue | File(s) | Change | Status |
|-------|---------|--------|--------|
| PyTorch incompatibility | Drug Reporposing/requirements.txt | torchaudio 0.15.2 → 2.0.1 | ✅ Fixed |
| Dockerfile casing | ADMET/Dockerfile | `as` → `AS` | ✅ Fixed |
| Dockerfile casing | Drug Repurposing/Dockerfile | `as` → `AS` | ✅ Fixed |
| Dockerfile casing | Chemical-RAG/Dockerfile | Already correct | ✅ OK |

---

## ✅ Verification Steps

After fixes, verify the Docker build succeeds:

```bash
# Clear Docker cache (optional)
docker compose down
docker system prune -a

# Build all services with fixes applied
cd AILIXIR_BackEnd
docker compose build --parallel

# Expected output:
# [+] Building 5/5
#  - Image ailixir-admet          Built
#  - Image ailixir-drug-repurposing Built
#  - Image ailixir-chemical-rag    Built
#  - Image ailixir-laravel         Built
#  - Image ailixir-queue           Built
```

### Verify Individual Services
```bash
# Check each image built successfully
docker images | grep ailixir

# Test container startup
docker compose up -d
docker compose ps
# All services should show "Up" status ✅
```

---

## 🔍 Technical Details

### PyTorch Version Compatibility Matrix

For Python 3.10 with CUDA-free (CPU) installation:

| torch | torchvision | torchaudio | Status |
|-------|-------------|-----------|--------|
| 2.0.1 | 0.15.2 | 0.15.2 | ❌ torchaudio doesn't exist |
| 2.0.1 | 0.15.2 | 2.0.1 | ✅ Valid (current) |
| 2.0.1 | 0.15.2 | 2.0.2 | ✅ Valid alternative |
| 1.13.1 | 0.14.1 | 0.13.1 | ✅ Legacy option |

**Selected**: torch 2.0.1 + torchaudio 2.0.1 (latest stable, best compatibility)

### Dockerfile Multi-Stage Build Syntax

The styleguide requires proper case:

```dockerfile
# Correct syntax
FROM <image> AS <name>
COPY --from=<name> /path /path

# Example (Drug Repurposing)
FROM python:3.10-slim AS builder
RUN apt-get update && pip install -r requirements.txt
FROM python:3.10-slim
COPY --from=builder /opt/venv /opt/venv
```

---

## 📋 Checklist for Future Updates

When updating dependencies or Dockerfiles:

- [ ] Verify PyPI has the exact version (search on pypi.org)
- [ ] Check PyTorch compatibility: https://pytorch.org/get-started/locally/
- [ ] Test locally: `docker compose build --parallel`
- [ ] Check for Dockerfile linter warnings: `docker build --check`
- [ ] Run services: `docker compose up -d`
- [ ] Verify API endpoints respond: `curl http://localhost:8000`

---

## 🆘 Troubleshooting

### If Docker Build Still Fails

1. **Clear Docker cache**:
   ```bash
   docker compose down
   docker system prune -a -f
   docker compose build --no-cache --parallel
   ```

2. **Check individual service logs**:
   ```bash
   docker compose build admet 2>&1 | tail -50
   docker compose build drug-repurposing 2>&1 | tail -50
   ```

3. **Verify file changes**:
   ```bash
   # Check torchaudio version
   grep -n "torchaudio" ai_apps/Drug\ Reporposing/requirements.txt
   
   # Check Dockerfile casing
   grep "FROM.*as\|FROM.*AS" ai_apps/*/*/Dockerfile ai_apps/*/*/Dockerfile
   ```

4. **Network/Registry Issues**:
   - Ensure internet connectivity for PyPI, Docker Hub
   - Try building at different time (registry mirrors may have delays)

---

## 📚 References

- PyPI Package Index: https://pypi.org/
- PyTorch Installation: https://pytorch.org/get-started/locally/
- Docker Best Practices: https://docs.docker.com/develop/dev-best-practices/
- Dockerfile Reference: https://docs.docker.com/engine/reference/builder/

---

## 🎯 Next Steps

- Monitor CI/CD pipeline for successful builds: `docker compose build --parallel`
- Add pre-commit hooks to validate Dockerfile syntax
- Document dependency update procedures
- Set up automated version compatibility checks
