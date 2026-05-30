"""Tests for Awards & Scientists Endpoints."""

import pytest


class TestListAwards:
    """GET /awards"""

    def test_list_awards_default(self, client):
        """Test listing awards with default pagination."""
        response = client.get("/awards")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "data" in data
        assert "results" in data["data"]
        assert "pagination" in data["data"]
        pagination = data["data"]["pagination"]
        assert "currentPage" in pagination
        assert "totalPages" in pagination

    def test_list_awards_pagination(self, client):
        """Test listing awards with custom pagination."""
        response = client.get("/awards?page=1&per_page=5")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert len(data["data"]["results"]) <= 5

    def test_list_awards_structure(self, client):
        """Test awards response structure."""
        response = client.get("/awards")
        data = response.json()
        if len(data["data"]["results"]) > 0:
            award = data["data"]["results"][0]
            assert "id" in award
            assert "name" in award
            assert "category" in award
            assert "scientists_count" in award


class TestGetAwardDetails:
    """GET /awards/{award_id}"""

    def test_get_award_details(self, client):
        """Test getting specific award details."""
        list_response = client.get("/awards")
        awards = list_response.json()["data"]["results"]
        if len(awards) == 0:
            pytest.skip("No awards available")

        award_id = awards[0]["id"]
        response = client.get(f"/awards/{award_id}")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "data" in data
        assert data["data"]["id"] == award_id

    def test_get_award_not_found(self, client):
        """Test getting non-existent award."""
        response = client.get("/awards/999999")
        assert response.status_code == 404


class TestGetAwardScientists:
    """GET /awards/{award_id}/scientists"""

    def test_get_award_scientists(self, client):
        """Test getting scientists for an award."""
        list_response = client.get("/awards")
        awards = list_response.json()["data"]["results"]
        if len(awards) == 0:
            pytest.skip("No awards available")

        award_id = awards[0]["id"]
        response = client.get(f"/awards/{award_id}/scientists")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert isinstance(data["data"], list)

    def test_get_award_scientists_not_found(self, client):
        """Test getting scientists for non-existent award."""
        response = client.get("/awards/999999/scientists")
        assert response.status_code == 404


class TestListScientists:
    """GET /scientists"""

    def test_list_scientists_default(self, client):
        """Test listing scientists with default pagination."""
        response = client.get("/scientists")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "data" in data
        assert "results" in data["data"]

    def test_list_scientists_pagination(self, client):
        """Test listing scientists with custom pagination."""
        response = client.get("/scientists?page=1&per_page=10")
        assert response.status_code == 200
        data = response.json()
        assert len(data["data"]["results"]) <= 10

    def test_list_scientists_structure(self, client):
        """Test scientists response structure."""
        response = client.get("/scientists")
        data = response.json()
        if len(data["data"]["results"]) > 0:
            scientist = data["data"]["results"][0]
            assert "id" in scientist
            assert "name" in scientist
            assert "nationality" in scientist
            assert "field" in scientist


class TestGetScientistDetails:
    """GET /scientists/{scientist_id}"""

    def test_get_scientist_details(self, client):
        """Test getting specific scientist details."""
        list_response = client.get("/scientists")
        scientists = list_response.json()["data"]["results"]
        if len(scientists) == 0:
            pytest.skip("No scientists available")

        scientist_id = scientists[0]["id"]
        response = client.get(f"/scientists/{scientist_id}")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert data["data"]["id"] == scientist_id
        assert "awards" in data["data"]

    def test_get_scientist_not_found(self, client):
        """Test getting non-existent scientist."""
        response = client.get("/scientists/999999")
        assert response.status_code == 404


class TestGetScientistAwards:
    """GET /scientists/{scientist_id}/awards"""

    def test_get_scientist_awards(self, client):
        """Test getting awards for a scientist."""
        list_response = client.get("/scientists")
        scientists = list_response.json()["data"]["results"]
        if len(scientists) == 0:
            pytest.skip("No scientists available")

        scientist_id = scientists[0]["id"]
        response = client.get(f"/scientists/{scientist_id}/awards")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert isinstance(data["data"], list)

    def test_get_scientist_awards_not_found(self, client):
        """Test getting awards for non-existent scientist."""
        response = client.get("/scientists/999999/awards")
        assert response.status_code == 404
