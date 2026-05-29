"""
Stage 5: Result Processing & Filtering
Sorts results and labels them as 'Known Treatment' or 'Potential Discovery'.
"""
import logging
from typing import List, Dict

logger = logging.getLogger(__name__)

class ResultProcessingPipeline:
    
    @staticmethod
    def process_final_results(
        results: List[Dict],
        known_drugs: List[str],
        min_score: float = 0.0
    ) -> List[Dict]:
        """
        Sorts results and labels them as 'Known Treatment' or 'Potential Discovery'.
        
        Args:
            results: List of prediction results
            known_drugs: List of known drugs for the disease
            min_score: Minimum binding affinity score to include (default: 0.0)
        
        Returns:
            Processed and sorted results
        """
        logger.info(f"Processing {len(results)} results...")
        
        # Filter by minimum score
        filtered_results = [r for r in results if r['score'] >= min_score]
        logger.info(f"Filtered to {len(filtered_results)} results with score >= {min_score}")
        
        # Sort by score (descending)
        sorted_results = sorted(filtered_results, key=lambda x: x['score'], reverse=True)
        
        final_output = []
        for res in sorted_results:
            # Check if drug is already known for this disease
            is_known = any(
                known.lower() in res['drug_name'].lower()
                for known in known_drugs
            )
            res['status'] = "✅ Known Treatment" if is_known else "🆕 Potential Discovery"
            final_output.append(res)
        
        logger.info(f"✅ Processing complete. Found {len(final_output)} candidates")
        return final_output

    @staticmethod
    def get_top_results(results: List[Dict], top_n: int = 15) -> List[Dict]:
        """
        Returns the top N results.
        
        Args:
            results: List of processed results
            top_n: Number of top results to return
        
        Returns:
            Top N results
        """
        return results[:top_n]

    @staticmethod
    def get_potential_discoveries(results: List[Dict]) -> List[Dict]:
        """
        Returns only potential discoveries (non-known treatments).
        
        Args:
            results: List of processed results
        
        Returns:
            Potential discoveries only
        """
        return [r for r in results if "Potential Discovery" in r.get('status', '')]

    @staticmethod
    def get_results_by_target(results: List[Dict], target_symbol: str) -> List[Dict]:
        """
        Returns results filtered by target symbol.
        
        Args:
            results: List of processed results
            target_symbol: Target protein symbol to filter by
        
        Returns:
            Filtered results for the specific target
        """
        return [r for r in results if r['target_symbol'] == target_symbol]

    @staticmethod
    def format_results_table(results: List[Dict], top_n: int = 15) -> str:
        """
        Formats results as a string table.
        
        Args:
            results: List of results to format
            top_n: Number of results to display
        
        Returns:
            Formatted table string
        """
        table = f"{'Drug Name':<20} | {'Target':<10} | {'Score':<8} | {'Status'}\n"
        table += "-" * 65 + "\n"
        
        for res in results[:top_n]:
            table += (
                f"{res['drug_name']:<20} | "
                f"{res['target_symbol']:<10} | "
                f"{res['score']:<8} | "
                f"{res['status']}\n"
            )
        
        return table

    @staticmethod
    def export_results_csv(results: List[Dict], filename: str) -> None:
        """
        Exports results to CSV file.
        
        Args:
            results: List of results to export
            filename: Output CSV filename
        """
        try:
            import pandas as pd
            df = pd.DataFrame(results)
            df.to_csv(filename, index=False)
            logger.info(f"✅ Results exported to {filename}")
        except Exception as e:
            logger.error(f"Error exporting results: {str(e)}")
            raise
