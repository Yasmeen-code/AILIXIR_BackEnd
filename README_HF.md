---
title: AILIXIR - Backend API
emoji: 🧠
colorFrom: blue
colorTo: indigo
sdk: docker
app_port: 7680
---

# AILIXIR Backend API

Laravel backend with SQLite database for the AILIXIR drug discovery platform.

- **Port:** 7680
- **SDK:** Docker
- **Database:** SQLite

## Endpoints

- `GET /api/ai-services/health` — Health check
- `POST /api/admet/predict` — ADMET prediction
- `POST /api/chemical-search` — Chemical search
- `POST /api/user/register` — User registration
- `POST /api/user/login` — User login
