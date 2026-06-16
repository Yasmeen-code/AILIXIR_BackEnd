"""
Screening Cache — persists screening results so identical requests skip recomputation.
"""
import json
import os
import hashlib
from typing import Optional

CACHE_DIR = os.path.join(
    os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
    "data",
    "screening_cache"
)


def _ensure_cache_dir():
    os.makedirs(CACHE_DIR, exist_ok=True)


def _cache_key(request) -> str:
    raw = f"{request.disease_name}_{request.top_n_targets}_{request.min_score}_{hash(tuple(request.known_drugs or []))}"
    return hashlib.md5(raw.encode()).hexdigest()


def load_cached_screening(request) -> Optional[dict]:
    path = os.path.join(CACHE_DIR, f"{_cache_key(request)}.json")
    if os.path.exists(path):
        try:
            with open(path) as f:
                return json.load(f)
        except (json.JSONDecodeError, IOError):
            return None
    return None


def save_cached_screening(request, data: dict):
    _ensure_cache_dir()
    path = os.path.join(CACHE_DIR, f"{_cache_key(request)}.json")
    try:
        with open(path, "w") as f:
            json.dump(data, f, indent=2)
    except IOError:
        pass
