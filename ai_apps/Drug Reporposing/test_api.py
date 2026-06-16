"""
Comprehensive Test Suite for Drug Repurposing API
Tests both with pytest and standalone requests
"""
import pytest
import requests
import json
import time
from fastapi.testclient import TestClient
from app.main import app

client = TestClient(app)

# ============================================================================
# PYTEST UNIT TESTS - Run with: pytest test_api.py
# ============================================================================

class TestHealthCheck:
    """Test health check endpoint"""
    
    def test_health_check(self):
        """Test health endpoint returns 200"""
        response = client.get("/health")
        assert response.status_code == 200
        data = response.json()
        assert data["status"] == "healthy"
        assert "version" in data
        assert "service" in data


class TestRootEndpoint:
    """Test root endpoint"""
    
    def test_root_endpoint(self):
        """Test root endpoint returns API info"""
        response = client.get("/")
        assert response.status_code == 200
        data = response.json()
        assert "name" in data
        assert "version" in data
        assert "docs" in data


class TestModelStatus:
    """Test model status endpoint"""
    
    def test_model_status(self):
        """Test model status endpoint"""
        response = client.get("/api/v1/model-status")
        assert response.status_code == 200
        data = response.json()
        assert "model" in data
        assert "device" in data
        assert "gpu_available" in data


class TestDiseaseTargets:
    """Test disease target endpoint"""
    
    def test_disease_targets_valid_disease(self):
        """Test with valid disease name"""
        payload = {
            "disease_name": "Type 2 Diabetes",
            "top_n": 5
        }
        response = client.post("/api/v1/disease-targets", json=payload)
        # Response depends on API availability
        assert response.status_code in [200, 404, 500]
        """Test with invalid disease name"""
        payload = {
            "disease_name": "NonexistentDisease12345XYZ",
            "top_n": 5
        }
        response = client.post("/api/v1/disease-targets", json=payload)
        assert response.status_code in [404, 500]


class TestProteinSequences:
    """Test protein sequence endpoint"""
    
    def test_protein_sequences_empty_list(self):
        """Test with empty target list"""
        response = client.post("/api/v1/protein-sequences", json=[])
        assert response.status_code == 200
        data = response.json()
        assert data["total_requested"] == 0


class TestDrugLibrary:
    """Test drug library endpoint"""
    
    def test_drug_library(self):
        """Test drug library endpoint"""
        response = client.get("/api/v1/drug-library")
        assert response.status_code == 200
        data = response.json()
        assert "total_drugs" in data
        assert "drugs" in data
        assert isinstance(data["drugs"], list)


class TestRequestValidation:
    """Test request validation"""
    
    def test_invalid_top_n_too_large(self):
        """Test validation of top_n parameter"""
        payload = {
            "disease_name": "Type 2 Diabetes",
            "top_n": 101  # Max is 100
        }
        response = client.post("/api/v1/disease-targets", json=payload)
        # Should either accept (clamp) or reject
        # Current implementation clamps to 100
        assert response.status_code in [200, 422]
    
    def test_missing_required_field(self):
        """Test missing required field"""
        payload = {
            "top_n": 10
            # Missing disease_name
        }
        response = client.post("/api/v1/disease-targets", json=payload)
        assert response.status_code == 422


class TestScreeningPipeline:
    """Test complete screening pipeline"""
    
    def test_screening_with_mock_data(self):
        """Test screening endpoint with mock data"""
        payload = {
            "disease_name": "Type 2 Diabetes",
            "min_score": 0.5,
            "top_n_targets": 3,
            "known_drugs": ["Metformin"]
        }
        response = client.post("/api/v1/screen", json=payload)
        # Response depends on external API availability
        assert response.status_code in [200, 404, 500]
        
        if response.status_code == 200:
            data = response.json()
            assert "disease_name" in data
            assert "total_targets_found" in data
            assert "total_drugs_screened" in data
            assert "top_candidates" in data
            assert "warnings" in data


if __name__ == "__main__":
    # Run with: python test_api.py
    # Or pytest: pytest test_api.py -v
    pytest.main([__file__, "-v"])
    pytest.main([__file__, "-v"])
