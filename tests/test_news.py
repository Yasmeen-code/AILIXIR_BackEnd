"""Tests for News Endpoints."""

import pytest


class TestGetNewsFeed:
    """GET /news"""

    def test_get_news_default(self, authenticated_client):
        """Test getting news feed with default pagination."""
        response = authenticated_client.get("/news")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "data" in data
        assert "results" in data["data"]
        assert "pagination" in data["data"]

    def test_get_news_pagination(self, authenticated_client):
        """Test getting news with custom pagination."""
        response = authenticated_client.get("/news?page=2&per_page=5")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        pagination = data["data"]["pagination"]
        assert pagination["currentPage"] == 2
        assert len(data["data"]["results"]) <= 5

    def test_get_news_structure(self, authenticated_client):
        """Test news article structure."""
        response = authenticated_client.get("/news")
        data = response.json()
        if len(data["data"]["results"]) > 0:
            article = data["data"]["results"][0]
            assert "id" in article
            assert "title" in article
            assert "summary" in article
            assert "source" in article
            assert "published_at" in article

    def test_get_news_unauthorized(self, client):
        """Test getting news without auth."""
        response = client.get("/news")
        assert response.status_code == 401


class TestRefreshNews:
    """GET /news/refresh"""

    def test_refresh_news(self, authenticated_client):
        """Test refreshing news feed."""
        response = authenticated_client.get("/news/refresh")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "new_articles" in data["data"]
        assert "total_articles" in data["data"]

    def test_refresh_news_unauthorized(self, client):
        """Test refreshing news without auth."""
        response = client.get("/news/refresh")
        assert response.status_code == 401


class TestGetNewsCategories:
    """GET /news/categories"""

    def test_get_categories(self, authenticated_client):
        """Test getting news categories."""
        response = authenticated_client.get("/news/categories")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert isinstance(data["data"], list)
        expected = ["chemistry", "pharma", "biotech", "medicine", "research", "clinical_trials"]
        for cat in expected:
            assert cat in data["data"]

    def test_get_categories_unauthorized(self, client):
        """Test getting categories without auth."""
        response = client.get("/news/categories")
        assert response.status_code == 401


class TestSaveArticle:
    """POST /news/{article_id}/save"""

    def test_save_article(self, authenticated_client):
        """Test saving an article."""
        news_response = authenticated_client.get("/news")
        articles = news_response.json()["data"]["results"]
        if len(articles) == 0:
            pytest.skip("No articles available")

        article_id = articles[0]["id"]
        response = authenticated_client.post(f"/news/{article_id}/save")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "saved_article_id" in data["data"]

    def test_save_article_not_found(self, authenticated_client):
        """Test saving non-existent article."""
        response = authenticated_client.post("/news/999999/save")
        assert response.status_code == 404

    def test_save_article_unauthorized(self, client):
        """Test saving article without auth."""
        response = client.post("/news/1/save")
        assert response.status_code == 401


class TestShareArticle:
    """POST /news/{article_id}/share"""

    def test_share_article(self, authenticated_client):
        """Test sharing an article."""
        news_response = authenticated_client.get("/news")
        articles = news_response.json()["data"]["results"]
        if len(articles) == 0:
            pytest.skip("No articles available")

        article_id = articles[0]["id"]
        payload = {
            "share_with": "colleague@example.com",
            "message": "Check this out!"
        }
        response = authenticated_client.post(f"/news/{article_id}/share", json=payload)
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "share_id" in data["data"]

    def test_share_article_missing_email(self, authenticated_client):
        """Test sharing without recipient."""
        news_response = authenticated_client.get("/news")
        articles = news_response.json()["data"]["results"]
        if len(articles) == 0:
            pytest.skip("No articles available")

        article_id = articles[0]["id"]
        response = authenticated_client.post(f"/news/{article_id}/share", json={})
        assert response.status_code == 422

    def test_share_article_unauthorized(self, client):
        """Test sharing without auth."""
        response = client.post("/news/1/share", json={"share_with": "test@example.com"})
        assert response.status_code == 401


class TestGetSavedArticles:
    """GET /news/saved"""

    def test_get_saved_articles(self, authenticated_client):
        """Test getting saved articles."""
        response = authenticated_client.get("/news/saved")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "data" in data
        assert "results" in data["data"]
        assert "pagination" in data["data"]

    def test_get_saved_articles_unauthorized(self, client):
        """Test getting saved articles without auth."""
        response = client.get("/news/saved")
        assert response.status_code == 401


class TestUnsaveArticle:
    """DELETE /news/saved/{saved_article_id}"""

    def test_unsave_article(self, authenticated_client):
        """Test unsaving an article."""
        news_response = authenticated_client.get("/news")
        articles = news_response.json()["data"]["results"]
        if len(articles) == 0:
            pytest.skip("No articles available")

        article_id = articles[0]["id"]
        save_response = authenticated_client.post(f"/news/{article_id}/save")
        saved_id = save_response.json()["data"]["saved_article_id"]

        response = authenticated_client.delete(f"/news/saved/{saved_id}")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True

    def test_unsave_not_found(self, authenticated_client):
        """Test unsaving non-existent saved article."""
        response = authenticated_client.delete("/news/saved/999999")
        assert response.status_code == 404

    def test_unsave_unauthorized(self, client):
        """Test unsaving without auth."""
        response = client.delete("/news/saved/1")
        assert response.status_code == 401
