"""
FastAPI Application for Drug Repurposing System
"""
import logging
from fastapi import FastAPI, HTTPException, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
import time

from app.config import settings
from app.models import (
    DiseaseSearchRequest,
    ScreeningRequest,
    ScreeningResponse,
    DrugCandidate,
    HealthCheckResponse,
    ErrorResponse,
    EnrichedTargetResponse
)
from app.pipelines.screening_cache import load_cached_screening, save_cached_screening
from app.pipelines import (
    DiseaseTargetPipeline,
    ProteinSequencePipeline,
    DrugLibraryPipeline,
    AIScreeningPipeline,
    ResultProcessingPipeline,
    PdbStructurePipeline
)

# Configure logging
logging.basicConfig(
    level=settings.LOG_LEVEL,
    format=settings.LOG_FORMAT
)
logger = logging.getLogger(__name__)

# Initialize FastAPI app
app = FastAPI(
    title=settings.API_TITLE,
    description=settings.APP_DESCRIPTION,
    version=settings.API_VERSION,
    docs_url="/docs",
    openapi_url="/openapi.json"
)

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.CORS_ORIGINS,
    allow_credentials=settings.CORS_CREDENTIALS,
    allow_methods=settings.CORS_METHODS,
    allow_headers=settings.CORS_HEADERS,
)

# Initialize pipelines
disease_pipeline = DiseaseTargetPipeline(api_url=settings.OPENTARGETS_API_URL)
protein_pipeline = ProteinSequencePipeline(api_url=settings.UNIPROT_API_URL)
drug_pipeline = DrugLibraryPipeline(use_mock=settings.USE_MOCK_DRUGS)
ai_pipeline = AIScreeningPipeline(use_mock=settings.USE_MOCK_MODEL)
result_pipeline = ResultProcessingPipeline()
pdb_pipeline = PdbStructurePipeline(api_url=settings.UNIPROT_API_URL)

# Load AI model at startup
@app.on_event("startup")
async def startup_event():
    """Initialize AI model and drug library at startup - PRODUCTION MODE"""
    logger.info(f"{'='*70}")
    logger.info(f"Starting {settings.APP_NAME} v{settings.APP_VERSION}")
    logger.info(f"{'='*70}")
    logger.info(f"Device: {settings.DEVICE} {'(GPU)' if settings.HAS_GPU else '(CPU)'}")
    logger.info(f"Max drugs per screening: {settings.MAX_DRUGS_FOR_DEMO}")
    logger.info(f"Model: {settings.DEEP_PURPOSE_MODEL}")
    logger.info(f"Dataset: {settings.TDC_DATASET}")
    
    try:
        if ai_pipeline.use_mock:
            logger.info("\n[1/2] Mock mode enabled - skipping DeepPurpose model loading")
            logger.info("[2/2] Mock drug library enabled - skipping TDC download")
            logger.info(f"\n{'='*70}")
            logger.info("✅ MOCK MODE: All systems ready")
            logger.info("   - Using mock predictions (random scores)")
            logger.info("   - Drug library: local fallback")
            logger.info(f"{'='*70}\n")
        else:
            logger.info("\n[1/2] Loading DeepPurpose MPNN_CNN_BindingDB model...")
            ai_pipeline.load_model(model_name=settings.DEEP_PURPOSE_MODEL)
            logger.info("✅ AI model loaded successfully\n")
            
            logger.info("[2/2] Verifying drug library...")
            test_drugs = drug_pipeline.load_drug_library(
                dataset_name=settings.TDC_DATASET, 
                max_drugs=10
            )
            logger.info(f"✅ Drug library ready - {len(test_drugs)} sample drugs loaded\n")
            
            logger.info(f"{'='*70}")
            logger.info("✅ PRODUCTION MODE: All systems ready")
            logger.info("   - Real DeepPurpose MPNN_CNN predictions enabled")
            logger.info("   - Drug library enabled (Official TDC or Local Fallback)")
            logger.info("   - No mock predictions active")
            logger.info(f"{'='*70}\n")
        
    except ImportError as e:
        logger.error(f"\n{'='*70}")
        logger.error("❌ STARTUP FAILED: Missing required dependencies")
        logger.error(f"{'='*70}")
        logger.error(f"Error: {str(e)}\n")
        if not ai_pipeline.use_mock:
            logger.error("Install required packages:\n")
            logger.error("  DeepPurpose and dependencies are REQUIRED")
            logger.error("  Check terminal output above for pip install commands\n")
        raise
        
    except RuntimeError as e:
        logger.error(f"\n{'='*70}")
        logger.error("❌ STARTUP FAILED: Runtime error")
        logger.error(f"{'='*70}")
        logger.error(f"Error: {str(e)}\n")
        raise
        
    except Exception as e:
        logger.error(f"\n{'='*70}")
        logger.error(f"❌ STARTUP FAILED: Unexpected error")
        logger.error(f"{'='*70}")
        logger.error(f"Error: {str(e)}\n")
        if not ai_pipeline.use_mock:
            import traceback
            traceback.print_exc()
        raise


# Health Check Endpoint
@app.get("/health", response_model=HealthCheckResponse)
async def health_check():
    """Check API health status"""
    return HealthCheckResponse(
        status="healthy",
        version=settings.APP_VERSION,
        service=settings.APP_NAME
    )


# Model Status Endpoint
@app.get("/api/v1/model-status")
async def model_status():
    """Get information about the loaded AI model"""
    model_info = ai_pipeline.get_model_info()
    return {
        "model": model_info['model_name'],
        "device": model_info['device'],
        "gpu_available": model_info['gpu_available'],
        "model_loaded": model_info['is_loaded'],
        "using_mock_mode": model_info['using_mock'],
        "batch_size": model_info['batch_size'],
        "max_drugs_per_screening": settings.MAX_DRUGS_FOR_DEMO,
        "version": settings.APP_VERSION
    }


# Root Endpoint
@app.get("/")
async def root():
    """Root endpoint with API information"""
    return {
        "name": settings.APP_NAME,
        "version": settings.APP_VERSION,
        "description": settings.APP_DESCRIPTION,
        "docs": "/docs",
        "health": "/health"
    }


# Disease Targets Endpoint (enriched with sequences + PDB IDs)
@app.post("/api/v1/disease-targets", response_model=EnrichedTargetResponse)
async def get_disease_targets(request: DiseaseSearchRequest):
    """
    Get target proteins associated with a disease using Open Targets API,
    enriched with protein sequences (UniProt) and PDB structure IDs.
    
    - **disease_name**: Name of the disease to search for
    - **top_n**: Number of top targets to retrieve (1-100, default: 10)
    """
    try:
        logger.info(f"Searching targets for disease: {request.disease_name}")

        disease_id = disease_pipeline.fetch_disease_id(request.disease_name)
        
        targets = disease_pipeline.get_disease_targets(
            disease_name=request.disease_name,
            top_n=request.top_n
        )
        
        if not targets:
            raise HTTPException(
                status_code=404,
                detail=f"No targets found for disease: {request.disease_name}"
            )

        targets_with_seqs = protein_pipeline.get_protein_sequences(targets)

        if not targets_with_seqs:
            raise HTTPException(
                status_code=400,
                detail="Could not fetch sequences for any targets"
            )

        enriched = pdb_pipeline.fetch_pdb_ids(targets_with_seqs)
        
        return {
            "disease": request.disease_name,
            "disease_id": disease_id or "unknown",
            "total_targets": len(enriched),
            "targets": enriched
        }
    
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error getting disease targets: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))


# Protein Sequences Endpoint
@app.post("/api/v1/protein-sequences")
async def get_protein_sequences(targets: list):
    """
    Fetch protein sequences from UniProt for given target symbols.
    
    - **targets**: List of target objects with 'symbol' field
    """
    try:
        logger.info(f"Fetching sequences for {len(targets)} targets")
        
        sequences = protein_pipeline.get_protein_sequences(targets)
        
        return {
            "total_requested": len(targets),
            "total_found": len(sequences),
            "targets": sequences
        }
    
    except Exception as e:
        logger.error(f"Error getting protein sequences: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))


# Drug Library Endpoint
@app.get("/api/v1/drug-library")
async def get_drug_library():
    """
    Load FDA-approved drug library with SMILES strings.
    """
    try:
        logger.info("Loading drug library")
        
        drugs = drug_pipeline.load_drug_library(dataset_name=settings.TDC_DATASET)
        
        # Limit number of drugs for demo
        if len(drugs) > settings.MAX_DRUGS_FOR_DEMO:
            drugs = drugs[:settings.MAX_DRUGS_FOR_DEMO]
            logger.info(f"Limited drugs to {settings.MAX_DRUGS_FOR_DEMO} for demo")
        
        return {
            "total_drugs": len(drugs),
            "drugs": drugs
        }
    
    except Exception as e:
        logger.error(f"Error loading drug library: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))


# Virtual Screening Endpoint (Main Pipeline)
@app.post("/api/v1/screen", response_model=ScreeningResponse)
async def virtual_screening(request: ScreeningRequest):
    """
    Run complete virtual drug screening pipeline for a disease.
    
    This endpoint runs the entire A-to-Z pipeline:
    1. Identify disease targets (Open Targets)
    2. Fetch protein sequences (UniProt)
    3. Load drug library (TDC)
    4. Run AI predictions (DeepPurpose)
    5. Process and rank results
    """
    try:
        # Check cache
        cached = load_cached_screening(request)
        if cached is not None:
            logger.info(f"Returning cached screening for: {request.disease_name}")
            return ScreeningResponse(**cached)

        start_time = time.time()
        logger.info(f"Starting screening for disease: {request.disease_name}")
        
        # Stage 1: Get disease targets
        logger.info("Stage 1: Identifying disease targets...")
        targets = disease_pipeline.get_disease_targets(
            disease_name=request.disease_name,
            top_n=min(request.top_n_targets, settings.MAX_TARGETS)
        )
        
        if not targets:
            raise HTTPException(
                status_code=404,
                detail=f"No targets found for disease: {request.disease_name}"
            )
        
        # Stage 2: Get protein sequences
        logger.info(f"Stage 2: Fetching sequences for {len(targets)} targets...")
        targets_with_seqs = protein_pipeline.get_protein_sequences(targets)
        
        if not targets_with_seqs:
            raise HTTPException(
                status_code=400,
                detail="Could not fetch sequences for any targets"
            )
        
        # Stage 3: Load drug library
        logger.info(f"Stage 3: Loading drug library (max {settings.MAX_DRUGS_FOR_DEMO} drugs)...")
        drugs = drug_pipeline.load_drug_library(
            dataset_name=settings.TDC_DATASET,
            max_drugs=settings.MAX_DRUGS_FOR_DEMO
        )
        
        if not drugs:
            raise HTTPException(
                status_code=400,
                detail="Could not load any drugs from library"
            )
        
        # Limit drugs based on GPU availability (performance optimization)
        actual_max = settings.MAX_DRUGS_FOR_DEMO
        if len(drugs) > actual_max:
            logger.info(f"Limiting drugs to {actual_max} for performance")
            drugs = drugs[:actual_max]
        
        logger.info(f"Using {len(drugs)} drugs for screening")
        
        # Stage 4: Run AI screening
        n_pairs = len(drugs) * len(targets_with_seqs)
        logger.info(f"Stage 4: Running AI predictions...")
        logger.info(f"  Drug-target pairs: {n_pairs} ({len(drugs)} drugs × {len(targets_with_seqs)} targets)")
        
        if settings.HAS_GPU:
            logger.info(f"  GPU acceleration enabled (batch size: {settings.BATCH_SIZE})")
        else:
            logger.info(f"  CPU mode (batch size: {settings.BATCH_SIZE})")
        
        candidates, warnings = ai_pipeline.run_virtual_screening(drugs, targets_with_seqs)
        
        if not candidates:
            raise HTTPException(
                status_code=400,
                detail="AI screening failed to produce predictions"
            )
        
        # Stage 5: Process results
        logger.info("Stage 5: Processing and ranking results...")
        final_results = result_pipeline.process_final_results(
            candidates,
            known_drugs=request.known_drugs,
            min_score=request.min_score
        )
        
        top_k = final_results[:10]
        
        top_candidates = [
            DrugCandidate(
                drug_name=r["drug_name"],
                smiles=r.get("smiles", ""),
                target_symbol=r["target_symbol"],
                uniprot_id=r.get("uniprot_id", ""),
                binding_score=r["score"],
                rank=i + 1,
                status=r.get("status", "").replace("✅ ", "").replace("🆕 ", "")
            )
            for i, r in enumerate(top_k)
        ]
        
        elapsed_time = time.time() - start_time
        logger.info(f"✅ Screening complete in {elapsed_time:.2f} seconds")
        logger.info(f"   Total candidates: {len(final_results)}")
        logger.info(f"   Top candidates: {len(top_k)}")
        
        response = ScreeningResponse(
            disease_name=request.disease_name,
            total_targets_found=len(targets_with_seqs),
            total_drugs_screened=len(drugs),
            total_pairs_evaluated=len(candidates),
            top_candidates=top_candidates,
            warnings=warnings,
        )
        
        save_cached_screening(request, response.model_dump())
        return response
    
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error during screening: {str(e)}", exc_info=True)
        raise HTTPException(status_code=500, detail=f"Screening failed: {str(e)}")


# Filter Results Endpoint
@app.get("/api/v1/results/potential")
async def get_potential_discoveries():
    """
    Returns only potential drug discoveries (non-approved treatments).
    This is a helper endpoint - use with stored results from screening.
    """
    return {
        "message": "Run screening first, then call this endpoint with results parameter"
    }


# Error Handler
@app.exception_handler(HTTPException)
async def http_exception_handler(request: Request, exc: HTTPException):
    """Custom HTTP exception handler"""
    return JSONResponse(
        status_code=exc.status_code,
        content={
            "detail": exc.detail,
            "status_code": exc.status_code
        },
    )


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(
        "app.main:app",
        host=settings.HOST,
        port=settings.PORT,
        reload=settings.DEBUG
    )
