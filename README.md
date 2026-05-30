# AILIXIR API Test Suite

> Automated test suite for AILIXIR Drug Discovery API v2.1

## Quick Start

```bash
# 1. Install dependencies
pip install -r requirements.txt

# 2. Set environment variables
cp .env.example .env
# Edit .env with your credentials

# 3. Run all tests
pytest

# 4. Run specific module
pytest tests/test_auth.py -v

# 5. Run with coverage
pytest --cov=tests --cov-report=html
```

## Test Coverage

| Module | Endpoints | Tests |
|--------|-----------|-------|
| Authentication | 7 | ~25 |
| AI Agent | 12 | ~45 |
| Chemical Search | 4 | ~15 |
| User Management | 2 | ~8 |
| Awards & Scientists | 6 | ~18 |
| News | 8 | ~24 |
| **Total** | **39** | **~135** |

## Environment Variables

| Variable | Description | Required |
|----------|-------------|----------|
| `BASE_URL` | API base URL | Yes |
| `AUTH_BASE_URL` | Auth service URL | Yes |
| `TEST_EMAIL` | Test user email | Yes |
| `TEST_PASSWORD` | Test user password | Yes |
| `TEST_NAME` | Test user name | Yes |

## CI/CD

Tests run automatically on:
- Push to `main` or `develop`
- Pull requests to `main`
- Daily at 6 AM UTC

## Notes

- AI Agent endpoints have daily quotas (20 req/day for chat/analyze)
- CSV uploads limited to 100 rows
- Some tests may be slow due to async processing
- Use `-m "not slow"` to skip slow tests

## Project Structure

```
ailixir-api-tests/
├── .github/workflows/api-tests.yml
├── tests/
│   ├── conftest.py
│   ├── test_auth.py
│   ├── test_ai_agent.py
│   ├── test_chemical_search.py
│   ├── test_user_management.py
│   ├── test_awards_scientists.py
│   └── test_news.py
├── requirements.txt
├── pytest.ini
├── .env.example
└── README.md
```

## Running Tests

```bash
# All tests
pytest

# Specific module
pytest tests/test_auth.py

# With verbose output
pytest -v

# Skip slow tests
pytest -m "not slow"

# Specific test class
pytest tests/test_auth.py::TestLogin

# Specific test method
pytest tests/test_auth.py::TestLogin::test_login_success

# With HTML report
pytest --html=report.html
```

---

*Generated from AILIXIR API Reference v2.1*
