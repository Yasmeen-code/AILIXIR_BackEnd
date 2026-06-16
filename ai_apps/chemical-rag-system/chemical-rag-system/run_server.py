"""
FastAPI Server Runner Script
Automatically detects Docker environment and adjusts host/port accordingly
"""
import sys
import os
import warnings

# Suppress all warnings
warnings.filterwarnings("ignore")

# Suppress RDKit deprecation warnings at stderr level
import logging
logging.getLogger("rdkit").setLevel(logging.ERROR)

# Add current directory to path
sys.path.insert(0, os.path.dirname(__file__))

import uvicorn

if __name__ == "__main__":
    # Detect if running in Docker
    running_in_docker = os.path.exists('/.dockerenv')
    
    if running_in_docker:
        host = "0.0.0.0"
        port = int(os.getenv("API_PORT", 7860))
        is_reload = False
    else:
        host = "127.0.0.1"
        port = int(os.getenv("API_PORT", 8000))
        is_reload = False
    
    print(f"[STARTUP] Starting FastAPI server on {host}:{port}")
    if running_in_docker:
        print("   Running in Docker mode (reload disabled)")
    else:
        print("   Running in local development mode (reload disabled for index stability)")
    
    uvicorn.run(
        "app.main:app",
        host=host,
        port=port,
        reload=is_reload,
        log_level="info"
    )
