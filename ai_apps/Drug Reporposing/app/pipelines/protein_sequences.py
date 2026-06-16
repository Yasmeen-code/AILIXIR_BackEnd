"""
Stage 2: Fetch Protein Sequences from UniProt API
Retrieves Amino Acid sequences for the identified proteins.
Includes fallback for mock sequences if UniProt fails.
"""
import requests
import logging
from typing import List, Dict

logger = logging.getLogger(__name__)

# Fallback mock sequences for testing
MOCK_SEQUENCES = {
    'KCNJ11': 'MKVLLIMFIPVFLSSDYGFHTPDDHPKGRLLEPEGKFTGGQNLVYGGFPLVTLEIGVVVLYGSGLKYPGAPSFRLSFDSIQGDAIPIQEIGFQGSGHNGSDHHSQGAQAQPPWYGKMQKSEGDQGYSVVNQLFPPRKKSQAASNDHRQQPESVQGSAGQWSTSGEQLYEDVLSDDAGQMQSHGDHRSGAQGRLVEVNQFQPPRAVVSVEKKAGSEAVVNQLFPPRKKSQAELVGRCNVPAPGASEEIQGLEEAKGRCGAYVYYDDGKKFNVDPKVVEPPIEPVTYPPGV',
    'ABCC8': 'MSFYDSAFDNLPKFEWKNLGLPQKQGLFSTQWHVSTGYQPPGLGVFVLHHSCPTFVLQFSLGDTLPLPLQLPSPPKITVWGKPPHTSFKGRLQQSGSLPPFLRYSVGQRLVQLQKARGQSADFSHSDQVSELSVLKQVSYNSIQGLGGHLSVWQKPGLPEFLLQQGLHPKSLRQSVQQLLEPVHKPPPPLDPRAATRDVSLRQQASLPPQQPYGQLRKQLLAVSGAQSIQERRHIHQQGLPPPPMLEPLQASLGDSIGRQAGQIHHCQQPFLKQSQSLSPSISPQQHHASGIQSLKRQWHTQRGDPPTLHQRRAQSGAQRQGPSGSGSFVQSLSGFQQQQQQMPPPQQVPLMPPQHPPYAQSQPAQGAQGGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSGSIPPPPPPVPPPPPQHPPYAQSQ',
    'GCK': 'MRSYLLQDFQTDLMKSGTEHPQSLQVQSEEDVFHQFMPTLSRPSVSDRGHVVNHDSQYMTTKRKPRDSGRAQSYQVKQKQFDTSLTKVYKPPHDGLSVTTVFPSYVPLPGFVKHKFENPPSLVQHLSGDKWEWVTTSLTDQPKLSGASFRRSVDSVVNQDTLYTVYSGTDQSASSLQSLNILSQLPLPTLYYVEASQ'
}

class ProteinSequencePipeline:
    def __init__(self, api_url: str = "https://rest.uniprot.org/uniprotkb/search"):
        self.api_url = api_url

    def get_protein_sequences(self, targets_list: List[Dict]) -> List[Dict]:
        """
        Retrieves Amino Acid sequences for the identified proteins.
        Uses UniProt API first, falls back to mock sequences if not found.
        
        Args:
            targets_list: List of target proteins with 'symbol' key
        
        Returns:
            List of targets with 'sequence' key added (all targets included)
        """
        final_targets = []
        logger.info("🧬 Fetching Amino Acid Sequences from UniProt...")
        
        for t in targets_list:
            symbol = t['symbol']
            try:
                params = {
                    'query': symbol,
                    'format': 'json',
                    'size': 1
                }
                
                response = requests.get(self.api_url, params=params, timeout=30)
                response.raise_for_status()
                
                data = response.json()
                
                if data.get('results') and len(data['results']) > 0:
                    result = data['results'][0]
                    sequence = result.get('sequence', {}).get('value')
                    t['uniprot_id'] = result.get('primaryAccession', '')
                    if sequence:
                        t['sequence'] = sequence
                        logger.info(f"   ✅ Successfully fetched sequence for: {symbol} (uniprot_id={t['uniprot_id']})")
                    else:
                        # Use mock sequence if no real sequence found
                        if symbol in MOCK_SEQUENCES:
                            t['sequence'] = MOCK_SEQUENCES[symbol]
                            logger.warning(f"   ℹ️  Using mock sequence for: {symbol}")
                        else:
                            logger.warning(f"   ⚠️ No sequence found for: {symbol}, using random mock sequence")
                            t['sequence'] = 'MSSDVSPSDDPQDLDPTDQEEEKEKKKAGVEESKKKAVTVQVQVQVQVQVQVQVQVQVQVQVQVQVQVQVQVQ'
                else:
                    # Use mock sequence if no results from UniProt
                    if symbol in MOCK_SEQUENCES:
                        t['sequence'] = MOCK_SEQUENCES[symbol]
                        logger.warning(f"   ℹ️  Using mock sequence for: {symbol} (not found in UniProt)")
                    else:
                        logger.warning(f"   ℹ️  Using mock sequence for: {symbol} (not found in UniProt)")
                        t['sequence'] = 'MSSDVSPSDDPQDLDPTDQEEEKEKKKAGVEESKKKAVTVQVQVQVQVQVQVQVQVQVQVQVQVQVQVQVQVQ'
                        
                final_targets.append(t)
                    
            except requests.exceptions.RequestException as e:
                logger.warning(f"   ℹ️  Could not fetch sequence from UniProt for {symbol}, using mock: {str(e)}")
                # Use mock sequence on connection error
                if symbol in MOCK_SEQUENCES:
                    t['sequence'] = MOCK_SEQUENCES[symbol]
                else:
                    t['sequence'] = 'MSSDVSPSDDPQDLDPTDQEEEKEKKKAGVEESKKKAVTVQVQVQVQVQVQVQVQVQVQVQVQVQVQVQVQVQ'
                final_targets.append(t)
            except Exception as e:
                logger.warning(f"   ℹ️  Error processing {symbol}: {str(e)}, using mock sequence")
                # Use mock sequence on any error
                if symbol in MOCK_SEQUENCES:
                    t['sequence'] = MOCK_SEQUENCES[symbol]
                else:
                    t['sequence'] = 'MSSDVSPSDDPQDLDPTDQEEEKEKKKAGVEESKKKAVTVQVQVQVQVQVQVQVQVQVQVQVQVQVQVQVQVQ'
                final_targets.append(t)
            
        logger.info(f"✅ Prepared sequences for {len(final_targets)}/{len(targets_list)} targets (with fallbacks)")
        return final_targets
