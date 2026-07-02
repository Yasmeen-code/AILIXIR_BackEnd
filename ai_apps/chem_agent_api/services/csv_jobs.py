# services/csv_jobs.py
"""
CSV batch job store and background processor.

Responsibilities:
  - Maintain the in-memory jobs dictionary (thread-safe via Lock)
  - Provide CRUD helpers used by routers/csv_batch.py
  - Run the background processing loop (process_csv_job)

In production, replace the in-memory dict with Redis or a database.
"""

import uuid
import time
import threading
from typing import Optional

from services.agent_service import run_agent


# ─────────────────────────────────────────────────────────────────────────────
# Job store
# ─────────────────────────────────────────────────────────────────────────────

_jobs: dict = {}
_lock = threading.Lock()


# ─────────────────────────────────────────────────────────────────────────────
# Message templates per analysis type
# ─────────────────────────────────────────────────────────────────────────────

MESSAGE_TEMPLATES: dict[str, str] = {
    "full": (
        "Run a complete analysis on this molecule: {smiles}. "
        "Include molecular properties, drug-likeness classification, and ADMET profile."
    ),
    "quick": (
        "Give a brief drug-likeness summary for: {smiles}. "
        "Just Lipinski pass/fail, QED score, and a one-sentence conclusion."
    ),
    "admet": (
        "Estimate ADMET properties for: {smiles}. "
        "Focus on absorption, toxicity flags, and overall suitability."
    ),
    "classify": (
        "Classify drug-likeness only for: {smiles}. "
        "Apply Lipinski Ro5, Veber rules, and lead-likeness."
    ),
}

VALID_ANALYSIS_TYPES = set(MESSAGE_TEMPLATES.keys())


# ─────────────────────────────────────────────────────────────────────────────
# Public helpers (used by routers/csv_batch.py)
# ─────────────────────────────────────────────────────────────────────────────

def create_job(rows: list, analysis_type: str, filename: str) -> str:
    """
    Register a new job in the store and return its job_id.
    The caller is responsible for kicking off process_csv_job() in the background.
    """
    job_id = str(uuid.uuid4())
    with _lock:
        _jobs[job_id] = {
            "status":        "queued",
            "total":         len(rows),
            "completed":     0,
            "failed_rows":   0,
            "results":       None,
            "analysis_type": analysis_type,
            "filename":      filename,
        }
    return job_id


def get_job(job_id: str) -> Optional[dict]:
    """Return a snapshot of the job dict, or None if not found."""
    with _lock:
        job = _jobs.get(job_id)
        return dict(job) if job else None


def list_jobs() -> list[dict]:
    """Return a summary list of all jobs (no results payload)."""
    with _lock:
        return [
            {
                "job_id":        jid,
                "status":        j["status"],
                "filename":      j.get("filename", "unknown"),
                "analysis_type": j.get("analysis_type", "unknown"),
                "total":         j["total"],
                "completed":     j["completed"],
                "failed_rows":   j["failed_rows"],
                "progress":      f"{j['completed']}/{j['total']}",
            }
            for jid, j in _jobs.items()
        ]


def delete_job(job_id: str) -> bool:
    """
    Remove a job from the store.
    Returns True if deleted, False if job_id was not found.
    """
    with _lock:
        if job_id not in _jobs:
            return False
        del _jobs[job_id]
        return True


# ─────────────────────────────────────────────────────────────────────────────
# Background processor
# ─────────────────────────────────────────────────────────────────────────────

def process_csv_job(job_id: str, rows: list, analysis_type: str) -> None:
    """
    Background task — invoked via FastAPI BackgroundTasks.

    Iterates over each CSV row, calls the agent, and writes results back
    into the job store incrementally so polling reflects live progress.

    Each molecule gets an isolated thread_id so agent memory does not
    bleed between compounds.

    A 1.2-second sleep between rows prevents hammering the LLM API rate limit.
    """
    # Mark job as running
    with _lock:
        _jobs[job_id]["status"] = "running"

    results: list = []
    failed: int   = 0

    for i, row in enumerate(rows):
        smiles = str(row.get("smiles", "")).strip()
        name   = str(row.get("name",   f"compound_{i + 1}")).strip()

        # ── Skip empty SMILES ────────────────────────────────────────────────
        if not smiles:
            failed += 1
            results.append({
                "row":      i + 1,
                "name":     name,
                "smiles":   smiles,
                "status":   "failed",
                "error":    "Empty SMILES string",
                "analysis": "",
            })
            with _lock:
                _jobs[job_id]["completed"]  += 1
                _jobs[job_id]["failed_rows"] = failed
            continue

        # ── Call agent ───────────────────────────────────────────────────────
        message   = MESSAGE_TEMPLATES[analysis_type].format(smiles=smiles)
        thread_id = str(uuid.uuid4())   # isolated per molecule

        try:
            reply = run_agent(thread_id, message)
            results.append({
                "row":      i + 1,
                "name":     name,
                "smiles":   smiles,
                "status":   "success",
                "error":    "",
                "analysis": reply,
            })
        except Exception as e:
            failed += 1
            results.append({
                "row":      i + 1,
                "name":     name,
                "smiles":   smiles,
                "status":   "failed",
                "error":    str(e),
                "analysis": "",
            })

        # ── Update progress ──────────────────────────────────────────────────
        with _lock:
            _jobs[job_id]["completed"]  += 1
            _jobs[job_id]["failed_rows"] = failed

        # Rate-limit buffer between LLM calls
        time.sleep(1.2)

    # ── Mark done ────────────────────────────────────────────────────────────
    with _lock:
        _jobs[job_id]["status"]  = "done"
        _jobs[job_id]["results"] = results