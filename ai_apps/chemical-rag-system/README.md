# Chemical RAG System v2.3

**Drug-discovery grade chemical similarity search with chemical-aware reranking.**

A production-ready FastAPI service that indexes 1M+ chemical compounds using FAISS binary retrieval, multi-fingerprint fusion (Morgan, MACCS, Atom Pairs, Topological Torsions), chemical-aware drug-discovery constraints, and optional Llama-3.1-8B LLM explanations.

---

## Architecture: 5-Layer Pipeline

```
Client (HTTP) → FastAPI Router → FAISS Binary Retrieval → Multi-Fingerprint Fusion
  → Chemical-Aware Reranking → Z-Score Calibration → MMR Diversity → Response
```

| Layer | Purpose | Speed |
|-------|---------|-------|
| 1. FAISS Binary Retrieval | Ultra-fast broad screen via Morgan fingerprints (Top 200) | <1ms |
| 2. Multi-Fingerprint Fusion | 4 fingerprint types with domain-optimized weights | ~10ms |
| 3. Chemical-Aware Reranking | Aromaticity bonus, ring-system bonus, charge penalty, fragment penalty | O(1) |
| 4. Z-Score Calibration | Normalize scores → sigmoid probability distribution | <1ms |
| 5. MMR Diversity Control | Eliminate redundant scaffolds (greedy selection, λ=0.6) | <5ms |

---

## API Endpoints

| Endpoint | Method | Description | Latency |
|----------|--------|-------------|---------|
| `/search/retrieval-only` | POST | FAISS retrieval only, no LLM | ~55ms |
| `/search/full-rag` | POST | Retrieval + Llama-3.1-8B explanations | ~500ms |
| `/health` | GET | System health & features | — |
| `/stats` | GET | Compound count, index info, metrics | — |
| `/` | GET | Root with endpoint list | — |

### Request Format

```json
{
  "smiles": "CCO",
  "top_k": 5,
  "explain": true
}
```

### Response Format

```json
{
  "results": [{
    "smiles": "CCO",
    "similarity_score": 1.0,
    "calibrated_score": 0.9997,
    "image": "/static/images/12345.png",
    "explanation": "Exact match — ethanol",
    "cid": "702",
    "name": "Ethanol"
  }],
  "query_smiles": "CCO",
  "total_results": 5
}
```

---

## Quick Start

```bash
# 1. Create virtual environment
python -m venv .venv
source .venv/bin/activate   # Linux/macOS
.venv\Scripts\activate      # Windows

# 2. Install dependencies
pip install -r requirements.txt

# 3. Start server (auto-detects data, builds index on first run)
python run_server.py

# 4. Query (in another terminal)
curl -X POST http://127.0.0.1:8000/search/retrieval-only \
  -H 'Content-Type: application/json' \
  -d '{"smiles":"CCO","top_k":5}'
```

### Docker

```bash
docker compose up -d
curl http://localhost:5000/health
```

---

## Technology Stack

| Component | Technology |
|-----------|-----------|
| API Framework | FastAPI 0.104.1 + Uvicorn 0.24.0 |
| Search Engine | FAISS IndexBinaryFlat (Hamming distance) |
| Fingerprinting | RDKit 2026.03 (Morgan 2048-bit, MACCS, Atom Pairs, Torsions) |
| Similarity Metric | Exact Tanimoto (industry standard) |
| LLM | Llama-3.1-8B-Instruct via HuggingFace Inference API |
| Data Source | PubChem (batch ingestion with chemical filtering) |
| Validation | Pydantic 2.5.0 |
| Containerization | Docker + Docker Compose |
| Mobile Integration | Flutter-compatible REST API |

---

## Project Structure

```
chemical-rag-system/
├── app/
│   ├── main.py              # FastAPI routes & startup
│   ├── engine.py            # ChemicalSearchEngine (5-layer pipeline)
│   ├── services.py          # Business logic & auto-initialization
│   ├── generation.py        # LLM explanation generator (Llama-3.1-8B)
│   ├── schemas.py           # Pydantic request/response models
│   ├── utils.py             # SMILES → molecule image rendering
│   ├── ingest_handler.py    # Auto-detection & ingestion trigger
│   ├── tett_diff_fingerprint.py  # Fingerprint comparison scratchpad
│   └── static/images/       # Cached molecule PNGs
├── data/
│   ├── compounds.json       # Compound dataset (auto-ingested)
│   ├── compounds_index.pkl  # Pickled engine state (metadata + fingerprints)
│   └── compounds_index.faiss # FAISS binary index
├── digrams/                 # Architecture diagrams (Class, Sequence, Deployment, etc.)
├── ingest.py                # PubChem batch ingestion (batching, retries, filtering)
├── run_server.py            # Server launcher with Docker auto-detection
├── Dockerfile               # Container definition
├── docker-compose.yml       # Docker orchestration
├── requirements.txt         # Python dependencies
├── package.json             # Project metadata
├── .env.docker              # Docker environment config
└── README.md                # This file
```

---

## Key Features

- **5-layer drug-discovery pipeline**: FAISS → Multi-FP → Chemical-Aware → Calibration → MMR
- **4 fingerprint types**: Morgan (structural), MACCS (functional groups), Atom Pairs (geometry), Torsions (conformation)
- **Chemical-aware constraints**: Aromaticity bonus, ring-system bonus, charge penalty (Lipinski-aligned), fragment penalty
- **Statistical calibration**: Z-score normalization + logistic sigmoid → [0,1] probability
- **MMR diversity**: Eliminates redundant scaffolds, controlled by lambda parameter (0.0-1.0)
- **Auto-detection**: Zero-configuration startup — automatically ingests data and builds index
- **Dual endpoints**: Fast retrieval-only (~55ms) or full RAG with LLM explanations (~500ms)
- **LLM fallback**: Heuristic explanations when HuggingFace API is unavailable
- **Persistent caching**: FAISS index + fingerprint data saved to disk for instant reload
- **Docker smart config**: Auto-detects Docker environment and binds correct host/port
- **Mobile ready**: REST API formatted for Flutter / React Native integration
- **Interactive docs**: Swagger UI at `/docs`, ReDoc at `/redoc`

---

## Configuration

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `HF_TOKEN` | — | HuggingFace API token for Llama-3.1-8B |
| `API_PORT` | 8000 (local) / 7860 (Docker) | Server port |
| `BASE_URL` | `https://test.com` | Base URL for molecule image URLs |

### Search Parameters

| Parameter | Type | Default | Max | Description |
|-----------|------|---------|-----|-------------|
| `smiles` | string | — | — | Query molecule in SMILES notation |
| `top_k` | int | 3 | 100 | Number of results to return |
| `explain` | bool | true | — | Generate LLM explanation (full-rag only) |

### MMR Lambda (engine-level)

- `1.0` = Pure relevance (no diversity)
- `0.6` = Balanced (recommended default)
- `0.0` = Maximum diversity

---

## Deployment Options

**Native**: `python run_server.py` (auto-detects Docker)

**Docker**: `docker compose up -d` (port 5000)

**Systemd**: Service file included for Linux production deployment

**Gunicorn + Nginx**: For high-performance production with reverse proxy

---

## v2.3 New in This Release

- Chemical-aware reranking layer with aromaticity, ring, charge, and fragment penalties
- Pre-computed chemical features cached in metadata (zero overhead during search)
- Drug-discovery grade ranking aligned with Lipinski Rule of Five
- Full release notes: [V2.3_RELEASE_NOTES.md](V2.3_RELEASE_NOTES.md)

## v2.2 Historical

- Multi-fingerprint fusion (4 fingerprint types with optimized weights)
- Z-score calibration with sigmoid transformation
- MMR diversity control replacing redundancy-heavy results
- Full release notes: [V2.2_RELEASE_NOTES.md](V2.2_RELEASE_NOTES.md)

---

## License

MIT — Built with RDKit, FAISS, FastAPI, and PubChem data.
