"""
Stage 4: AI Prediction Engine
Performs AI-based matching between drugs and targets using DeepPurpose MPNN_CNN model.
"""
import logging
from typing import List, Dict, Any, Tuple
import numpy as np
from app.config import settings

logger = logging.getLogger(__name__)

class AIScreeningPipeline:
    def __init__(self, use_mock: bool = False):
        """
        Initialize AI screening pipeline.
        
        Args:
            use_mock: If True, use mock predictions for testing
        """
        self.use_mock = use_mock
        self.model = None
        self.model_name = settings.DEEP_PURPOSE_MODEL
        self._is_initialized = False

    def load_model(self, model_name: str = 'MPNN_CNN_BindingDB') -> bool:
        """
        Load pre-trained DeepPurpose model (REQUIRED FOR PRODUCTION).
        
        Args:
            model_name: Name of the pre-trained model to load
            
        Returns:
            True if model loaded successfully
            
        Raises:
            ImportError: If DeepPurpose not installed
            RuntimeError: If model loading fails
        """
        if self._is_initialized and self.model is not None:
            logger.info("✅ Model already loaded")
            return True
        
        logger.info(f"Loading Pre-trained DeepPurpose Model ({model_name})...")
        logger.info(f"Device: {settings.DEVICE}")
        
        try:
            from DeepPurpose import DTI as models
            from DeepPurpose.utils import download_pretrained_model, name2filename
            import os
            import shutil

            folder_name = name2filename[model_name.lower()]
            model_dir = os.path.join("./save_folder", "pretrained_models", folder_name)
            config_path = os.path.join(model_dir, "config.pkl")

            if not os.path.isfile(config_path):
                if os.path.isdir(model_dir):
                    logger.warning(
                        f"Incomplete model at {model_dir} (missing config.pkl), re-downloading..."
                    )
                    shutil.rmtree(model_dir)
                download_pretrained_model(model_name, save_dir="./save_folder")
                if not os.path.isfile(config_path):
                    raise RuntimeError(
                        f"DeepPurpose model files missing after download: {config_path}"
                    )

            # Load the model
            self.model = models.model_pretrained(model=model_name)
            
            # Move to GPU if available
            if settings.HAS_GPU:
                try:
                    self.model = self.model.to(settings.DEVICE)
                    logger.info("✅ Model moved to GPU for faster predictions")
                except Exception as e:
                    logger.warning(f"Could not move model to GPU: {str(e)}")
            
            self.model_name = model_name
            self._is_initialized = True
            logger.info(f"✅ DeepPurpose model loaded successfully ({model_name})")
            return True
            
        except ImportError as e:
            logger.error(f"❌ DeepPurpose not installed: {str(e)}")
            raise ImportError(
                "DeepPurpose is required for production. Install with:\n"
                "  pip install -r requirements.txt"
            ) from e
            
        except Exception as e:
            logger.error(f"❌ Error loading DeepPurpose model: {str(e)}")
            raise RuntimeError(f"Failed to load AI model: {str(e)}") from e

    def run_virtual_screening(
        self,
        drug_lib: List[Dict],
        target_lib: List[Dict]
    ) -> Tuple[List[Dict], List[str]]:
        """
        Performs AI-based matching between all drugs and targets.
        Uses real DeepPurpose predictions or mock predictions based on use_mock flag.
        
        Args:
            drug_lib: List of drugs with 'name' and 'smiles'
            target_lib: List of targets with 'symbol' and 'sequence'
        
        Returns:
            Tuple of (predictions, warnings) where each prediction dict contains
            drug_name, target_symbol, smiles, uniprot_id, and score
        """
        if not drug_lib or not target_lib:
            raise ValueError("Drug library and target library cannot be empty")

        n_pairs = len(drug_lib) * len(target_lib)
        warnings = []
        
        if self.use_mock:
            logger.info(f"Using mock predictions for {n_pairs} drug-target pairs")
            pair_info = []
            for drug in drug_lib:
                for target in target_lib:
                    pair_info.append({
                        'drug_name': drug['name'],
                        'smiles': drug.get('smiles', ''),
                        'target_symbol': target['symbol'],
                        'uniprot_id': target.get('uniprot_id', ''),
                    })
            scores = self._generate_mock_predictions(n_pairs)
            method = "MOCK"
            warnings.append("Mock predictions enabled (not real AI scores)")
        else:
            if not self.model or not self._is_initialized:
                raise RuntimeError(
                    "AI model not loaded. Call load_model() first."
                )
            
            logger.info(f"Starting AI Virtual Screening (REAL PREDICTIONS)...")
            logger.info(f"   Drugs: {len(drug_lib)}, Targets: {len(target_lib)}")
            logger.info(f"   Total pairs: {n_pairs}")
            
            X_drugs = []
            X_targets = []
            pair_info = []
            for drug in drug_lib:
                for target in target_lib:
                    X_drugs.append(drug['smiles'])
                    X_targets.append(target['sequence'])
                    pair_info.append({
                        'drug_name': drug['name'],
                        'smiles': drug.get('smiles', ''),
                        'target_symbol': target['symbol'],
                        'uniprot_id': target.get('uniprot_id', ''),
                    })
            
            scores = self._predict_with_model(X_drugs, X_targets)
            method = "REAL"

        results = []
        for i, score in enumerate(scores):
            results.append({
                "drug_name": pair_info[i]['drug_name'],
                "smiles": pair_info[i]['smiles'],
                "target_symbol": pair_info[i]['target_symbol'],
                "uniprot_id": pair_info[i]['uniprot_id'],
                "score": round(float(score), 4)
            })

        logger.info(f"AI Screening completed ({method}): {len(results)} drug-target pairs scored")
        return results, warnings

    def _predict_with_model(self, X_drugs: List[str], X_targets: List[str]) -> np.ndarray:
        """
        Run REAL predictions with DeepPurpose MPNN_CNN model.
        
        Uses batching for efficiency and GPU acceleration if available.
        
        Raises:
            Exception: If prediction fails (no fallback to mock)
        """
        from DeepPurpose.utils import data_process
        
        try:
            logger.info(f"🧠 Encoding data (MPNN drug encoding, CNN protein encoding)...")
            logger.debug(f"   Drugs to encode: {len(X_drugs)}")
            logger.debug(f"   Targets to encode: {len(X_targets)}")
            
            # Process data with proper encodings for MPNN_CNN model
            X_pred = data_process(
                X_drug=X_drugs,
                X_target=X_targets,
                y=[0] * len(X_drugs),  # Dummy labels
                drug_encoding='MPNN',
                target_encoding='CNN',
                split_method='no_split'
            )
            
            logger.debug(f"   Data processed successfully")
            logger.debug(f"   X_pred type: {type(X_pred)}")
            if hasattr(X_pred, '__len__'):
                logger.debug(f"   X_pred length: {len(X_pred)}")
            if isinstance(X_pred, (tuple, list)):
                for i, item in enumerate(X_pred):
                    if hasattr(item, 'shape'):
                        logger.debug(f"     Item {i}: shape {item.shape}")
                    elif hasattr(item, '__len__'):
                        logger.debug(f"     Item {i}: length {len(item)}")

            logger.info(f"🧠 Running binding affinity predictions ({len(X_drugs)} pairs)...")
            
            # Run predictions with batching for better performance
            predictions = self._batch_predict(X_pred)
            
            # Normalize scores to 0-1 range
            predictions = np.clip(predictions, 0, 1)
            
            logger.info(f"✅ Prediction complete. Score range: [{predictions.min():.4f}, {predictions.max():.4f}]")
            return predictions
            
        except Exception as e:
            logger.error(f"Error in _predict_with_model: {str(e)}", exc_info=True)
            raise

    def _batch_predict(self, X_pred: Any) -> np.ndarray:
        """
        Perform batch predictions using DeepPurpose.
        Note: Use full prediction without manual batching due to DeepPurpose DataLoader issues.
        
        Args:
            X_pred: Preprocessed data from data_process
            
        Returns:
            Array of predictions
        """
        try:
            # DeepPurpose handles batching internally, don't add extra batching on top
            logger.info(f"Running predictions on full dataset...")
            predictions = self.model.predict(X_pred)
            
            return np.array(predictions)
            
        except Exception as e:
            logger.error(f"Error during prediction: {str(e)}", exc_info=True)
            raise

    def _generate_mock_predictions(self, n_pairs: int) -> np.ndarray:
        """
        Generate realistic mock predictions for testing.
        Weights towards more realistic binding affinity distribution.
        """
        logger.info(f"🧠 Generating mock predictions ({n_pairs} pairs)...")
        
        import random
        import numpy as np
        
        # Create more realistic distribution (favor moderate to good binding)
        predictions = []
        for _ in range(n_pairs):
            # Biased towards 0.4-0.8 range (more realistic for drug screening)
            raw_score = random.gauss(0.55, 0.2)  # Mean 0.55, StdDev 0.2
            score = np.clip(raw_score, 0.0, 1.0)  # Clip to 0-1
            predictions.append(round(float(score), 4))
        
        return np.array(predictions)

    def get_model_info(self) -> Dict:
        """Get information about loaded model"""
        return {
            "model_name": self.model_name,
            "device": settings.DEVICE,
            "gpu_available": settings.HAS_GPU,
            "is_loaded": self._is_initialized and not self.use_mock,
            "using_mock": self.use_mock,
            "batch_size": settings.BATCH_SIZE
        }

