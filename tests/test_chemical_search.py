"""Tests for Chemical Search Endpoints."""

import pytest
import time


class TestRetrievalSearch:
    """POST /chemical-search"""

    def test_retrieval_search_success(self, authenticated_client):
        """Test retrieval-only chemical search."""
        payload = {
            "smiles": "CC(=O)Oc1ccccc1C(=O)O",
            "top_k": 5
        }
        response = authenticated_client.post("/chemical-search", json=payload)
        assert response.status_code == 202
        data = response.json()
        assert data["success"] is True
        assert "job_id" in data
        assert data["status"] == "pending"
        assert data["type"] == "retrieval_only"
        return data["job_id"]

    def test_retrieval_search_invalid_smiles(self, authenticated_client):
        """Test search with invalid SMILES."""
        payload = {
            "smiles": "INVALID",
            "top_k": 5
        }
        response = authenticated_client.post("/chemical-search", json=payload)
        assert response.status_code in [202, 400, 422]

    def test_retrieval_search_missing_smiles(self, authenticated_client):
        """Test search without SMILES."""
        payload = {"top_k": 5}
        response = authenticated_client.post("/chemical-search", json=payload)
        assert response.status_code == 422

    def test_retrieval_search_unauthorized(self, client):
        """Test search without auth."""
        payload = {"smiles": "CC(=O)Oc1ccccc1C(=O)O"}
        response = client.post("/chemical-search", json=payload)
        assert response.status_code == 401


class TestFullRAGSearch:
    """POST /chemical-search/full-rag"""

    def test_full_rag_search_success(self, authenticated_client):
        """Test full RAG search with explanations."""
        payload = {
            "smiles": "CC(=O)Oc1ccccc1C(=O)O",
            "top_k": 3
        }
        response = authenticated_client.post("/chemical-search/full-rag", json=payload)
        assert response.status_code == 202
        data = response.json()
        assert data["success"] is True
        assert "job_id" in data
        assert data["type"] == "full_rag"
        return data["job_id"]

    def test_full_rag_search_unauthorized(self, client):
        """Test RAG search without auth."""
        payload = {"smiles": "CC(=O)Oc1ccccc1C(=O)O"}
        response = client.post("/chemical-search/full-rag", json=payload)
        assert response.status_code == 401


class TestGetSearchResults:
    """GET /chemical-search/{job_id}/status"""

    def test_get_search_results(self, authenticated_client):
        """Test retrieving search results."""
        payload = {"smiles": "CC(=O)Oc1ccccc1C(=O)O", "top_k": 3}
        submit_response = authenticated_client.post("/chemical-search", json=payload)
        job_id = submit_response.json()["job_id"]

        max_attempts = 15
        for _ in range(max_attempts):
            response = authenticated_client.get(f"/chemical-search/{job_id}/status")
            data = response.json()
            if data["status"] == "completed":
                break
            time.sleep(2)

        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        if data["status"] == "completed":
            assert "compounds" in data
            assert "query" in data
            assert "metadata" in data

    def test_get_invalid_job(self, authenticated_client):
        """Test getting results for invalid job."""
        response = authenticated_client.get("/chemical-search/invalid-job/status")
        assert response.status_code in [404, 400]

    def test_get_results_unauthorized(self, client):
        """Test getting results without auth."""
        response = client.get("/chemical-search/some-job/status")
        assert response.status_code == 401


class TestGetCompoundImages:
    """GET /chemical-search/{job_id}/images"""

    def test_get_images(self, authenticated_client):
        """Test getting compound images."""
        payload = {"smiles": "CC(=O)Oc1ccccc1C(=O)O", "top_k": 3}
        submit_response = authenticated_client.post("/chemical-search", json=payload)
        job_id = submit_response.json()["job_id"]

        for _ in range(15):
            status_response = authenticated_client.get(f"/chemical-search/{job_id}/status")
            if status_response.json()["status"] == "completed":
                break
            time.sleep(2)

        response = authenticated_client.get(f"/chemical-search/{job_id}/images")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "image_urls" in data
        assert "total_images" in data

    def test_get_images_unauthorized(self, client):
        """Test getting images without auth."""
        response = client.get("/chemical-search/some-job/images")
        assert response.status_code == 401
