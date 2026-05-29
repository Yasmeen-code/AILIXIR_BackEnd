#!/usr/bin/env python
"""
Integration Test Suite for Drug Repurposing API
Tests all endpoints and verifies the entire pipeline works with real APIs
Run with: python test_integration.py
"""
import requests
import json
import time
from typing import Dict, Any, Tuple, Optional, List
import sys

# Configuration
API_BASE_URL = "http://localhost:8000"
TIMEOUT = 120  # 2 minutes timeout for screening

class Colors:
    """Terminal colors"""
    GREEN = '\033[92m'
    RED = '\033[91m'
    YELLOW = '\033[93m'
    BLUE = '\033[94m'
    RESET = '\033[0m'

def print_header(text: str):
    """Print a formatted header"""
    print(f"\n{Colors.BLUE}{'='*70}")
    print(f"  {text}")
    print(f"{'='*70}{Colors.RESET}\n")

def print_success(text: str):
    """Print success message"""
    print(f"{Colors.GREEN}✅ {text}{Colors.RESET}")

def print_error(text: str):
    """Print error message"""
    print(f"{Colors.RED}❌ {text}{Colors.RESET}")

def print_info(text: str):
    """Print info message"""
    print(f"{Colors.YELLOW}ℹ️  {text}{Colors.RESET}")

def test_health_check() -> bool:
    """Test health check endpoint"""
    print_header("Test 1: Health Check")
    try:
        response = requests.get(f"{API_BASE_URL}/health", timeout=10)
        if response.status_code == 200:
            data = response.json()
            print_success(f"API is healthy: {data['status']}")
            print(f"   Version: {data['version']}")
            print(f"   Service: {data['service']}")
            return True
        else:
            print_error(f"Health check failed with status {response.status_code}")
            return False
    except requests.exceptions.ConnectionError:
        print_error("Cannot connect to API. Is the server running on port 8000?")
        print_info("Start the server with: python -m uvicorn app.main:app --reload")
        return False
    except Exception as e:
        print_error(f"Health check error: {str(e)}")
        return False

def test_model_status() -> bool:
    """Test model status endpoint"""
    print_header("Test 2: Model Status")
    try:
        response = requests.get(f"{API_BASE_URL}/api/v1/model-status", timeout=10)
        if response.status_code == 200:
            data = response.json()
            print_success("Model status retrieved:")
            print(f"   Model: {data.get('model', 'N/A')}")
            print(f"   Device: {data.get('device', 'N/A')}")
            print(f"   GPU Available: {data.get('gpu_available', False)}")
            print(f"   Model Loaded: {data.get('model_loaded', False)}")
            print(f"   Using Mock: {data.get('using_mock_mode', False)}")
            print(f"   Batch Size: {data.get('batch_size', 'N/A')}")
            print(f"   Max Drugs: {data.get('max_drugs_per_screening', 'N/A')}")
            
            if not data.get('model_loaded') and data.get('using_mock_mode'):
                print_error("WARNING: DeepPurpose not loaded - using mock predictions")
                print_info("Install DeepPurpose for real predictions:")
                print_info("  pip install git+https://github.com/kexinhuang12345/DeepPurpose.git")
            
            return True
        else:
            print_error(f"Model status failed with status {response.status_code}")
            return False
    except Exception as e:
        print_error(f"Model status error: {str(e)}")
        return False

def test_disease_targets() -> Tuple[bool, Optional[List[Dict]]]:
    """Test disease targets endpoint (Stage 1)"""
    print_header("Test 3: Disease Target Identification (OpenTargets)")
    
    disease = "Type 2 Diabetes"
    payload = {
        "disease_name": disease,
        "top_n": 5
    }
    
    try:
        print_info(f"Searching for targets associated with: {disease}")
        response = requests.post(
            f"{API_BASE_URL}/api/v1/disease-targets",
            json=payload,
            timeout=30
        )
        
        if response.status_code == 200:
            data = response.json()
            print_success(f"Found {data['total_targets']} target proteins:")
            for i, target in enumerate(data['targets'][:3], 1):
                print(f"   {i}. {target['symbol']} ({target['name']}) - Score: {target['score']}")
            if len(data['targets']) > 3:
                print(f"   ... and {len(data['targets']) - 3} more")
            return True, data['targets']
        else:
            print_error(f"Disease target search failed: {response.status_code}")
            print_error(f"Response: {response.text}")
            return False, None
    except requests.exceptions.Timeout:
        print_error(f"Request timeout - OpenTargets API slow or unavailable")
        return False, None
    except Exception as e:
        print_error(f"Disease targets error: {str(e)}")
        return False, None

def test_protein_sequences(targets: Optional[List[Dict]]) -> Tuple[bool, Optional[List[Dict]]]:
    """Test protein sequences endpoint (Stage 2)"""
    print_header("Test 4: Protein Sequence Retrieval (UniProt)")
    
    if not targets or len(targets) == 0:
        print_error("No targets to test (skipping)")
        return False, None
    
    payload = targets[:3]  # Test with first 3 targets
    
    try:
        print_info(f"Fetching sequences for {len(payload)} targets...")
        response = requests.post(
            f"{API_BASE_URL}/api/v1/protein-sequences",
            json=payload,
            timeout=30
        )
        
        if response.status_code == 200:
            data = response.json()
            print_success(f"Successfully fetched {data['total_found']} sequences:")
            for target in data['targets'][:2]:
                seq_len = len(target.get('sequence', ''))
                print(f"   {target['symbol']}: {seq_len} amino acids")
            return True, data['targets']
        else:
            print_error(f"Protein sequence fetch failed: {response.status_code}")
            return False, None
    except Exception as e:
        print_error(f"Protein sequences error: {str(e)}")
        return False, None

def test_drug_library() -> Tuple[bool, Optional[List[Dict]]]:
    """Test drug library endpoint (Stage 3)"""
    print_header("Test 5: Drug Library Loading (TDC)")
    
    try:
        print_info("Loading drug library...")
        response = requests.get(
            f"{API_BASE_URL}/api/v1/drug-library",
            timeout=30
        )
        
        if response.status_code == 200:
            data = response.json()
            print_success(f"Loaded {data['total_drugs']} FDA-approved drugs:")
            for i, drug in enumerate(data['drugs'][:3], 1):
                print(f"   {i}. {drug['name']} (ID: {drug.get('drug_id', 'N/A')})")
                print(f"      SMILES: {drug['smiles'][:30]}...")
            if len(data['drugs']) > 3:
                print(f"   ... and {len(data['drugs']) - 3} more")
            return True, data['drugs']
        else:
            print_error(f"Drug library load failed: {response.status_code}")
            return False, None
    except Exception as e:
        print_error(f"Drug library error: {str(e)}")
        return False, None

def test_virtual_screening() -> bool:
    """Test full virtual screening endpoint (All stages)"""
    print_header("Test 6: FULL VIRTUAL SCREENING PIPELINE")
    
    payload = {
        "disease_name": "Type 2 Diabetes",
        "min_score": 0.5,
        "top_n_targets": 5,
        "known_drugs": ["Metformin", "Insulin"]
    }
    
    print_info("This is the REAL END-TO-END PIPELINE:")
    print_info("  Stage 1: Disease targets (OpenTargets)")
    print_info("  Stage 2: Protein sequences (UniProt)")
    print_info("  Stage 3: Drug library (TDC)")
    print_info("  Stage 4: AI predictions (DeepPurpose)")
    print_info("  Stage 5: Result processing")
    print_info("")
    print_info(f"Request: {json.dumps(payload, indent=2)}")
    print_info("")
    print_info("⏳ Running screening... (this may take 1-2 minutes)")
    
    start_time = time.time()
    
    try:
        response = requests.post(
            f"{API_BASE_URL}/api/v1/screen",
            json=payload,
            timeout=TIMEOUT
        )
        
        elapsed = time.time() - start_time
        
        if response.status_code == 200:
            data = response.json()
            print_success(f"✅ SCREENING COMPLETE in {elapsed:.1f} seconds")
            print("")
            print(f"Disease Targeted: {data['disease']}")
            print(f"Targets Used: {data['total_targets']}")
            print(f"Drugs Screened: {data['total_drugs']}")
            print(f"Total Predictions: {data['total_predictions']}")
            print(f"Success: {data['success']}")
            print(f"Message: {data['message']}")
            print("")
            
            if data['top_results']:
                print_success(f"Top {len(data['top_results'])} candidates:")
                for i, result in enumerate(data['top_results'], 1):
                    print(f"   {i}. {result['drug_name']} → {result['target_symbol']}")
                    print(f"      Score: {result['score']:.3f} | {result['status']}")
            else:
                print_error("No results found")
            
            return True
        else:
            print_error(f"Screening failed: {response.status_code}")
            print_error(f"Response: {response.text}")
            return False
    
    except requests.exceptions.Timeout:
        elapsed = time.time() - start_time
        print_error(f"Request timeout after {elapsed:.1f} seconds")
        print_info("The screening took too long. This could be:")
        print_info("  1. OpenTargets/UniProt APIs are slow")
        print_info("  2. DeepPurpose is running on CPU (try GPU)")
        print_info("  3. Network connection is slow")
        return False
    except Exception as e:
        print_error(f"Screening error: {str(e)}")
        return False

def run_all_tests():
    """Run all tests"""
    print(f"\n{Colors.BLUE}")
    print("╔══════════════════════════════════════════════════════════════════╗")
    print("║  🧬 DRUG REPURPOSING API - COMPREHENSIVE TEST SUITE              ║")
    print("║     Testing Production-Ready End-to-End Pipeline                 ║")
    print("╚══════════════════════════════════════════════════════════════════╝")
    print(f"{Colors.RESET}\n")
    
    results = []
    
    # Test 1: Health
    results.append(("Health Check", test_health_check()))
    if not results[-1][1]:
        print_error("API is not responding. Make sure the server is running.")
        print_info("Start with: python -m uvicorn app.main:app --reload")
        return
    
    # Test 2: Model Status
    results.append(("Model Status", test_model_status()))
    
    # Test 3: Disease Targets
    success, targets = test_disease_targets()
    results.append(("Disease Targets", success))
    
    # Test 4: Protein Sequences (requires targets)
    if targets:
        success, sequences = test_protein_sequences(targets)
        results.append(("Protein Sequences", success))
    else:
        results.append(("Protein Sequences", False))
    
    # Test 5: Drug Library
    success, drugs = test_drug_library()
    results.append(("Drug Library", success))
    
    # Test 6: Full Pipeline
    results.append(("Full Virtual Screening", test_virtual_screening()))
    
    # Print summary
    print_header("TEST SUMMARY")
    
    passed = sum(1 for _, success in results if success)
    total = len(results)
    
    for test_name, success in results:
        status = f"{Colors.GREEN}✅ PASSED{Colors.RESET}" if success else f"{Colors.RED}❌ FAILED{Colors.RESET}"
        print(f"{test_name:.<50} {status}")
    
    print("")
    print_success(f"TOTAL: {passed}/{total} tests passed")
    
    if passed == total:
        print_success("🎉 ALL TESTS PASSED! System is production-ready.")
    else:
        print_error(f"⚠️  {total - passed} test(s) failed. See details above.")
    
    print("")

if __name__ == "__main__":
    try:
        run_all_tests()
    except KeyboardInterrupt:
        print_error("\nTests interrupted by user")
        sys.exit(1)
    except Exception as e:
        print_error(f"Test suite error: {str(e)}")
        sys.exit(1)
