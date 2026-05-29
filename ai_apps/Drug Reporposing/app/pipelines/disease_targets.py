"""
Stage 1: Disease-to-Targets Mapping using Open Targets API
Fetches the top proteins associated with a specific disease.
"""
import requests
import logging
from typing import Optional, List, Dict

logger = logging.getLogger(__name__)

class DiseaseTargetPipeline:
    def __init__(self, api_url: str = "https://api.platform.opentargets.org/api/v4/graphql"):
        self.api_url = api_url

    def get_disease_targets(self, disease_name: str, top_n: int = 10) -> Optional[List[Dict]]:
        """
        Fetches the top N target proteins associated with a specific disease.
        
        Args:
            disease_name: Name of the disease to search for
            top_n: Number of top targets to retrieve (default: 10)
        
        Returns:
            List of dicts with 'symbol' and 'score' keys, or None if disease not found
        """
        try:
            # Step 1: Search for the Disease ID (EFO ID)
            search_query = """
            query searchDisease($queryString: String!) {
              search(queryString: $queryString, entityNames: ["disease"]) {
                hits { id name }
              }
            }
            """
            variables = {"queryString": disease_name}
            
            logger.info(f"Searching for disease: {disease_name}")
            response = requests.post(
                self.api_url,
                json={'query': search_query, 'variables': variables},
                timeout=30
            )
            response.raise_for_status()
            
            search_results = response.json().get('data', {}).get('search', {}).get('hits', [])
            
            if not search_results:
                logger.warning(f"No disease found for: {disease_name}")
                return None

            disease_id = search_results[0]['id']
            disease_name_found = search_results[0]['name']
            logger.info(f"✅ Disease identified: {disease_name_found} ({disease_id})")

            # Step 2: Fetch top N associated proteins
            target_query = """
            query diseaseTargets($diseaseId: String!, $size: Int!) {
              disease(efoId: $diseaseId) {
                associatedTargets(page: {index: 0, size: $size}) {
                  rows {
                    target { approvedSymbol approvedName }
                    score
                  }
                }
              }
            }
            """
            variables = {"diseaseId": disease_id, "size": top_n}
            
            logger.info(f"Fetching top {top_n} targets for {disease_name_found}")
            response = requests.post(
                self.api_url,
                json={'query': target_query, 'variables': variables},
                timeout=30
            )
            response.raise_for_status()
            
            targets_data = response.json().get('data', {}).get('disease', {}).get('associatedTargets', {}).get('rows', [])
            
            result = [
                {
                    "symbol": r['target']['approvedSymbol'],
                    "name": r['target']['approvedName'],
                    "score": round(r['score'], 4)
                }
                for r in targets_data
            ]
            
            logger.info(f"Successfully fetched {len(result)} targets")
            return result

        except requests.exceptions.RequestException as e:
            logger.error(f"API request failed: {str(e)}")
            raise
        except Exception as e:
            logger.error(f"Error processing disease data: {str(e)}")
            raise
