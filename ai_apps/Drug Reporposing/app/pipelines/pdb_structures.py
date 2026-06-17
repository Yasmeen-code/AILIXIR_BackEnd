"""
Stage 3: PDB Structure & UniProt ID Fetch
Retrieves PDB structure IDs and UniProt accession numbers for target proteins.
Includes file-based caching to avoid redundant API calls.
"""
import requests
import json
import logging
import os
from datetime import datetime
from typing import List, Dict, Optional

logger = logging.getLogger(__name__)


class PdbStructurePipeline:
    def __init__(self, api_url: str = "https://rest.uniprot.org/uniprotkb/search",
                 cache_dir: str = None):
        self.api_url = api_url
        if cache_dir is None:
            cache_dir = os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(__file__))), "data")
        self.cache_dir = cache_dir
        os.makedirs(self.cache_dir, exist_ok=True)

    def _get_cache_path(self, symbol: str) -> str:
        return os.path.join(self.cache_dir, f"cache_pdb_{symbol.lower()}.json")

    def _load_from_cache(self, symbol: str) -> Optional[Dict]:
        cache_path = self._get_cache_path(symbol)
        if os.path.exists(cache_path):
            try:
                with open(cache_path, "r") as f:
                    data = json.load(f)
                if data.get("symbol") == symbol:
                    logger.info(f"  📦 Loaded PDB data from cache for: {symbol}")
                    return data
            except (json.JSONDecodeError, IOError) as e:
                logger.warning(f"  ⚠️ Failed to read cache for {symbol}: {e}")
        return None

    def _save_to_cache(self, symbol: str, data: Dict):
        cache_path = self._get_cache_path(symbol)
        data["fetched_at"] = datetime.utcnow().isoformat()
        try:
            with open(cache_path, "w") as f:
                json.dump(data, f, indent=2)
        except IOError as e:
            logger.warning(f"  ⚠️ Failed to write cache for {symbol}: {e}")

    def fetch_pdb_ids(self, targets: List[Dict]) -> List[Dict]:
        logger.info(f"Fetching PDB structures for {len(targets)} targets...")
        enriched = []
        for t in targets:
            symbol = t["symbol"]
            cached = self._load_from_cache(symbol)
            if cached is not None:
                t["pdb_ids"] = cached.get("pdb_ids", [])
                t["uniprot_id"] = cached.get("uniprot_id", "")
                enriched.append(t)
                continue
            try:
                params = {
                    "query": f"{symbol} AND taxonomy_id:9606",
                    "format": "json",
                    "size": 1,
                }
                response = requests.get(self.api_url, params=params, timeout=30)
                response.raise_for_status()
                data = response.json()
                uniprot_id = ""
                pdb_ids = []
                if data.get("results") and len(data["results"]) > 0:
                    result = data["results"][0]
                    uniprot_id = result.get("primaryAccession", "")
                    xrefs = result.get("uniProtKBCrossReferences", [])
                    for xref in xrefs:
                        if xref.get("database") == "PDB":
                            pdb_id = xref.get("id")
                            if pdb_id:
                                pdb_ids.append(pdb_id)
                    pdb_ids = pdb_ids[:3]
                t["pdb_ids"] = pdb_ids
                t["uniprot_id"] = uniprot_id
                if pdb_ids:
                    logger.info(f"  ✅ Found {len(pdb_ids)} PDB IDs for: {symbol}")
                else:
                    logger.warning(f"  ⚠️ No PDB structures found for: {symbol}")
                self._save_to_cache(symbol, {
                    "symbol": symbol,
                    "uniprot_id": uniprot_id,
                    "pdb_ids": pdb_ids,
                })
            except requests.exceptions.RequestException as e:
                logger.warning(f"  ⚠️ Failed to fetch PDB for {symbol}: {e}")
                t["pdb_ids"] = []
                t["uniprot_id"] = ""
            except Exception as e:
                logger.warning(f"  ⚠️ Failed to fetch PDB for {symbol}: {e}")
                t["pdb_ids"] = []
                t["uniprot_id"] = ""
            enriched.append(t)
        logger.info(f"✅ PDB fetch complete for {len(enriched)}/{len(targets)} targets")
        return enriched
