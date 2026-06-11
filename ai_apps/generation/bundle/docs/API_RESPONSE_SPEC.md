# API Response Specification — EGFR AI Backend Bundle

This document describes the expected response shape for the backend/frontend integration.

## 1. Generate endpoint

### Request

```json
{
  "preset": "egfr_generator",
  "num_molecules": 100,
  "return_top_k": 20,
  "docking_mode": "off"
}

Allowed docking modes
off
top_k
all

Meaning:

off: no docking is run. Docking columns are returned as empty/null.
top_k: docking is run only for the top-k generated molecules after property + affinity scoring.
all: docking is run for all returned molecules. This is slower.
##

2. Response
**Review ai_artifacts_release\examples\generate_response.example.json
{
  "job_id": "gen_001",
  "preset": "egfr_generator",
  "status": "completed",
  "docking_mode": "off",
  "summary": {
    "num_generated": 100,
    "num_valid": 100,
    "num_returned": 20
  },
  "columns": [
    "SMILES",
    "SMILES_state",
    "NLL",
    "valid",
    "canonical_smiles",
    "mw",
    "logp",
    "tpsa",
    "hbd",
    "hba",
    "rot_bonds",
    "qed",
    "sa_score",
    "pred_pAff_mean",
    "docking_score",
    "docking_status",
    "docking_pose_file"
  ],
  "results": [
    {
      "SMILES": "CNCCC(=O)Nc1ccc2nncc(-c3ccc4cncnc4c3)c2c1",
      "SMILES_state": 1,
      "NLL": 5.71,
      "valid": true,
      "canonical_smiles": "CNCCC(=O)Nc1ccc2nncc(-c3ccc4cncnc4c3)c2c1",
      "mw": 358.405,
      "logp": 2.788,
      "tpsa": 92.69,
      "hbd": 2,
      "hba": 6,
      "rot_bonds": 5,
      "qed": 0.5698,
      "sa_score": 2.5204,
      "pred_pAff_mean": 11.0104,
      "docking_score": null,
      "docking_status": "not_run",
      "docking_pose_file": null
    }
  ],
  "warnings": [
    "Outputs are computational predictions only.",
    "Docking was not run for this job."   ## depend on the user's preference
  ]
}



2. Score SMILES endpoint
Request
{
  "smiles": [
    "CCO",
    "c1ccccc1"
  ],
  "docking_mode": "off"
}



4. Frontend/filter recommendations

Recommended filters:

valid == true
mw between 250 and 600
logp between 0 and 6
tpsa between 40 and 140
qed >= 0.3
pred_pAff_mean available
docking_status in ["not_run", "completed"]