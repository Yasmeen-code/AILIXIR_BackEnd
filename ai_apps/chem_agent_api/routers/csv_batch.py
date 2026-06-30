# routers/csv_batch.py
"""
CSV Batch router — async batch analysis of multiple molecules via CSV upload:
  POST   /csv/upload           upload CSV → returns job_id
  GET    /csv/status/{job_id}  poll job progress
  GET    /csv/results/{job_id} download completed results as CSV
  GET    /csv/jobs             list all jobs
  DELETE /csv/jobs/{job_id}    delete a completed job from memory
"""

import io

import pandas as pd
from fastapi import APIRouter, BackgroundTasks, File, HTTPException, UploadFile
from fastapi.responses import StreamingResponse

from schemas import JobStatus
from services.csv_jobs import (
    VALID_ANALYSIS_TYPES,
    create_job,
    delete_job,
    get_job,
    list_jobs,
    process_csv_job,
)


router = APIRouter(prefix="/csv", tags=["CSV Batch"])

MAX_ROWS = 100


# ─────────────────────────────────────────────────────────────────────────────
# Upload
# ─────────────────────────────────────────────────────────────────────────────

@router.post(
    "/upload",
    summary="Upload CSV for batch analysis",
)
async def upload_csv(
    background_tasks: BackgroundTasks,
    file: UploadFile = File(...),
    analysis_type: str = "full",
):
    """
    Upload a CSV file to run batch analysis on multiple molecules.

    Processing runs in the background — this endpoint returns immediately.
    Poll `GET /csv/status/{job_id}` to track progress.
    Download results with `GET /csv/results/{job_id}` once done.

    ---

    **Required CSV columns:**
    - `smiles` — SMILES string (required)
    - `name`   — compound name or ID (optional, auto-generated if missing)

    **analysis_type options:**

    | Value      | What it runs                           | Speed  |
    |------------|----------------------------------------|--------|
    | `full`     | Properties + drug-likeness + ADMET     | Slow   |
    | `quick`    | Lipinski pass/fail + QED only          | Fast   |
    | `admet`    | ADMET profile only                     | Medium |
    | `classify` | Drug-likeness classification only      | Fast   |

    **Limits:** Maximum 100 rows per upload. Split larger datasets into batches.

    **Example CSV:**
    ```csv
    name,smiles
    Aspirin,CC(=O)Oc1ccccc1C(=O)O
    Ibuprofen,CC(C)Cc1ccc(cc1)C(C)C(=O)O
    Caffeine,Cn1cnc2c1c(=O)n(c(=O)n2C)C
    ```
    """
    # ── Validate file type ───────────────────────────────────────────────────
    if not file.filename.lower().endswith(".csv"):
        raise HTTPException(
            status_code=400,
            detail="Only .csv files are accepted.",
        )

    # ── Validate analysis_type ───────────────────────────────────────────────
    if analysis_type not in VALID_ANALYSIS_TYPES:
        raise HTTPException(
            status_code=400,
            detail=f"analysis_type must be one of: {sorted(VALID_ANALYSIS_TYPES)}",
        )

    # ── Parse CSV ────────────────────────────────────────────────────────────
    try:
        contents = await file.read()
        df       = pd.read_csv(io.BytesIO(contents))
    except Exception as e:
        raise HTTPException(
            status_code=400,
            detail=f"Could not parse CSV file: {e}",
        )

    # ── Normalise column names ───────────────────────────────────────────────
    df.columns = df.columns.str.lower().str.strip()

    if "smiles" not in df.columns:
        raise HTTPException(
            status_code=400,
            detail=(
                "CSV must contain a 'smiles' column. "
                "Optional: 'name' column for compound identifiers. "
                f"Columns found: {list(df.columns)}"
            ),
        )

    if "name" not in df.columns:
        df["name"] = [f"compound_{i + 1}" for i in range(len(df))]

    # ── Row limits ───────────────────────────────────────────────────────────
    if len(df) == 0:
        raise HTTPException(status_code=400, detail="CSV file is empty.")

    if len(df) > MAX_ROWS:
        raise HTTPException(
            status_code=400,
            detail=(
                f"CSV has {len(df)} rows but the limit is {MAX_ROWS}. "
                "Please split into smaller batches."
            ),
        )

    # ── Create job and start background processing ───────────────────────────
    rows   = df[["name", "smiles"]].to_dict(orient="records")
    job_id = create_job(rows, analysis_type, file.filename)
    background_tasks.add_task(process_csv_job, job_id, rows, analysis_type)

    return {
        "job_id":        job_id,
        "total_rows":    len(rows),
        "analysis_type": analysis_type,
        "filename":      file.filename,
        "message": (
            f"Job queued successfully. "
            f"Poll GET /csv/status/{job_id} to track progress. "
            f"Download results with GET /csv/results/{job_id} once done."
        ),
    }


# ─────────────────────────────────────────────────────────────────────────────
# Status
# ─────────────────────────────────────────────────────────────────────────────

@router.get(
    "/status/{job_id}",
    response_model=JobStatus,
    summary="Poll CSV job status",
)
def csv_status(job_id: str):
    """
    Check the processing status of a CSV batch job.

    Poll every 5–10 seconds. Once `status == "done"`, call
    `GET /csv/results/{job_id}` to download the results CSV.

    **Status values:**

    | Value     | Meaning                                           |
    |-----------|---------------------------------------------------|
    | `queued`  | Job is waiting to start                           |
    | `running` | Actively processing rows                          |
    | `done`    | All rows finished — results are ready             |
    | `failed`  | Job-level error (row errors are inside `results`) |
    """
    job = get_job(job_id)
    if not job:
        raise HTTPException(
            status_code=404,
            detail=f"Job '{job_id}' not found.",
        )

    total     = job["total"]
    completed = job["completed"]
    progress  = round((completed / total * 100) if total > 0 else 0, 1)

    return JobStatus(
        job_id           = job_id,
        status           = job["status"],
        total            = total,
        completed        = completed,
        failed_rows      = job["failed_rows"],
        progress_percent = progress,
        results          = job["results"] if job["status"] == "done" else None,
    )


# ─────────────────────────────────────────────────────────────────────────────
# Download results
# ─────────────────────────────────────────────────────────────────────────────

@router.get(
    "/results/{job_id}",
    summary="Download completed results as CSV",
)
def csv_results(job_id: str):
    """
    Download the analysis results for a completed CSV batch job.

    Only available when `GET /csv/status/{job_id}` returns `status == "done"`.
    Returns a CSV file download (`text/csv`).

    **Output CSV columns:**
    - `row`      — original row number in the uploaded file
    - `name`     — compound name
    - `smiles`   — input SMILES string
    - `status`   — `success` or `failed`
    - `analysis` — full agent analysis text
    - `error`    — error message (empty if status is `success`)
    """
    job = get_job(job_id)
    if not job:
        raise HTTPException(
            status_code=404,
            detail=f"Job '{job_id}' not found.",
        )

    if job["status"] != "done":
        raise HTTPException(
            status_code=400,
            detail=(
                f"Job is not finished yet. "
                f"Current status: '{job['status']}'. "
                f"Progress: {job['completed']}/{job['total']} rows. "
                f"Poll GET /csv/status/{job_id} and retry when done."
            ),
        )

    df     = pd.DataFrame(job["results"])
    stream = io.StringIO()
    df.to_csv(stream, index=False)
    stream.seek(0)

    return StreamingResponse(
        iter([stream.getvalue()]),
        media_type="text/csv",
        headers={
            "Content-Disposition": f"attachment; filename=results_{job_id[:8]}.csv"
        },
    )


# ─────────────────────────────────────────────────────────────────────────────
# List all jobs
# ─────────────────────────────────────────────────────────────────────────────

@router.get(
    "/jobs",
    summary="List all CSV batch jobs",
)
def list_all_jobs():
    """
    List all CSV batch jobs and their current status.

    Useful for monitoring the processing queue.
    In production, filter by user/session to avoid exposing other users' jobs.
    """
    jobs = list_jobs()
    return {"total_jobs": len(jobs), "jobs": jobs}


# ─────────────────────────────────────────────────────────────────────────────
# Delete job
# ─────────────────────────────────────────────────────────────────────────────

@router.delete(
    "/jobs/{job_id}",
    summary="Delete a completed job from memory",
)
def delete_job_endpoint(job_id: str):
    """
    Remove a completed job and its results from server memory.

    Call this after successfully downloading results to keep memory usage low.
    Trying to delete a non-existent job returns 404.
    """
    deleted = delete_job(job_id)
    if not deleted:
        raise HTTPException(
            status_code=404,
            detail=f"Job '{job_id}' not found.",
        )
    return {"message": f"Job '{job_id}' deleted successfully."}