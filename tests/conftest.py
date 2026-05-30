"""Shared fixtures and configuration for AILIXIR API tests."""

import pytest
import httpx
import os
from dotenv import load_dotenv

load_dotenv()

BASE_URL = os.getenv("BASE_URL", "http://localhost:8080/api")
AUTH_BASE_URL = os.getenv("AUTH_BASE_URL", "https://ailixir.pharmaai.io/api")
TEST_EMAIL = os.getenv("TEST_EMAIL", "test@example.com")
TEST_PASSWORD = os.getenv("TEST_PASSWORD", "password123")
TEST_NAME = os.getenv("TEST_NAME", "Test User")


@pytest.fixture(scope="session")
def base_url():
    """Return the base URL for API requests."""
    return BASE_URL


@pytest.fixture(scope="session")
def auth_base_url():
    """Return the auth base URL for authentication requests."""
    return AUTH_BASE_URL


@pytest.fixture(scope="session")
def client():
    """Create a shared HTTP client for the test session."""
    with httpx.Client(base_url=BASE_URL, timeout=30.0) as c:
        yield c


@pytest.fixture(scope="session")
def auth_client():
    """Create an HTTP client for auth endpoints."""
    with httpx.Client(base_url=AUTH_BASE_URL, timeout=30.0) as c:
        yield c


@pytest.fixture(scope="session")
def test_credentials():
    """Return test user credentials."""
    return {
        "email": TEST_EMAIL,
        "password": TEST_PASSWORD,
        "name": TEST_NAME
    }


@pytest.fixture(scope="session")
def registered_user(auth_client, test_credentials):
    """Register a test user and return user data."""
    response = auth_client.post("/user/register", json={
        "name": test_credentials["name"],
        "email": test_credentials["email"],
        "password": test_credentials["password"],
        "password_confirmation": test_credentials["password"]
    })
    return test_credentials


@pytest.fixture(scope="session")
def access_token(auth_client, registered_user, test_credentials):
    """Login and return access token."""
    response = auth_client.post("/user/login", json={
        "email": test_credentials["email"],
        "password": test_credentials["password"]
    })
    assert response.status_code == 200
    data = response.json()
    assert data["success"] is True
    return data["data"]["token"]


@pytest.fixture(scope="session")
def authenticated_client(client, access_token):
    """Create an authenticated HTTP client."""
    client.headers.update({"Authorization": f"Bearer {access_token}"})
    return client


@pytest.fixture(scope="session")
def thread_id(authenticated_client):
    """Create a conversation thread and return thread_id."""
    response = authenticated_client.post("/chemistry/thread")
    assert response.status_code == 200
    data = response.json()
    assert data["success"] is True
    return data["data"]["thread_id"]
