---
title: AILIXIR - Drug Repurposing
emoji: 💊
colorFrom: green
colorTo: blue
sdk: docker
app_port: 7860
---

# Drug Repurposing Service

AI-powered drug repurposing platform for identifying potential therapeutic uses of existing drugs.

- **Port:** 7860
- **SDK:** Docker

## Endpoints

- `GET /health` — Health check
- `POST /api/v1/disease-targets` — Get disease targets
- `POST /api/v1/protein-sequences` — Fetch protein sequences
- `GET /api/v1/drug-library` — Available FDA drugs
- `POST /api/v1/screen` — Run full screening pipeline
