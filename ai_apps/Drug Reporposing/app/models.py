"""
Data Models for FastAPI Requests and Responses
"""
from pydantic import BaseModel, Field
from typing import List, Optional
from enum import Enum

class DiseaseSearchRequest(BaseModel):
    """Request model for disease target search"""
    disease_name: str = Field(..., description="Name of the disease to search for")
    top_n: int = Field(10, ge=1, le=100, description="Number of top targets to retrieve")

    class Config:
        example = {"disease_name": "Type 2 Diabetes", "top_n": 10}


class TargetInfo(BaseModel):
    """Protein target information"""
    symbol: str
    name: Optional[str] = None
    score: float
    sequence: Optional[str] = None
    uniprot_id: str = ""
    pdb_ids: List[str] = []

    class Config:
        example = {
            "symbol": "INSR",
            "name": "Insulin Receptor",
            "score": 0.85,
            "sequence": None,
            "uniprot_id": "P06213",
            "pdb_ids": ["2HR7", "3EKN", "4IBM"]
        }


class DrugInfo(BaseModel):
    """Drug information"""
    name: str
    smiles: str
    drug_id: Optional[str] = None

    class Config:
        example = {
            "name": "Drug_001",
            "smiles": "CC(=O)Oc1ccccc1C(=O)O",
            "drug_id": "1"
        }


class DrugCandidate(BaseModel):
    """Drug-target prediction candidate"""
    drug_name: str
    smiles: str = ""
    target_symbol: str
    uniprot_id: str = ""
    binding_score: float
    rank: int = 0
    status: Optional[str] = None

    class Config:
        example = {
            "drug_name": "Drug_001",
            "smiles": "CC(=O)Oc1ccccc1C(=O)O",
            "target_symbol": "INSR",
            "uniprot_id": "P06213",
            "binding_score": 0.85,
            "rank": 1,
            "status": "🆕 Potential Discovery"
        }


class ScreeningRequest(BaseModel):
    """Request model for virtual drug screening"""
    disease_name: str = Field(..., description="Disease name for target identification")
    min_score: float = Field(0.0, ge=0.0, le=1.0, description="Minimum binding affinity score")
    top_n_targets: int = Field(10, ge=1, le=50, description="Number of targets to use")
    known_drugs: List[str] = Field(
        default=["Metformin"],
        description="List of known drugs for the disease (for filtering)"
    )

    class Config:
        example = {
            "disease_name": "Type 2 Diabetes",
            "min_score": 0.5,
            "top_n_targets": 10,
            "known_drugs": ["Metformin", "Insulin"]
        }


class ScreeningResponse(BaseModel):
    """Response model for screening results"""
    disease_name: str
    total_targets_found: int
    total_drugs_screened: int
    total_pairs_evaluated: int
    top_candidates: List[DrugCandidate]
    warnings: List[str] = []

    class Config:
        example = {
            "disease_name": "Type 2 Diabetes",
            "total_targets_found": 10,
            "total_drugs_screened": 200,
            "total_pairs_evaluated": 2000,
            "top_candidates": [
                {
                    "drug_name": "Drug_001",
                    "smiles": "CC(=O)Oc1ccccc1C(=O)O",
                    "target_symbol": "INSR",
                    "uniprot_id": "P06213",
                    "binding_score": 0.85,
                    "rank": 1,
                    "status": "🆕 Potential Discovery"
                }
            ],
            "warnings": []
        }


class HealthCheckResponse(BaseModel):
    """Health check response"""
    status: str
    version: str
    service: str

    class Config:
        example = {
            "status": "healthy",
            "version": "1.0.0",
            "service": "Drug Repurposing API"
        }


class EnrichedTargetResponse(BaseModel):
    """Response model for enriched targets endpoint"""
    disease: str
    disease_id: str
    total_targets: int
    targets: List[TargetInfo]

    class Config:
        example = {
            "disease": "Type 2 Diabetes",
            "disease_id": "EFO_0001360",
            "total_targets": 10,
            "targets": [
                {
                    "symbol": "INSR",
                    "name": "Insulin Receptor",
                    "score": 0.85,
                    "sequence": None,
                    "uniprot_id": "P06213",
                    "pdb_ids": ["2HR7", "3EKN", "4IBM"]
                }
            ]
        }


class ErrorResponse(BaseModel):
    """Error response model"""
    detail: str
    error_code: Optional[str] = None

    class Config:
        example = {
            "detail": "Disease not found",
            "error_code": "DISEASE_NOT_FOUND"
        }
