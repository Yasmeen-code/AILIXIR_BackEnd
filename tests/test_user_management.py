"""Tests for User Management Endpoints."""

import pytest


class TestGetProfile:
    """GET /user/profile"""

    def test_get_profile_success(self, authenticated_client, test_credentials):
        """Test getting user profile."""
        response = authenticated_client.get("/user/profile")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "user" in data
        assert data["user"]["email"] == test_credentials["email"]
        assert "id" in data["user"]
        assert "profile" in data["user"]

    def test_get_profile_unauthorized(self, client):
        """Test getting profile without auth."""
        response = client.get("/user/profile")
        assert response.status_code == 401

    def test_get_profile_invalid_token(self, client):
        """Test getting profile with invalid token."""
        client.headers.update({"Authorization": "Bearer invalid_token"})
        response = client.get("/user/profile")
        assert response.status_code == 401


class TestUpdateProfile:
    """POST /user/update-profile"""

    def test_update_profile_success(self, authenticated_client):
        """Test updating user profile."""
        payload = {
            "name": "Updated Test User",
            "profile": {
                "institution": "Test University",
                "research_focus": "drug_discovery",
                "bio": "Updated bio for testing"
            }
        }
        response = authenticated_client.post("/user/update-profile", json=payload)
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "Profile updated" in data["message"]

    def test_update_profile_partial(self, authenticated_client):
        """Test partial profile update."""
        payload = {
            "name": "Partial Update User"
        }
        response = authenticated_client.post("/user/update-profile", json=payload)
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True

    def test_update_profile_unauthorized(self, client):
        """Test updating profile without auth."""
        payload = {"name": "Test"}
        response = client.post("/user/update-profile", json=payload)
        assert response.status_code == 401
