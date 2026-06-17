---
title: AILIXIR - ADMET Prediction
emoji: 🧬
colorFrom: blue
colorTo: green
sdk: docker
app_port: 7860
---

# ADMET Prediction Service

Drug ADMET property prediction using pre-trained MPNN models.

- **Port:** 7860
- **SDK:** Docker

## Endpoints

- `GET /health` — Health check
- `GET /models/status` — Model availability
- `POST /predict` — Single molecule prediction
- `POST /predict_batch` — Batch prediction

## Usage

```bash
curl -X POST https://shdwRow-ailixir-admet.hf.space/predict \
  -H "Content-Type: application/json" \
  -d '{"smiles": "c1ccccc1"}'
```
