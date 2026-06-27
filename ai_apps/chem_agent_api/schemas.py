# schemas.py
"""
Pydantic request / response models shared across all routers.
Import from here — never define models inside routers.
"""

from typing import Optional
from pydantic import BaseModel


# ─────────────────────────────────────────────────────────────────────────────
# System
# ─────────────────────────────────────────────────────────────────────────────

class HealthResponse(BaseModel):
    status: str
    version: str


# ─────────────────────────────────────────────────────────────────────────────
# Threads
# ─────────────────────────────────────────────────────────────────────────────

class NewThreadResponse(BaseModel):
    thread_id: str


# ─────────────────────────────────────────────────────────────────────────────
# Chat
# ─────────────────────────────────────────────────────────────────────────────

class ChatRequest(BaseModel):
    message: str
    thread_id: Optional[str] = None

    class Config:
        json_schema_extra = {
            "example": {
                "message": "Is CC(=O)Oc1ccccc1C(=O)O a good drug candidate?",
                "thread_id": "optional-existing-thread-id",
            }
        }


class ChatResponse(BaseModel):
    reply: str
    thread_id: str
    processing_time_ms: int


# ─────────────────────────────────────────────────────────────────────────────
# Chemistry
# ─────────────────────────────────────────────────────────────────────────────

class DockingRequest(BaseModel):
    docking_data: str
    thread_id: Optional[str] = None

    class Config:
        json_schema_extra = {
            "example": {
                "docking_data": (
                    "CC(=O)Oc1ccccc1C(=O)O | -7.2 | 1.1 | H-bond to Ser195\n"
                    "CC(C)Cc1ccc(cc1)C(C)C(=O)O | -8.9 | 0.8 | deep pocket binding\n"
                    "Cn1cnc2c1c(=O)n(c(=O)n2C)C | -6.1 | 1.9 |"
                ),
                "thread_id": None,
            }
        }


# ─────────────────────────────────────────────────────────────────────────────
# CSV Batch
# ─────────────────────────────────────────────────────────────────────────────

class JobStatus(BaseModel):
    job_id: str
    status: str               # queued | running | done | failed
    total: int
    completed: int
    failed_rows: int
    progress_percent: float
    results: Optional[list] = None
    error: Optional[str] = None