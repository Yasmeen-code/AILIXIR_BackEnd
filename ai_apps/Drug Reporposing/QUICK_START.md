# 🧬 Drug Repurposing API - QUICK REFERENCE

## 🚀 Get Started in 60 Seconds

```bash
# Windows
start.bat

# Linux/Mac
chmod +x start.sh && ./start.sh
```

Wait for "✅ SETUP COMPLETE" message, then visit: **http://localhost:8000/docs**

---

## 🎯 Main Endpoint

### POST `/api/v1/screen`

**Request:**
```json
{
  "disease_name": "Type 2 Diabetes",
  "min_score": 0.5,
  "top_n_targets": 10,
  "known_drugs": ["Metformin"]
}
```

**Response:**
```json
{
  "disease": "Type 2 Diabetes",
  "total_targets": 10,
  "total_drugs": 200,
  "total_predictions": 2000,
  "top_results": [
    {
      "drug_name": "Drug_DB00838",
      "target_symbol": "GCK",
      "score": 0.92,
      "status": "✅ Known Treatment"
    }
  ],
  "success": true,
  "message": "✅ Screening completed in 45.23s"
}
```

---

## 📊 Other Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/health` | GET | Check API status |
| `/api/v1/model-status` | GET | Check AI model info |
| `/api/v1/disease-targets` | POST | Get disease targets |
| `/api/v1/protein-sequences` | POST | Get protein sequences |
| `/api/v1/drug-library` | GET | Get drug library |

---

## 🔍 Check Status

```bash
curl http://localhost:8000/health
curl http://localhost:8000/api/v1/model-status
```

---

## ⚙️ Configuration

Edit `app/config.py` to adjust:
- `MAX_DRUGS_FOR_DEMO`: Number of drugs to screen
- `MAX_TARGETS`: Number of disease targets
- `BATCH_SIZE`: Optimization for GPU/CPU

---

## 🆘 Troubleshooting

| Problem | Solution |
|---------|----------|
| API won't start | Ensure Python 3.10+ installed |
| DeepPurpose missing | `pip install git+https://github.com/kexinhuang12345/DeepPurpose.git` |
| GPU not detected | Install PyTorch CUDA: `pip install torch torchvision --index-url https://download.pytorch.org/whl/cu121` |
| Slow predictions | System uses CPU - GPU dramatically faster |
| No API docs | Visit http://localhost:8000/docs |

---

## 📈 Performance

| Config | Speed | Throughput |
|--------|-------|-----------|
| GPU | ~5s | 1,200 pairs/sec |
| CPU | ~30s | 67 pairs/sec |

---

## 📚 Full Documentation

- **PRODUCTION_GUIDE.md** - Complete guide
- **IMPLEMENTATION_SUMMARY.md** - What was built
- **http://localhost:8000/docs** - Interactive API docs

---

## 🎓 Pipeline Stages

```
Disease Input
    ↓
[1] Disease → Targets (OpenTargets API)
    ↓
[2] Targets → Sequences (UniProt API)
    ↓
[3] Load Drug Library (TDC)
    ↓
[4] AI Screening (DeepPurpose MPNN_CNN)
    ↓ GPU CUDA acceleration
    ↓
[5] Process Results
    ↓
Ranked Drug Candidates
```

---

## 🔧 Dependencies

**Minimum**: Python 3.10, pip, 8GB RAM
**Recommended**: GPU with CUDA 12.0+, 16GB RAM

**Auto-installed by start scripts**:
- FastAPI
- PyTorch
- DeepPurpose (AI model)
- TDC (drug data)

---

**Version**: 1.0.0 | **Status**: Production-Ready ✅
