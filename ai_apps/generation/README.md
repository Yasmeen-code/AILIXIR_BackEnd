\# Ailixir Source Code



This repository contains the source code and Docker build files for the Ailixir EGFR drug discovery pipeline.



\## Components



\- FastAPI backend

\- DeepPurpose affinity prediction service

\- REINVENT generation integration

\- RDKit descriptors

\- SA score calculation

\- CUDA AutoDock-GPU docking integration

\- Ligand export to PDB, PDBQT, and MOL2



\## Main files



\- Dockerfile.api

\- Dockerfile.dp

\- docker-compose.yml

\- api.py

\- bundle/configs

\- bundle/models

\- bundle/docking

\- bundle/tools



\## Build



```powershell

docker compose build

