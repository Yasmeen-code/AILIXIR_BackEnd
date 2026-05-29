"""
Application Configuration
"""
import os
from typing import Optional
import logging

logger = logging.getLogger(__name__)

def _detect_gpu():
    """Detect if GPU/CUDA is available"""
    try:
        import torch
        return torch.cuda.is_available()
    except (ImportError, Exception):
        return False

def _get_max_drugs(use_gpu: bool) -> int:
    """Get max drugs based on GPU availability"""
    if use_gpu:
        return 600  # GPU can handle more drugs
    else:
        return 200  # CPU limited for performance

class Settings:
    """Application settings"""
    
    # Application Info
    APP_NAME: str = "Drug Repurposing AI System"
    APP_VERSION: str = "1.0.0"
    APP_DESCRIPTION: str = "AI-powered drug repurposing system using Deep Learning and Open Targets"
    
    # API Configuration
    API_TITLE: str = "Drug Repurposing API"
    API_VERSION: str = "v1"
    DEBUG: bool = os.getenv("DEBUG", "False").lower() == "true"
    
    # Server Configuration
    HOST: str = os.getenv("HOST", "0.0.0.0")
    PORT: int = int(os.getenv("PORT", "8000"))
    
    # API Keys and URLs
    OPENTARGETS_API_URL: str = "https://api.platform.opentargets.org/api/v4/graphql"
    UNIPROT_API_URL: str = "https://rest.uniprot.org/uniprotkb/search"
    
    # GPU Detection
    HAS_GPU: bool = _detect_gpu()
    DEVICE: str = "cuda" if HAS_GPU else "cpu"
    
    # Model Configuration
    DEEP_PURPOSE_MODEL: str = os.getenv("DEEP_PURPOSE_MODEL", "MPNN_CNN_BindingDB")
    USE_MOCK_MODEL: bool = os.getenv("USE_MOCK_MODEL", "False").lower() == "true"  # PRODUCTION: Always False
    USE_MOCK_DRUGS: bool = os.getenv("USE_MOCK_DRUGS", "False").lower() == "true"  # PRODUCTION: Always False
    
    # Screening Parameters
    DEFAULT_TOP_TARGETS: int = 10
    DEFAULT_TOP_RESULTS: int = 15
    DEFAULT_MIN_SCORE: float = 0.0
    MAX_TARGETS: int = 50
    MAX_DRUGS_FOR_DEMO: int = _get_max_drugs(HAS_GPU)
    BATCH_SIZE: int = 32 if HAS_GPU else 8  # Batch size for predictions
    
    # TDC Configuration
    TDC_DATASET: str = os.getenv("TDC_DATASET", "Half_Life_Obach")
    TDC_TIMEOUT: int = 300  # Timeout for TDC downloads in seconds
    
    # Logging Configuration
    LOG_LEVEL: str = os.getenv("LOG_LEVEL", "INFO")
    LOG_FORMAT: str = "%(asctime)s - %(name)s - %(levelname)s - %(message)s"
    
    # CORS Configuration
    CORS_ORIGINS: list = ["*"]
    CORS_CREDENTIALS: bool = True
    CORS_METHODS: list = ["*"]
    CORS_HEADERS: list = ["*"]
    
    # Timeout Settings
    API_TIMEOUT: int = 60
    REQUEST_TIMEOUT: int = 300
    
    # Production Mode
    PRODUCTION_MODE: bool = os.getenv("PRODUCTION_MODE", "False").lower() == "true"

    @classmethod
    def get_settings(cls) -> 'Settings':
        """Get application settings instance"""
        return cls()


settings = Settings.get_settings()

# Log GPU status on startup
if settings.HAS_GPU:
    logger.info(f"✅ GPU/CUDA detected. Max drugs: {settings.MAX_DRUGS_FOR_DEMO}")
else:
    logger.info(f"⚠️ No GPU detected. CPU mode. Max drugs: {settings.MAX_DRUGS_FOR_DEMO}")
