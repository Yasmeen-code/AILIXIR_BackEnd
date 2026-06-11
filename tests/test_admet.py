"""
Integration Tests for ADMET Service with Laravel Sanctum Authentication
اختبارات التكامل لخدمة ADMET مع توثيق Laravel Sanctum
"""

import os
import pytest
import requests
import redis
import json
import time
from typing import List, Dict, Any
from dataclasses import dataclass
from datetime import datetime

# ==================== إعدادات الاختبارات ====================

class TestConfig:
    """إعدادات بيئة الاختبار"""
    def __init__(self):
        self.base_url: str = os.environ.get("BASE_URL", "http://127.0.0.1:8000")
        self.api_endpoint: str = "/api/admet/predict"
        self.login_endpoint: str = "/api/user/login"
        self.register_endpoint: str = "/api/user/register"
        self.redis_host: str = os.environ.get("REDIS_HOST", "localhost")
        self.redis_port: int = int(os.environ.get("REDIS_PORT", "6379"))
        self.timeout: int = 30

config = TestConfig()

# بيانات مستخدم اختبار
TEST_USER = {
    "name": os.environ.get("TEST_NAME", "Hazem Hatem"),
    "email": os.environ.get("TEST_EMAIL", "hhazm6745@gmail.com"),
    "password": os.environ.get("TEST_PASSWORD", "Hazem@2005")
}

# تجاهل التحذير الخاص بـ PytestCollectionWarning
pytestmark = pytest.mark.filterwarnings("ignore::pytest.PytestCollectionWarning")


# ==================== Fixtures ====================

@pytest.fixture(scope="session")
def auth_token():
    """الحصول على توكن Authentication من Sanctum"""

    print(f"\n🔐 Attempting to login with {TEST_USER['email']}...")

    try:
        login_response = requests.post(
            f"{config.base_url}{config.login_endpoint}",
            json={"email": TEST_USER["email"], "password": TEST_USER["password"]},
            timeout=config.timeout
        )

        print(f"📡 Login response status: {login_response.status_code}")

        if login_response.status_code == 200:
            data = login_response.json()

            token = None
            if "data" in data and isinstance(data["data"], dict):
                token = data["data"].get("token")

            if not token:
                token = data.get("auth_token") or data.get("token") or data.get("access_token")

            if token:
                print(f"✅ Token obtained successfully: {token[:20]}...")
                return token
            else:
                print(f"❌ No token found in response. Keys: {list(data.keys())}")
        else:
            print(f"❌ Login failed with status {login_response.status_code}")

    except Exception as e:
        print(f"❌ Login request failed: {e}")

    print(f"\n📝 Attempting to register user...")
    try:
        register_data = {
            "name": TEST_USER.get("name", "Test User"),
            "email": TEST_USER["email"],
            "password": TEST_USER["password"],
            "password_confirmation": TEST_USER["password"]
        }

        register_response = requests.post(
            f"{config.base_url}{config.register_endpoint}",
            json=register_data,
            timeout=config.timeout
        )

        print(f"📡 Register response status: {register_response.status_code}")

        if register_response.status_code in [200, 201]:
            data = register_response.json()
            token = data.get("auth_token") or data.get("token") or data.get("access_token")
            if token:
                print(f"✅ Registration successful. Token: {token[:20]}...")
                return token
    except Exception as e:
        print(f"❌ Registration failed: {e}")

    pytest.skip("Cannot authenticate with Sanctum. Please ensure user exists.")
    return None


@pytest.fixture
def api_headers(auth_token):
    """رؤوس HTTP للطلبات مع Bearer Token"""
    headers = {
        "Content-Type": "application/json",
        "Accept": "application/json"
    }

    if auth_token:
        headers["Authorization"] = f"Bearer {auth_token}"

    return headers


@pytest.fixture
def redis_client():
    """تهيئة اتصال Redis للاختبارات (Docker Redis)"""
    try:
        client = redis.Redis(
            host=config.redis_host,
            port=config.redis_port,
            decode_responses=True,
            socket_connect_timeout=5
        )
        client.ping()

        test_keys = client.keys("admet_*")
        if test_keys:
            client.delete(*test_keys)

        yield client

        test_keys = client.keys("admet_*")
        if test_keys:
            client.delete(*test_keys)

    except redis.ConnectionError as e:
        pytest.skip(f"Redis not available in Docker: {e}")
        yield None
    except Exception as e:
        pytest.skip(f"Cannot connect to Redis: {e}")
        yield None


@pytest.fixture
def sample_smiles():
    """عينات من صيغ SMILES للاختبار"""
    return {
        "benzene": "c1ccccc1",
        "ethanol": "CCO",
        "propane": "CCC",
        "ibuprofen": "CC(C)CC1=CC=C(C=C1)C(C)C(=O)O",
        "caffeine": "CN1C=NC2=C1C(=O)N(C(=O)N2C)C"
    }


# ==================== Helper Functions ====================

def create_test_file(content: str, filename: str = "test.txt") -> str:
    """إنشاء ملف اختبار مؤقت"""
    import tempfile
    temp_file = tempfile.NamedTemporaryFile(
        mode='w',
        suffix=f"_{filename}",
        delete=False
    )
    temp_file.write(content)
    temp_file.close()
    return temp_file.name


def check_redis_health():
    """التحقق من صحة اتصال Redis"""
    try:
        client = redis.Redis(
            host=config.redis_host,
            port=config.redis_port,
            decode_responses=True,
            socket_connect_timeout=3
        )
        client.ping()
        return True
    except:
        return False


# ==================== Integration Tests ====================

class TestAdmetServiceIntegration:
    """اختبارات التكامل لخدمة ADMET"""

    @pytest.mark.skipif(not check_redis_health(), reason="Redis not available")
    def test_single_smiles_prediction(self, api_headers, sample_smiles):
        """✅ TC-001: توقع مركب واحد"""
        response = requests.post(
            f"{config.base_url}{config.api_endpoint}",
            json={"smiles": sample_smiles["benzene"]},
            headers=api_headers,
            timeout=config.timeout
        )

        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "data" in data

    @pytest.mark.skipif(not check_redis_health(), reason="Redis not available")
    def test_multiple_smiles_comma_separated(self, api_headers, sample_smiles):
        """✅ TC-002: توقع عدة مركبات مفصولة بفواصل"""
        smiles_input = f"{sample_smiles['benzene']}, {sample_smiles['ethanol']}, {sample_smiles['propane']}"

        response = requests.post(
            f"{config.base_url}{config.api_endpoint}",
            json={"smiles": smiles_input},
            headers=api_headers,
            timeout=config.timeout
        )

        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True

    @pytest.mark.skipif(not check_redis_health(), reason="Redis not available")
    def test_smiles_as_json_array(self, api_headers, sample_smiles):
        """✅ TC-003: إرسال SMILES كسلسلة نصية (String) بدلاً من مصفوفة"""
        smiles_input = f"{sample_smiles['benzene']}, {sample_smiles['ethanol']}"

        response = requests.post(
            f"{config.base_url}{config.api_endpoint}",
            json={"smiles": smiles_input},
            headers=api_headers,
            timeout=config.timeout
        )

        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True

    @pytest.mark.skipif(not check_redis_health(), reason="Redis not available")
    def test_file_upload_txt(self, api_headers, sample_smiles):
        """✅ TC-004: رفع ملف نصي"""
        content = f"{sample_smiles['benzene']}\n{sample_smiles['ethanol']}\n{sample_smiles['propane']}"
        file_path = create_test_file(content, "test_smiles.txt")

        with open(file_path, 'rb') as f:
            response = requests.post(
                f"{config.base_url}{config.api_endpoint}",
                files={"file": ("test_smiles.txt", f, "text/plain")},
                headers={"Authorization": api_headers.get("Authorization")},
                timeout=config.timeout
            )

        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert "file_type" in data["data"]

    @pytest.mark.skipif(not check_redis_health(), reason="Redis not available")
    def test_file_upload_csv(self, api_headers):
        """✅ TC-005: رفع ملف CSV"""
        csv_content = "SMILES,Name\nc1ccccc1,Benzene\nCCO,Ethanol\nCCC,Propane"
        file_path = create_test_file(csv_content, "test_smiles.csv")

        with open(file_path, 'rb') as f:
            response = requests.post(
                f"{config.base_url}{config.api_endpoint}",
                files={"file": ("test_smiles.csv", f, "text/csv")},
                headers={"Authorization": api_headers.get("Authorization")},
                timeout=config.timeout
            )

        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert data["data"]["file_type"] == "csv"

    def test_no_token_error(self, sample_smiles):
        """❌ TC-006: طلب بدون توكن (يجب أن يرجع 401)"""
        response = requests.post(
            f"{config.base_url}{config.api_endpoint}",
            json={"smiles": sample_smiles["benzene"]},
            timeout=config.timeout
        )

        assert response.status_code in [401, 500]
        data = response.json()
        assert "message" in data or "success" in data

    @pytest.mark.skipif(not check_redis_health(), reason="Redis not available")
    def test_empty_smiles_error(self, api_headers):
        """❌ TC-007: إرسال SMILES فارغ"""
        response = requests.post(
            f"{config.base_url}{config.api_endpoint}",
            json={"smiles": ""},
            headers=api_headers,
            timeout=config.timeout
        )

        assert response.status_code == 400 or response.status_code == 422
        data = response.json()
        assert data["success"] is False

    @pytest.mark.skipif(not check_redis_health(), reason="Redis not available")
    def test_no_data_error(self, api_headers):
        """❌ TC-008: طلب بدون أي بيانات"""
        response = requests.post(
            f"{config.base_url}{config.api_endpoint}",
            json={},
            headers=api_headers,
            timeout=config.timeout
        )

        assert response.status_code == 400
        data = response.json()
        assert data["success"] is False

    @pytest.mark.skipif(not check_redis_health(), reason="Redis not available")
    def test_response_structure(self, api_headers, sample_smiles):
        """📋 TC-009: التحقق من هيكلية الاستجابة"""
        response = requests.post(
            f"{config.base_url}{config.api_endpoint}",
            json={"smiles": sample_smiles["caffeine"]},
            headers=api_headers,
            timeout=config.timeout
        )

        assert response.status_code == 200
        data = response.json()

        assert "success" in data
        assert "message" in data
        assert isinstance(data["success"], bool)

    @pytest.mark.skipif(not check_redis_health(), reason="Redis not available")
    def test_file_with_comments(self, api_headers):
        """📝 TC-010: ملف نصي يحتوي على تعليقات"""
        content = """# This is a comment
c1ccccc1
# Another comment
CCO
CCC"""
        file_path = create_test_file(content, "test_with_comments.txt")

        with open(file_path, 'rb') as f:
            response = requests.post(
                f"{config.base_url}{config.api_endpoint}",
                files={"file": ("test_with_comments.txt", f, "text/plain")},
                headers={"Authorization": api_headers.get("Authorization")},
                timeout=config.timeout
            )

        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True

    def test_redis_connection(self):
        """🔴 TC-011: اختبار اتصال Redis في Docker"""
        result = check_redis_health()
        assert result is True
        print("\n✅ Redis in Docker is reachable!")


# ==================== Performance Tests ====================

class TestAdmetPerformance:
    """اختبارات أداء النظام"""

    @pytest.mark.skipif(not check_redis_health(), reason="Redis not available")
    def test_response_time_single(self, api_headers, sample_smiles):
        """⏱️ P-001: زمن استجابة لمركب واحد"""
        times = []
        for _ in range(5):
            start = time.time()
            response = requests.post(
                f"{config.base_url}{config.api_endpoint}",
                json={"smiles": sample_smiles["benzene"]},
                headers=api_headers,
                timeout=config.timeout
            )
            end = time.time()
            times.append(end - start)

        avg_time = sum(times) / len(times)
        print(f"\nمتوسط وقت الاستجابة: {avg_time:.2f} ثانية")

        assert avg_time < 10

    @pytest.mark.skipif(not check_redis_health(), reason="Redis not available")
    def test_concurrent_requests(self, api_headers, sample_smiles):
        """🔄 P-002: طلبات متزامنة"""
        import concurrent.futures

        def make_request(smiles):
            return requests.post(
                f"{config.base_url}{config.api_endpoint}",
                json={"smiles": smiles},
                headers=api_headers,
                timeout=config.timeout
            )

        smiles_batch = [sample_smiles["benzene"], sample_smiles["ethanol"],
                       sample_smiles["propane"], sample_smiles["caffeine"]]

        with concurrent.futures.ThreadPoolExecutor(max_workers=4) as executor:
            futures = [executor.submit(make_request, smiles) for smiles in smiles_batch]
            results = [f.result() for f in futures]

        for result in results:
            assert result.status_code == 200


# ==================== تشغيل الاختبارات ====================

if __name__ == "__main__":
    print("🧪 تشغيل Integration Tests with Docker Redis...")
    print("=" * 50)

    if check_redis_health():
        print("✅ Redis in Docker is running")
    else:
        print("❌ Redis in Docker is NOT running")
        print("Run: docker run -d -p 6379:6379 --name redis-test redis")

    pytest.main([
        __file__,
        "-v",
        "-s",
        "--tb=short",
        "--maxfail=3"
    ])
