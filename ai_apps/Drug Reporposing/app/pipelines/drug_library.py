"""
Stage 3: Load Drug Library
Loads FDA-approved drugs with their SMILES strings using TDC (Therapeutic Data Commons).
"""
import logging
from typing import List, Dict, Optional
import pandas as pd
import os

logger = logging.getLogger(__name__)

class DrugLibraryPipeline:
    def __init__(self, use_mock: bool = False):
        """
        Initialize drug library pipeline.
        
        Args:
            use_mock: If True, use mock data for testing without TDC dependency
        """
        self.use_mock = use_mock
        self._cache = {}

    def load_drug_library(self, dataset_name: str = 'Half_Life_Obach', max_drugs: int = 600) -> List[Dict]:
        """
        Loads FDA-approved drugs with their SMILES strings using TDC.
        Falls back to local TDC implementation if official TDC unavailable.
        
        Args:
            dataset_name: Name of the TDC dataset to load (default: 'Half_Life_Obach')
            max_drugs: Maximum number of drugs to load (for performance)
        
        Returns:
            List of drugs with 'name', 'smiles', and 'drug_id' keys
        """
        logger.info(f"💊 Loading FDA-Approved Drug Library...")
        
        # Check cache first
        cache_key = f"{dataset_name}_{max_drugs}"
        if cache_key in self._cache:
            logger.info(f"📦 Using cached drug library ({len(self._cache[cache_key])} drugs)")
            return self._cache[cache_key]
        
        # Try official TDC first
        try:
            from tdc.single_pred import ADME
            logger.info(f"Connecting to Official TDC and downloading {dataset_name} dataset...")
            data = ADME(name=dataset_name).get_data()
            
        except ImportError:
            logger.warning(f"⚠️  Official TDC not available - using local TDC fallback")
            # Use local implementation
            from app.local_tdc import ADME
            data = ADME(name=dataset_name).get_data()
            
        except Exception as e:
            logger.warning(f"⚠️  TDC download failed ({str(e)[:50]}...) - using local TDC fallback")
            from app.local_tdc import ADME
            data = ADME(name=dataset_name).get_data()
        
        if data is None or len(data) == 0:
            raise RuntimeError(f"Failed to load drug dataset {dataset_name}")
        
        library = []
        valid_count = 0
        invalid_count = 0
        
        for idx, row in data.iterrows():
            # Extract drug information
            smiles = row.get('Drug', '')
            drug_id = row.get('Drug_ID', str(idx))
            
            # Validate SMILES
            if not smiles or not isinstance(smiles, str) or len(smiles.strip()) == 0:
                invalid_count += 1
                continue
            
            drug = {
                "name": f"Drug_{drug_id}",
                "smiles": smiles.strip(),
                "drug_id": str(drug_id),
                "source": "TDC"
            }
            
            library.append(drug)
            valid_count += 1
            
            # Limit drugs for performance
            if len(library) >= max_drugs:
                logger.info(f"Reached drug limit of {max_drugs}")
                break
        
        logger.info(f"✅ Loaded {valid_count} valid drugs, {invalid_count} invalid")
        
        # Cache the results
        self._cache[cache_key] = library
        
        if len(library) == 0:
            raise RuntimeError(f"No valid drugs found in dataset {dataset_name}")
        
        return library


