"""
Chemistry AI Agent — REST API entry point.

This file only wires together the app, middleware, and routers.
All logic lives in:
  schemas.py                   ← Pydantic models
  services/agent_service.py    ← LLM runner + error handler
  services/csv_jobs.py         ← batch job store + processor
  routers/chat.py              ← /health  /thread/new  /chat
  routers/chemistry.py         ← /analyze/smiles  /compare  /docking
  routers/csv_batch.py         ← /csv/upload  /status  /results  /jobs

Run with:
    uvicorn api:app --host 0.0.0.0 --port 8000 --reload

Interactive docs:
    http://localhost:8000/docs      ← Swagger UI
    http://localhost:8000/redoc     ← ReDoc
"""

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from routers.chat import router as chat_router
from routers.chemistry import router as chemistry_router
from routers.csv_batch import router as csv_router


# ─────────────────────────────────────────────────────────────────────────────
# App
# ─────────────────────────────────────────────────────────────────────────────

app = FastAPI(
    title="Chemistry AI Agent API",
    description=(
        "AI-powered chemistry analysis agent.\n\n"
        "**Capabilities:**\n"
        "- SMILES validation and property calculation\n"
        "- Drug-likeness classification (Lipinski Ro5, Veber, Lead-likeness)\n"
        "- ADMET profiling with structural toxicity alerts\n"
        "- Molecular docking result ranking and recommendation\n"
        "- Multi-molecule side-by-side comparison\n"
        "- Async CSV batch processing\n\n"
        "**Memory:** All `/chat` and `/analyze` endpoints maintain "
        "conversation context via `thread_id`."
    ),
    version="1.0.0",
)


# ─────────────────────────────────────────────────────────────────────────────
# Middleware
# ─────────────────────────────────────────────────────────────────────────────

# Allow all origins during development.
# In production restrict allow_origins to your frontend domain:
#   allow_origins=["https://yourapp.com"]
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# ─────────────────────────────────────────────────────────────────────────────
# Routers
# ─────────────────────────────────────────────────────────────────────────────

app.include_router(chat_router)        # /health  /thread/new  /chat
app.include_router(chemistry_router)   # /analyze/smiles  /compare  /docking
app.include_router(csv_router)         # /csv/upload  /status  /results  /jobs