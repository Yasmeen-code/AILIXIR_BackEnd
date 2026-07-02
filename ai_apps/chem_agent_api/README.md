---
title: Chem Agent API
emoji: 🧪
colorFrom: blue
colorTo: gray
sdk: docker
app_port: 7860
---

# Chem Agent API

Chemistry AI Agent REST API.

This service exposes endpoints for chemistry chat, thread creation, molecule analysis, comparison, docking, and CSV batch jobs.

## Run locally without Docker

```bash
uvicorn api:app --host 0.0.0.0 --port 8000 --reload
```

Local docs:

```text
http://localhost:8000/docs
```

Health check:

```bash
curl http://localhost:8000/health
```

## Hugging Face runtime

Hugging Face will run the service from the Dockerfile using:

```bash
uvicorn api:app --host 0.0.0.0 --port 7860
```

## Endpoints

### GET /health

Public endpoint.

Response:

```json
{
  "status": "ok",
  "version": "1.0.0"
}
```

### POST /thread/new

Creates a new conversation thread.

Requires auth token.

Headers:

```text
Authorization: Bearer <token>
Content-Type: application/json
```

Response:

```json
{
  "thread_id": "user_id_thread_id"
}
```

### POST /chat

Sends a message to the chemistry agent.

Requires auth token.

Headers:

```text
Authorization: Bearer <token>
Content-Type: application/json
```

Request:

```json
{
  "message": "What are the toxicity concerns?",
  "thread_id": "optional_thread_id"
}
```

Response:

```json
{
  "reply": "AI response here",
  "thread_id": "thread_id_here",
  "processing_time_ms": 1234
}
```

## Environment variables

See `.env.example`.

API keys and secrets must be configured in Hugging Face Space Secrets.

Do not commit `.env`.
