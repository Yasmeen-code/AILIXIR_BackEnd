"""Tests for AI Agent Endpoints (Chemistry AI)."""

import pytest
import time
import io


class TestCreateThread:
    """POST /api/chemistry/thread"""

    def test_create_thread_success(self, authenticated_client):
        """Test creating a new conversation thread."""
        response = authenticated_client.post("/chemistry/thread")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "thread_id" in data["data"]
        assert "id" in data["data"]
        assert "created_at" in data["data"]

    def test_create_thread_unauthorized(self, client):
        """Test creating thread without auth."""
        response = client.post("/chemistry/thread")
        assert response.status_code == 401


class TestListThreads:
    """GET /api/chemistry/threads"""

    def test_list_threads_success(self, authenticated_client):
        """Test listing user threads."""
        response = authenticated_client.get("/chemistry/threads")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert isinstance(data["data"], list)
        if len(data["data"]) > 0:
            thread = data["data"][0]
            assert "thread_id" in thread
            assert "title" in thread
            assert "last_used_at" in thread

    def test_list_threads_unauthorized(self, client):
        """Test listing threads without auth."""
        response = client.get("/chemistry/threads")
        assert response.status_code == 401


class TestSendChatMessage:
    """POST /api/chemistry/chat"""

    def test_chat_with_thread(self, authenticated_client, thread_id):
        """Test sending chat message with thread_id."""
        payload = {
            "message": "Is CC(=O)Oc1ccccc1C(=O)O a good drug candidate?",
            "thread_id": thread_id
        }
        response = authenticated_client.post("/chemistry/chat", json=payload)
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "reply" in data["data"]
        assert data["data"]["thread_id"] == thread_id
        assert "processing_time_ms" in data["data"]

    def test_chat_without_thread(self, authenticated_client):
        """Test sending chat message without thread_id (auto-create)."""
        payload = {
            "message": "Analyze aspirin CC(=O)Oc1ccccc1C(=O)O"
        }
        response = authenticated_client.post("/chemistry/chat", json=payload)
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "reply" in data["data"]
        assert "thread_id" in data["data"]

    def test_chat_invalid_thread(self, authenticated_client):
        """Test chat with invalid thread_id."""
        payload = {
            "message": "Hello",
            "thread_id": "invalid-thread-id-123"
        }
        response = authenticated_client.post("/chemistry/chat", json=payload)
        assert response.status_code in [404, 400]

    def test_chat_unauthorized(self, client):
        """Test chat without auth."""
        payload = {"message": "Hello"}
        response = client.post("/chemistry/chat", json=payload)
        assert response.status_code == 401


class TestAnalyzeSmiles:
    """POST /api/chemistry/analyze/smiles"""

    def test_analyze_valid_smiles(self, authenticated_client, thread_id):
        """Test analyzing valid SMILES string."""
        payload = {
            "smiles": "CC(=O)Oc1ccccc1C(=O)O",
            "thread_id": thread_id
        }
        response = authenticated_client.post("/chemistry/analyze/smiles", json=payload)
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "reply" in data["data"]
        assert "processing_time_ms" in data["data"]

    def test_analyze_invalid_smiles(self, authenticated_client, thread_id):
        """Test analyzing invalid SMILES string."""
        payload = {
            "smiles": "INVALID_SMILES_STRING",
            "thread_id": thread_id
        }
        response = authenticated_client.post("/chemistry/analyze/smiles", json=payload)
        assert response.status_code in [200, 400, 422]

    def test_analyze_missing_smiles(self, authenticated_client):
        """Test analysis without SMILES field."""
        payload = {}
        response = authenticated_client.post("/chemistry/analyze/smiles", json=payload)
        assert response.status_code == 422

    def test_analyze_unauthorized(self, client):
        """Test analysis without auth."""
        payload = {"smiles": "CC(=O)Oc1ccccc1C(=O)O"}
        response = client.post("/chemistry/analyze/smiles", json=payload)
        assert response.status_code == 401


class TestCompareMolecules:
    """POST /api/chemistry/analyze/compare"""

    def test_compare_two_molecules(self, authenticated_client, thread_id):
        """Test comparing two valid molecules."""
        payload = {
            "smiles": [
                "CC(=O)Oc1ccccc1C(=O)O",
                "CC(C)Cc1ccc(cc1)C(C)C(=O)O"
            ],
            "thread_id": thread_id
        }
        response = authenticated_client.post("/chemistry/analyze/compare", json=payload)
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "reply" in data["data"]

    def test_compare_single_molecule(self, authenticated_client, thread_id):
        """Test compare with single molecule (should require 2+)."""
        payload = {
            "smiles": ["CC(=O)Oc1ccccc1C(=O)O"],
            "thread_id": thread_id
        }
        response = authenticated_client.post("/chemistry/analyze/compare", json=payload)
        assert response.status_code in [200, 422]

    def test_compare_three_molecules(self, authenticated_client, thread_id):
        """Test comparing three molecules."""
        payload = {
            "smiles": [
                "CC(=O)Oc1ccccc1C(=O)O",
                "CC(C)Cc1ccc(cc1)C(C)C(=O)O",
                "Cn1cnc2c1c(=O)n(c(=O)n2C)C"
            ],
            "thread_id": thread_id
        }
        response = authenticated_client.post("/chemistry/analyze/compare", json=payload)
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True


class TestAnalyzeDocking:
    """POST /api/chemistry/analyze/docking"""

    def test_analyze_docking_results(self, authenticated_client, thread_id):
        """Test analyzing docking results."""
        payload = {
            "docking_data": "CC(=O)Oc1ccccc1C(=O)O | -7.2 | 1.1 | H-bond to Ser195\nCC(C)Cc1ccc(cc1)C(C)C(=O)O | -8.9 | 0.8 | deep pocket binding",
            "thread_id": thread_id
        }
        response = authenticated_client.post("/chemistry/analyze/docking", json=payload)
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "reply" in data["data"]

    def test_analyze_docking_empty_data(self, authenticated_client, thread_id):
        """Test with empty docking data."""
        payload = {
            "docking_data": "",
            "thread_id": thread_id
        }
        response = authenticated_client.post("/chemistry/analyze/docking", json=payload)
        assert response.status_code in [200, 422]


class TestUploadCSV:
    """POST /api/chemistry/csv/upload"""

    def test_upload_csv_full_analysis(self, authenticated_client):
        """Test uploading CSV for full analysis."""
        csv_content = """name,smiles
Aspirin,CC(=O)Oc1ccccc1C(=O)O
Ibuprofen,CC(C)Cc1ccc(cc1)C(C)C(=O)O
Caffeine,Cn1cnc2c1c(=O)n(c(=O)n2C)C"""

        files = {
            "file": ("molecules.csv", io.BytesIO(csv_content.encode()), "text/csv")
        }
        data = {"analysis_type": "full"}

        response = authenticated_client.post(
            "/chemistry/csv/upload",
            data=data,
            files=files
        )
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "job_id" in data["data"]
        assert data["data"]["status"] == "queued"
        return data["data"]["job_id"]

    def test_upload_csv_quick_analysis(self, authenticated_client):
        """Test uploading CSV for quick analysis."""
        csv_content = "name,smiles\nTest,CC(=O)Oc1ccccc1C(=O)O"

        files = {
            "file": ("test.csv", io.BytesIO(csv_content.encode()), "text/csv")
        }
        data = {"analysis_type": "quick"}

        response = authenticated_client.post(
            "/chemistry/csv/upload",
            data=data,
            files=files
        )
        assert response.status_code == 200

    def test_upload_csv_no_file(self, authenticated_client):
        """Test upload without file."""
        response = authenticated_client.post("/chemistry/csv/upload", data={"analysis_type": "full"})
        assert response.status_code == 422

    def test_upload_csv_unauthorized(self, client):
        """Test upload without auth."""
        response = client.post("/chemistry/csv/upload")
        assert response.status_code == 401


class TestCheckCSVStatus:
    """GET /api/chemistry/csv/status/{job_id}"""

    def test_check_csv_status(self, authenticated_client):
        """Test checking CSV job status."""
        csv_content = "name,smiles\nTest,CC(=O)Oc1ccccc1C(=O)O"
        files = {"file": ("test.csv", io.BytesIO(csv_content.encode()), "text/csv")}
        upload_response = authenticated_client.post("/chemistry/csv/upload", data={"analysis_type": "quick"}, files=files)
        job_id = upload_response.json()["data"]["job_id"]

        response = authenticated_client.get(f"/chemistry/csv/status/{job_id}")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "status" in data["data"]
        assert data["data"]["status"] in ["queued", "running", "done", "failed"]

    def test_check_invalid_job(self, authenticated_client):
        """Test checking invalid job ID."""
        response = authenticated_client.get("/chemistry/csv/status/invalid-job-id")
        assert response.status_code in [404, 400]


class TestDownloadCSVResults:
    """GET /api/chemistry/csv/results/{job_id}"""

    def test_download_csv_results(self, authenticated_client):
        """Test downloading CSV results."""
        csv_content = "name,smiles\nTest,CC(=O)Oc1ccccc1C(=O)O"
        files = {"file": ("test.csv", io.BytesIO(csv_content.encode()), "text/csv")}
        upload_response = authenticated_client.post("/chemistry/csv/upload", data={"analysis_type": "quick"}, files=files)
        job_id = upload_response.json()["data"]["job_id"]

        # Poll until done
        max_attempts = 10
        for _ in range(max_attempts):
            status_response = authenticated_client.get(f"/chemistry/csv/status/{job_id}")
            if status_response.json()["data"]["status"] == "done":
                break
            time.sleep(2)

        response = authenticated_client.get(f"/chemistry/csv/results/{job_id}")
        assert response.status_code == 200
        assert "text/csv" in response.headers.get("content-type", "")

    def test_download_unauthorized(self, client):
        """Test download without auth."""
        response = client.get("/chemistry/csv/results/some-job-id")
        assert response.status_code == 401


class TestListCSVJobs:
    """GET /api/chemistry/csv/jobs"""

    def test_list_csv_jobs(self, authenticated_client):
        """Test listing user CSV jobs."""
        response = authenticated_client.get("/chemistry/csv/jobs")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert isinstance(data["data"], list)

    def test_list_csv_jobs_unauthorized(self, client):
        """Test listing jobs without auth."""
        response = client.get("/chemistry/csv/jobs")
        assert response.status_code == 401


class TestDeleteCSVJob:
    """DELETE /api/chemistry/csv/jobs/{job_id}"""

    def test_delete_csv_job(self, authenticated_client):
        """Test deleting a CSV job."""
        csv_content = "name,smiles\nTest,CC(=O)Oc1ccccc1C(=O)O"
        files = {"file": ("test.csv", io.BytesIO(csv_content.encode()), "text/csv")}
        upload_response = authenticated_client.post("/chemistry/csv/upload", data={"analysis_type": "quick"}, files=files)
        job_id = upload_response.json()["data"]["job_id"]

        response = authenticated_client.delete(f"/chemistry/csv/jobs/{job_id}")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True

    def test_delete_nonexistent_job(self, authenticated_client):
        """Test deleting non-existent job."""
        response = authenticated_client.delete("/chemistry/csv/jobs/nonexistent-job")
        assert response.status_code == 404


class TestGetAnalysisHistory:
    """GET /api/chemistry/history"""

    def test_get_history_smiles(self, authenticated_client):
        """Test getting analysis history filtered by type."""
        response = authenticated_client.get("/chemistry/history?type=smiles")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "data" in data
        assert "current_page" in data["data"]

    def test_get_history_pagination(self, authenticated_client):
        """Test history pagination."""
        response = authenticated_client.get("/chemistry/history?page=1")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True

    def test_get_history_unauthorized(self, client):
        """Test history without auth."""
        response = client.get("/chemistry/history")
        assert response.status_code == 401
