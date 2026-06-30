# routers/chat.py
"""
Chat router — covers:
  GET  /health        system health check
  POST /thread/new    create a new conversation thread
  POST /chat          send a message to the chemistry agent
"""

import uuid
import time

from fastapi import APIRouter, HTTPException

from schemas import (
    HealthResponse,
    NewThreadResponse,
    ChatRequest,
    ChatResponse,
)
from services.agent_service import run_agent, handle_llm_error


router = APIRouter()


# ─────────────────────────────────────────────────────────────────────────────
# Health check
# ─────────────────────────────────────────────────────────────────────────────

@router.get(
    "/health",
    response_model=HealthResponse,
    tags=["System"],
    summary="Health check",
)
def health_check():
    """
    Returns 200 OK if the API is running.
    Use this for uptime monitoring and load balancer health probes.
    """
    return {"status": "ok", "version": "1.0.0"}


# ─────────────────────────────────────────────────────────────────────────────
# Thread management
# ─────────────────────────────────────────────────────────────────────────────

@router.post(
    "/thread/new",
    response_model=NewThreadResponse,
    tags=["Threads"],
    summary="Create a new conversation thread",
)
def new_thread():
    """
    Create a new isolated conversation thread.

    Returns a `thread_id` that must be included in all `/chat` calls
    to maintain conversation memory across multiple turns.

    **Rule:** one `thread_id` per user session.
    Do not share thread IDs across different users.
    """
    return {"thread_id": str(uuid.uuid4())}


# ─────────────────────────────────────────────────────────────────────────────
# Main chat endpoint
# ─────────────────────────────────────────────────────────────────────────────

@router.post(
    "/chat",
    response_model=ChatResponse,
    tags=["Chat"],
    summary="Send a message to the chemistry agent",
)
def chat(request: ChatRequest):
    """
    Send any chemistry question to the AI agent in natural language.

    **Memory:** Pass the same `thread_id` across multiple calls to maintain
    conversation history. The agent remembers all prior messages on that thread.
    Omit `thread_id` to start a fresh conversation automatically.

    **The agent automatically handles:**
    - SMILES validation
    - Molecular property calculation (MW, LogP, HBD, HBA, TPSA, QED, Fsp3)
    - Drug-likeness rules (Lipinski Ro5, Veber, Lead-likeness)
    - ADMET profiling with structural toxicity alerts
    - Docking result ranking and recommendation
    - Multi-molecule comparison

    **Example messages:**
    - `"Is CC(=O)Oc1ccccc1C(=O)O a good drug candidate?"`
    - `"Compare aspirin and ibuprofen CC(C)Cc1ccc(cc1)C(C)C(=O)O"`
    - `"What are the toxicity concerns for this molecule?"` ← follow-up
    - `"Which molecule we discussed has the best CNS penetration?"`
    """
    thread_id = request.thread_id or str(uuid.uuid4())
    start     = time.time()

    try:
        reply = run_agent(thread_id, request.message)
    except HTTPException:
        raise
    except Exception as e:
        handle_llm_error(e)

    return ChatResponse(
        reply              = reply,
        thread_id          = thread_id,
        processing_time_ms = int((time.time() - start) * 1000),
    )