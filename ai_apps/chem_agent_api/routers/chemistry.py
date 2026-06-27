# routers/chemistry.py
"""
Chemistry router — shortcut endpoints for common analysis tasks:
  POST /analyze/smiles    full single-molecule analysis
  POST /analyze/compare   side-by-side multi-molecule comparison
  POST /analyze/docking   docking result ranking and recommendation
"""

import uuid
import time
from typing import Optional

from fastapi import APIRouter, HTTPException

from schemas import ChatRequest, ChatResponse, DockingRequest
from services.agent_service import run_agent, handle_llm_error


router = APIRouter(prefix="/analyze", tags=["Chemistry"])


# ─────────────────────────────────────────────────────────────────────────────
# Full single-molecule analysis
# ─────────────────────────────────────────────────────────────────────────────

@router.post(
    "/smiles",
    response_model=ChatResponse,
    summary="Full analysis of a single SMILES",
)
def analyze_smiles(smiles: str, thread_id: Optional[str] = None):
    """
    Shortcut endpoint — analyze one SMILES string with a single call.

    Automatically runs the full pipeline in order:
    1. Validate SMILES
    2. Compute molecular properties (MW, LogP, HBD, HBA, TPSA, QED, Fsp3)
    3. Drug-likeness classification (Lipinski Ro5, Veber, Lead-likeness)
    4. ADMET profile with structural toxicity alerts

    **Parameters:**
    - `smiles`: SMILES string as a query parameter.
      URL-encode special characters, e.g. `CC%28%3DO%29Oc1ccccc1C%28%3DO%29O`
    - `thread_id`: Optional. Pass to link this analysis to an existing conversation.

    **Example:**
    ```
    POST /analyze/smiles?smiles=CC(=O)Oc1ccccc1C(=O)O
    ```
    """
    thread_id = thread_id or str(uuid.uuid4())
    message   = (
        f"Run a complete analysis on this molecule: {smiles}. "
        f"Include molecular properties, drug-likeness classification, and ADMET profile."
    )
    start = time.time()

    try:
        reply = run_agent(thread_id, message)
    except HTTPException:
        raise
    except Exception as e:
        handle_llm_error(e)

    return ChatResponse(
        reply              = reply,
        thread_id          = thread_id,
        processing_time_ms = int((time.time() - start) * 1000),
    )


# ─────────────────────────────────────────────────────────────────────────────
# Multi-molecule comparison
# ─────────────────────────────────────────────────────────────────────────────

@router.post(
    "/compare",
    response_model=ChatResponse,
    summary="Compare multiple SMILES side by side",
)
def compare_smiles(request: ChatRequest):
    """
    Compare 2 or more molecules and receive a recommendation.

    **Message format** — comma-separated SMILES in the `message` field:
    ```
    CC(=O)Oc1ccccc1C(=O)O, CC(C)Cc1ccc(cc1)C(C)C(=O)O, Cn1cnc2c1c(=O)n(c(=O)n2C)C
    ```

    The agent returns:
    - A side-by-side property table (MW, LogP, HBD, HBA, TPSA, QED, Fsp3)
    - Drug-likeness pass/fail for each molecule
    - A named recommendation with justification
    """
    thread_id = request.thread_id or str(uuid.uuid4())
    message   = (
        f"Compare these molecules and recommend the best drug candidate. "
        f"SMILES list: {request.message}"
    )
    start = time.time()

    try:
        reply = run_agent(thread_id, message)
    except HTTPException:
        raise
    except Exception as e:
        handle_llm_error(e)

    return ChatResponse(
        reply              = reply,
        thread_id          = thread_id,
        processing_time_ms = int((time.time() - start) * 1000),
    )


# ─────────────────────────────────────────────────────────────────────────────
# Docking result analysis
# ─────────────────────────────────────────────────────────────────────────────

@router.post(
    "/docking",
    response_model=ChatResponse,
    summary="Analyze and rank docking results",
)
def analyze_docking(request: DockingRequest):
    """
    Analyze molecular docking results and recommend the best candidate.

    The agent combines binding affinity ranking with drug-likeness scoring
    to give a final, justified recommendation.

    **docking_data format** — one molecule per line:
    ```
    SMILES | binding_affinity_kcal_mol | rmsd_angstrom | optional_notes
    ```

    **Example:**
    ```
    CC(=O)Oc1ccccc1C(=O)O         | -7.2 | 1.1 | H-bond to Ser195
    CC(C)Cc1ccc(cc1)C(C)C(=O)O    | -8.9 | 0.8 | deep pocket binding
    Cn1cnc2c1c(=O)n(c(=O)n2C)C    | -6.1 | 1.9 |
    ```

    **Interpretation rules used by the agent:**
    - ΔG more negative = stronger binding
    - RMSD < 2 Å = reliable pose; > 2 Å = uncertain binding mode
    - Best candidate = strongest binder that also passes Lipinski Ro5
    """
    thread_id = request.thread_id or str(uuid.uuid4())
    message   = (
        f"Analyze these docking results and recommend the best candidate:\n"
        f"{request.docking_data}"
    )
    start = time.time()

    try:
        reply = run_agent(thread_id, message)
    except HTTPException:
        raise
    except Exception as e:
        handle_llm_error(e)

    return ChatResponse(
        reply              = reply,
        thread_id          = thread_id,
        processing_time_ms = int((time.time() - start) * 1000),
    )