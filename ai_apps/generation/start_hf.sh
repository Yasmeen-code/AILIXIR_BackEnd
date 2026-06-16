#!/usr/bin/env bash
set -euo pipefail
mkdir -p /app/outputs/jobs
mkdir -p /app/outputs/deeppurpose
mkdir -p /home/abdullah/projects/egfr_drug_discovery/runs/deeppurpose

export PORT="${PORT:-7860}"
export PUBLIC_BASE_URL="${PUBLIC_BASE_URL:-http://localhost:${PORT}}"
export DEEPPURPOSE_URL="${DEEPPURPOSE_URL:-http://127.0.0.1:8001/reinvent_predict}"
export REINVENT_DEVICE="${REINVENT_DEVICE:-cpu}"
export PYTHON="${PYTHON:-python}"

if [ -x "/opt/AutoDock-GPU/bin/autodock_cpu_1wi" ]; then
  export ADGPU_BIN="${ADGPU_BIN:-/opt/AutoDock-GPU/bin/autodock_cpu_1wi}"
elif [ -x "/opt/AutoDock-GPU/bin/autodock_gpu_64wi" ]; then
  export ADGPU_BIN="${ADGPU_BIN:-/opt/AutoDock-GPU/bin/autodock_gpu_64wi}"
else
  export ADGPU_BIN="${ADGPU_BIN:-}"
fi

echo "PORT=${PORT}"
echo "PUBLIC_BASE_URL=${PUBLIC_BASE_URL}"
echo "DEEPPURPOSE_URL=${DEEPPURPOSE_URL}"
echo "REINVENT_DEVICE=${REINVENT_DEVICE}"
echo "ADGPU_BIN=${ADGPU_BIN}"

echo "Starting DeepPurpose affinity service on 127.0.0.1:8001..."
conda run --no-capture-output -n dp \
  uvicorn services.deeppurpose.serve_affinity:app \
  --app-dir /app/bundle \
  --host 127.0.0.1 \
  --port 8001 &

AFFINITY_PID=$!

echo "Waiting for DeepPurpose affinity service..."
conda run --no-capture-output -n ailixir python - <<'PY'
import time
import sys
import requests

url = "http://127.0.0.1:8001/health"

for i in range(180):
    try:
        r = requests.get(url, timeout=3)
        print(f"Affinity health attempt {i+1}: {r.status_code}")
        if r.status_code == 200:
            print("Affinity service is ready.")
            sys.exit(0)
    except Exception as e:
        print(f"Affinity health attempt {i+1} failed: {e}")
    time.sleep(2)

print("Affinity service did not become ready.")
sys.exit(1)
PY

echo "Starting Generation API on 0.0.0.0:${PORT}..."
exec conda run --no-capture-output -n ailixir \
  uvicorn api:app \
  --host 0.0.0.0 \
  --port "${PORT}"
