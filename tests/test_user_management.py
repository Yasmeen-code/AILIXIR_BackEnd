import os
"""
AILIXIR User Management API Tests
==================================
Tests for user profile endpoints.
Run independently without affecting other tests.
"""

import pytest
import requests
import uuid

# ─── Configuration ───────────────────────────────────────────────
BASE_URL = os.environ.get("BASE_URL", "https://america-hyperlipemic-grazyna.ngrok-free.dev/api")

TEST_EMAIL = os.environ.get("TEST_EMAIL", "salehyasmeen080@gmail.com")
TEST_PASSWORD = os.environ.get("TEST_PASSWORD", "123456789")
TEST_NAME = os.environ.get("TEST_NAME", "yasmeen564")


# ─── Fixtures ────────────────────────────────────────────────────

@pytest.fixture(scope="module")
def api_client():
    """Provide a requests session for API calls."""
    session = requests.Session()
    session.headers.update({
        "Content-Type": "application/json",
        "Accept": "application/json"
    })
    yield session
    session.close()


@pytest.fixture(scope="module")
def auth_token(api_client):
    """Login and return a valid auth token."""
    response = api_client.post(
        f"{BASE_URL}/user/login",
        json={"email": TEST_EMAIL, "password": TEST_PASSWORD}
    )
    print(f"\n[auth_token] Status: {response.status_code}")
    assert response.status_code == 200, f"Login failed: {response.text}"
    data = response.json()
    assert data["success"] is True
    return data["data"]["token"]


# ════════════════════════════════════════════════════════════════
# GET PROFILE
# ════════════════════════════════════════════════════════════════

class TestGetProfile:
    """Tests for GET /user/profile"""

    def test_get_profile_success(self, api_client, auth_token):
        """Test getting profile with valid token."""
        api_client.headers.update({"Authorization": f"Bearer {auth_token}"})
        response = api_client.get(f"{BASE_URL}/user/profile")
        print(f"\n[get_profile] Status: {response.status_code}")
        print(f"[get_profile] Response: {response.text[:400]}")

        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "data" in data
        # API returns results array with pagination, not direct user object
        assert "results" in data["data"]
        assert len(data["data"]["results"]) > 0
        user = data["data"]["results"][0]
        assert "id" in user
        assert "name" in user
        assert "email" in user

    def test_get_profile_no_token(self, api_client):
        """Test getting profile without token."""
        if "Authorization" in api_client.headers:
            del api_client.headers["Authorization"]

        response = api_client.get(f"{BASE_URL}/user/profile")
        print(f"\n[get_profile_no_token] Status: {response.status_code}")

        assert response.status_code in [401, 403]

    def test_get_profile_invalid_token(self, api_client):
        """Test getting profile with invalid token."""
        api_client.headers.update({"Authorization": "Bearer invalid_token_12345"})

        response = api_client.get(f"{BASE_URL}/user/profile")
        print(f"\n[get_profile_invalid] Status: {response.status_code}")

        assert response.status_code in [401, 403]


# ════════════════════════════════════════════════════════════════
# UPDATE PROFILE
# ════════════════════════════════════════════════════════════════

class TestUpdateProfile:
    """Tests for POST /user/update-profile"""

    def test_update_profile_success(self, api_client, auth_token):
        """Test updating profile with valid data."""
        api_client.headers.update({"Authorization": f"Bearer {auth_token}"})

        unique_suffix = uuid.uuid4().hex[:6]
        payload = {
            "name": f"{TEST_NAME} Updated",
            "profile": {
                "institution": f"Test University {unique_suffix}",
                "research_focus": "drug_discovery",
                "bio": "Updated bio for testing"
            }
        }

        response = api_client.post(f"{BASE_URL}/user/update-profile", json=payload)
        print(f"\n[update_profile] Status: {response.status_code}")
        print(f"[update_profile] Response: {response.text[:400]}")

        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "Profile updated" in data["message"]
        assert "data" in data
        # API returns results array with pagination
        assert "results" in data["data"]
        assert len(data["data"]["results"]) > 0
        user = data["data"]["results"][0]
        assert user["name"] == f"{TEST_NAME} Updated"

    def test_update_profile_no_token(self, api_client):
        """Test updating profile without token."""
        if "Authorization" in api_client.headers:
            del api_client.headers["Authorization"]

        payload = {
            "name": "Test User",
            "profile": {
                "institution": "Test",
                "research_focus": "test",
                "bio": "test"
            }
        }

        response = api_client.post(f"{BASE_URL}/user/update-profile", json=payload)
        print(f"\n[update_no_token] Status: {response.status_code}")

        assert response.status_code in [401, 403]

    def test_update_profile_invalid_token(self, api_client):
        """Test updating profile with invalid token."""
        api_client.headers.update({"Authorization": "Bearer invalid_token_12345"})

        payload = {
            "name": "Test User",
            "profile": {
                "institution": "Test",
                "research_focus": "test",
                "bio": "test"
            }
        }

        response = api_client.post(f"{BASE_URL}/user/update-profile", json=payload)
        print(f"\n[update_invalid_token] Status: {response.status_code}")

        assert response.status_code in [401, 403]

    def test_update_profile_partial_data(self, api_client, auth_token):
        """Test updating profile with partial data (name only)."""
        api_client.headers.update({"Authorization": f"Bearer {auth_token}"})

        payload = {
            "name": f"{TEST_NAME} Partial"
        }

        response = api_client.post(f"{BASE_URL}/user/update-profile", json=payload)
        print(f"\n[update_partial] Status: {response.status_code}")
        print(f"[update_partial] Response: {response.text[:400]}")

        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "results" in data["data"]
        user = data["data"]["results"][0]
        assert user["name"] == f"{TEST_NAME} Partial"


# ════════════════════════════════════════════════════════════════
# ENDPOINT STRUCTURE
# ════════════════════════════════════════════════════════════════

class TestEndpointStructure:
    """Verify endpoints exist."""

    endpoints = [
        ("/user/profile", "GET"),
        ("/user/update-profile", "POST"),
    ]

    @pytest.mark.parametrize("endpoint,method", endpoints)
    def test_endpoint_exists(self, api_client, endpoint, method):
        """Verify endpoint exists."""
        url = f"{BASE_URL}{endpoint}"

        if method == "GET":
            response = api_client.get(url)
        else:
            response = api_client.post(url, json={})

        print(f"\n[endpoint {endpoint}] Status: {response.status_code}")

        assert response.status_code != 404, f"Endpoint {endpoint} not found!"