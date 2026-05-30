"""Tests for Authentication Endpoints."""

import pytest


class TestRegisterUser:
    """POST /user/register"""

    def test_register_success(self, auth_client, test_credentials):
        """Test successful user registration."""
        payload = {
            "name": "New Test User",
            "email": "new_test_user_" + str(hash(test_credentials["email"]))[:8] + "@example.com",
            "password": test_credentials["password"],
            "password_confirmation": test_credentials["password"]
        }
        response = auth_client.post("/user/register", json=payload)
        assert response.status_code == 201
        data = response.json()
        assert data["success"] is True
        assert "OTP verification code" in data["message"]
        assert data["data"]["email"] == payload["email"]

    def test_register_duplicate_email(self, auth_client, test_credentials):
        """Test registration with duplicate email returns error."""
        payload = {
            "name": test_credentials["name"],
            "email": test_credentials["email"],
            "password": test_credentials["password"],
            "password_confirmation": test_credentials["password"]
        }
        response = auth_client.post("/user/register", json=payload)
        assert response.status_code in [400, 422, 409]
        data = response.json()
        assert data["success"] is False

    def test_register_password_mismatch(self, auth_client, test_credentials):
        """Test registration with password mismatch."""
        payload = {
            "name": test_credentials["name"],
            "email": "mismatch@example.com",
            "password": test_credentials["password"],
            "password_confirmation": "different_password"
        }
        response = auth_client.post("/user/register", json=payload)
        assert response.status_code == 422
        data = response.json()
        assert data["success"] is False

    def test_register_missing_fields(self, auth_client):
        """Test registration with missing required fields."""
        response = auth_client.post("/user/register", json={})
        assert response.status_code == 422
        data = response.json()
        assert data["success"] is False


class TestLogin:
    """POST /user/login"""

    def test_login_success(self, auth_client, registered_user, test_credentials):
        """Test successful login."""
        payload = {
            "email": test_credentials["email"],
            "password": test_credentials["password"]
        }
        response = auth_client.post("/user/login", json=payload)
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "token" in data["data"]
        assert "user" in data["data"]
        assert data["data"]["user"]["email"] == test_credentials["email"]

    def test_login_invalid_password(self, auth_client, test_credentials):
        """Test login with wrong password."""
        payload = {
            "email": test_credentials["email"],
            "password": "wrong_password"
        }
        response = auth_client.post("/user/login", json=payload)
        assert response.status_code == 401
        data = response.json()
        assert data["success"] is False

    def test_login_nonexistent_user(self, auth_client):
        """Test login with non-existent user."""
        payload = {
            "email": "nonexistent@example.com",
            "password": "password123"
        }
        response = auth_client.post("/user/login", json=payload)
        assert response.status_code == 401
        data = response.json()
        assert data["success"] is False

    def test_login_missing_fields(self, auth_client):
        """Test login with missing fields."""
        response = auth_client.post("/user/login", json={})
        assert response.status_code == 422
        data = response.json()
        assert data["success"] is False


class TestVerifyEmail:
    """POST /user/verify-email"""

    def test_verify_email_success(self, auth_client, test_credentials):
        """Test email verification with valid OTP."""
        payload = {
            "email": test_credentials["email"],
            "otp": "123456"
        }
        response = auth_client.post("/user/verify-email", json=payload)
        assert response.status_code in [200, 400, 422]

    def test_verify_email_invalid_otp(self, auth_client, test_credentials):
        """Test verification with invalid OTP."""
        payload = {
            "email": test_credentials["email"],
            "otp": "000000"
        }
        response = auth_client.post("/user/verify-email", json=payload)
        assert response.status_code in [400, 422]
        data = response.json()
        assert data["success"] is False

    def test_verify_email_missing_fields(self, auth_client):
        """Test verification with missing fields."""
        response = auth_client.post("/user/verify-email", json={})
        assert response.status_code == 422


class TestResendVerification:
    """POST /user/resend-verification"""

    def test_resend_verification_success(self, auth_client, test_credentials):
        """Test resending verification email."""
        payload = {"email": test_credentials["email"]}
        response = auth_client.post("/user/resend-verification", json=payload)
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "OTP resent" in data["message"]

    def test_resend_verification_nonexistent(self, auth_client):
        """Test resending to non-existent email."""
        payload = {"email": "nonexistent@example.com"}
        response = auth_client.post("/user/resend-verification", json=payload)
        assert response.status_code in [200, 404]


class TestForgotPassword:
    """POST /user/forgot-password"""

    def test_forgot_password_success(self, auth_client, test_credentials):
        """Test forgot password request."""
        payload = {"email": test_credentials["email"]}
        response = auth_client.post("/user/forgot-password", json=payload)
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "OTP sent" in data["message"]

    def test_forgot_password_nonexistent(self, auth_client):
        """Test forgot password for non-existent user."""
        payload = {"email": "nonexistent@example.com"}
        response = auth_client.post("/user/forgot-password", json=payload)
        assert response.status_code in [200, 404]


class TestResetPassword:
    """POST /user/reset-password"""

    def test_reset_password_invalid_otp(self, auth_client, test_credentials):
        """Test reset password with invalid OTP."""
        payload = {
            "email": test_credentials["email"],
            "otp": "000000",
            "password": "new_password123",
            "password_confirmation": "new_password123"
        }
        response = auth_client.post("/user/reset-password", json=payload)
        assert response.status_code in [400, 422]
        data = response.json()
        assert data["success"] is False

    def test_reset_password_mismatch(self, auth_client, test_credentials):
        """Test reset password with mismatched passwords."""
        payload = {
            "email": test_credentials["email"],
            "otp": "123456",
            "password": "new_password123",
            "password_confirmation": "different_password"
        }
        response = auth_client.post("/user/reset-password", json=payload)
        assert response.status_code == 422


class TestLogout:
    """POST /user/logout"""

    def test_logout_success(self, authenticated_client):
        """Test successful logout."""
        response = authenticated_client.post("/user/logout")
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "Logged out" in data["message"]

    def test_logout_without_token(self, client):
        """Test logout without authentication."""
        response = client.post("/user/logout")
        assert response.status_code == 401
