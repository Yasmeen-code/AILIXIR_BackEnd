# AILIXIR — Enterprise AI-Powered Drug Discovery & Chemistry Intelligence Platform

<div align="center">

<img src="Diagrams/ailixir_hero_logo.png" alt="AILIXIR Logo" width="240">

**Accelerating molecular generation, virtual screening, ADMET profiling, and structure-activity reasoning through microservice AI orchestration.**

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12.0-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com/)
[![Python](https://img.shields.io/badge/Python-3.10%20%7C%203.11-3776AB?style=flat-square&logo=python&logoColor=white)](https://www.python.org/)
[![FastAPI](https://img.shields.io/badge/FastAPI-0.100+-009688?style=flat-square&logo=fastapi&logoColor=white)](https://fastapi.tiangolo.com/)
[![PyTorch](https://img.shields.io/badge/PyTorch-2.0+-EE4C2C?style=flat-square&logo=pytorch&logoColor=white)](https://pytorch.org/)
[![RDKit](https://img.shields.io/badge/RDKit-2023.09+-005580?style=flat-square)](https://www.rdkit.org/)
[![Docker](https://img.shields.io/badge/Docker-Enabled-2496ED?style=flat-square&logo=docker&logoColor=white)](https://www.docker.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE)

---

[Key Features](#5-key-features) • [System Architecture](#6-system-architecture) • [AI Pipeline](#7-ai-pipeline) • [RAG Engine](#8-rag-pipeline) • [Installation](#13-installation-guide) • [Docker vs. HF Build](#14-docker-build-vs-hugging-face-spaces-build) • [API Docs](#16-api-documentation) • [Engineering Challenges](#17-engineering-challenges)

</div>

---

## 1. Hero Section

**AILIXIR** is an enterprise-grade AI drug discovery platform that bridges deep learning microservices with robust web orchestration. It integrates de novo molecule generation, target-based drug repurposing, multi-property ADMET profiling, high-dimensional chemical vector search, and molecular dynamics simulation into a unified API ecosystem.

### Explore AILIXIR

>- **Desktop Application:** [AILIXIR Flutter Frontend](https://github.com/RottenShadow/AiLixir)
>- **Download Application:** [AILIXIR Releases](https://github.com/RottenShadow/AiLixir/releases)
>- **System Architecture:** [Architecture & Component Documentation](ARCHITECTURE.md)
---

## 2. The Problem

### The Real-World Challenge in Early-Stage Drug Discovery

Developing a novel therapeutic molecule is one of the most expensive and time-consuming endeavors in modern science:
- **Timeline**: 10 to 15 years from target discovery to approval.
- **Cost**: Exceeds **$2.6 billion USD** per approved drug (including failed pipeline costs).
- **Failure Rate**: **>90%** of candidate molecules fail in clinical trials due to efficacy shortfalls or unexpected absorption, distribution, metabolism, excretion, and toxicity (ADMET) profile failures.

```
+-----------------------------------------------------------------------------------+
| TRADITIONAL DRUG DISCOVERY PIPELINE                                               |
|                                                                                   |
|  Target Identification   Compound Synthesis    In Vitro/Vivo ADMET    Clinical Trial |
|  [ 1 - 2 Years ] ------> [ 2 - 3 Years ] -----> [ 2 - 3 Years ] ----> [ 6+ Years ]|
|                          (High Friction)        (90% Failure Rate)    ($2.6B Total)|
+-----------------------------------------------------------------------------------+
```

### Key Pain Points
1. **Hyper-Dimensional Chemical Space**: Chemical space contains an estimated $10^{60}$ synthetically feasible drug-like molecules. Traditional high-throughput screening (HTS) screens only $10^6$ compounds (<0.0000000000000000000000000000000000000000000000000001% of space).
2. **Siloed Computational Tools**: Cheminformatics software, docking engines, deep learning property predictors, and molecular dynamics suites exist in fragmented command-line packages with incompatible data structures.
3. **Late-Stage ADMET Failures**: ADMET testing traditionally occurs late in candidate optimization, wasting millions on molecules that fail simple bioavailability or cytotoxicity filters.
4. **Lack of Explainability**: Black-box neural network predictions are routinely dismissed by medicinal chemists who require structural rationale for predictions.

---

## 3. Why Existing Solutions Are Not Enough

Traditional computational chemistry and early commercial platforms suffer from critical structural limitations:

| Dimension | Traditional Software / Scripting | Generic AI Wrappers | AILIXIR Platform |
|---|---|---|---|
| **Architectural Topology** | Monolithic local scripts (CLI heavy) | Monolithic API wrapper over 1 model | Microservice-orchestrated multi-model engine |
| **Pipeline Integration** | Manual file transfers (SDF, PDB, CSV) | REST endpoints for single task | Unified job queue with asynchronous callbacks |
| **ADMET Assessment** | Rule-of-five heuristics (Lipinski) | Single-property predictions | 5-task MPNN multi-target neural ensemble |
| **Search Scale & Speed** | CPU linear scanning ($O(N)$ execution) | Exact string SQL querying | FAISS binary vector indexing ($O(1)$ Hamming) |
| **Domain Explainability** | None (Raw numerical float outputs) | Generic non-domain LLM prompts | Domain-tuned RAG combining chemical topology |
| **Compute Elasticity** | Static single machine compute | Fixed cloud instances | Containerized async queues with GPU scaling |

### Technical Limitations of Prior Approaches
- **Rule-Based Heuristics**: Lipinski's Rule of 5 fails to account for active transport, modern macrocycles, or complex structural binding nuances.
- **Linear Similarity Scans**: Querying a 1,000,000+ molecule database using exact Tanimoto/Dice comparisons on unindexed CPU threads requires minutes per query.
- **Isolated Docking**: Running molecular docking independently without immediate ADMET feedback creates candidates with high binding affinity but toxic metabolic profiles.

---

## 4. Our Solution

AILIXIR bridges computational chemistry and modern software engineering by establishing a multi-layered, microservice-orchestrated API platform.

```mermaid
flowchart TB
    subgraph CLIENT_LAYER [Client & Consumer Tier]
        WebUI[Web UI / SPA]
        MobileApp[Mobile / CLI]
        ExtIntegrations[Third-Party REST Clients]
    end

    subgraph ORCHESTRATION_LAYER [Orchestration & Gateway Layer - Laravel 12]
        Gateway[API Gateway & Router]
        Auth[Sanctum Auth & Security]
        QueueManager[Database Queue Worker / Dispatcher]
        DB[(MariaDB / SQLite State)]
    end

    subgraph AI_MICROSERVICES [AI & Cheminformatics Microservices Engine - FastAPI / Python]
        ADMET_SVC[ADMET Predictor\n5-Task Ensemble MPNN]
        REPURPOSE_SVC[Drug Repurposing\nDeepPurpose MPNN-CNN + UniProt]
        RAG_SVC[Chemical RAG Engine\nFAISS Binary Vector + LLM]
        GEN_SVC[Molecular Generator\nREINVENT + AutoDock-GPU]
        CHEM_AGENT[Chemistry Agent API\nConversational Chem Assistant]
        MD_SVC[Molecular Dynamics\nGROMACS Simulation Engine]
    end

    WebUI -->|REST / JSON| Gateway
    MobileApp -->|REST / JSON| Gateway
    ExtIntegrations -->|REST / JSON| Gateway

    Gateway --> Auth
    Gateway --> QueueManager
    Gateway --> DB

    QueueManager -->|HTTP Sync/Async| ADMET_SVC
    QueueManager -->|HTTP Sync/Async| REPURPOSE_SVC
    QueueManager -->|HTTP Sync/Async| RAG_SVC
    QueueManager -->|HTTP Async Job| GEN_SVC
    Gateway -->|HTTP Streaming| CHEM_AGENT
    QueueManager -->|HTTP Async Job| MD_SVC
```

### Key Architectural Concepts
1. **Decoupled Gateway & Computation**: High-performance PHP 8.3/Laravel 12 handles user session state, payments, authentication, and job queuing, allowing Python microservices to focus exclusively on tensor computation and GPU inference.
2. **Unified Representation Standard**: All molecular structures are validated, standardized, and canonicalized using RDKit across all service boundaries using SMILES (Simplified Molecular Input Line Entry System) and PDB QT formats.
3. **Hybrid Synchronous & Asynchronous Execution**: Low-latency tasks (ADMET scoring, chemical search) resolve synchronously in `<300ms`, while compute-heavy jobs (molecular dynamics, docking, generation) dispatch to asynchronous background queues with polling endpoints.

---

## 5. Key Features

```
+---------------------------------------------------------------------------------------+
|                                    AILIXIR FEATURES                                   |
+--------------------------+--------------------------+---------------------------------+
| 🧬 AI & Deep Learning    | 🔬 Virtual Screening     | 🔍 RAG Vector Search            |
| - De novo REINVENT gen   | - DeepPurpose MPNN-CNN   | - FAISS binary indexing         |
| - 5-Task ADMET Ensemble  | - Target API integration | - Multi-fingerprint fusion      |
| - AutoDock-GPU scoring   | - UniProt sequence fetch | - Llama-3.1-8B explanations     |
+--------------------------+--------------------------+---------------------------------+
| 🤖 Chemistry AI Agent    | 🧪 Molecular Dynamics    | 💳 Platform Architecture        |
| - Conversational Chemist | - GROMACS simulation     | - Stripe tiered billing         |
| - CSV batch processing   | - Trajectory analytics   | - Cloudinary file management    |
| - SMILES verification    | - RMSD/RMSF extraction   | - Supervisor & Docker isolated  |
+--------------------------+--------------------------+---------------------------------+
```

### Feature Categories & Technical Breakdown

#### 1. Artificial Intelligence & Generative Chemistry
- **De Novo Molecule Generation**: Reinforcement-learning-driven molecular generator using REINVENT architecture, scored dynamically against custom protein targets via AutoDock-GPU.
- **Multi-Task ADMET Prediction**: Neural Message Passing Neural Network (MPNN) predicting 5 vital ADMET parameters (Human Intestinal Absorption, Blood-Brain Barrier Penetration, CYP450 Inhibition, Clearance, Toxicity).

#### 2. Virtual Screening & Drug Repurposing
- **Automated Target Integration**: Direct integration with OpenTargets and UniProt APIs to automatically extract target proteins, amino acid sequences, and disease association scores.
- **DeepPurpose Screening**: Deep learning drug-target interaction (DTI) predictions employing MPNN encoders for molecules and CNN encoders for protein sequences.

#### 3. High-Dimensional Chemical Retrieval
- **FAISS Binary Vector Indexing**: High-speed chemical similarity search capable of processing over 1,000,000 molecules with sub-millisecond latency using 2048-bit fingerprints.
- **Multi-Fingerprint Structural Fusion**: Hybrid similarity metrics combining Morgan (Circular), MACCS Keys, Atom Pairs, and Topological Torsions.
- **LLM-Augmented RAG Rationale**: Retrieval-Augmented Generation using Llama-3.1-8B-Instruct to produce automated natural language structure-activity relationship (SAR) summaries.

#### 4. Physics Simulation & Chemistry Agent
- **GROMACS MD Pipeline**: Full workflow automation for preparing molecular systems, applying force fields, running energy minimization, NVT/NPT equilibration, and production MD trajectories.
- **Conversational Chemistry Agent**: AI assistant capable of interpreting chemical queries, validating SMILES strings, comparing molecular pairs, and processing multi-molecule CSV payloads.

---

## 6. System Architecture

AILIXIR uses an isolated multi-container architecture orchestrated via Docker Compose.

### Detailed Layer Breakdown

```mermaid
flowchart LR
    subgraph CLIENT [Presentation Layer]
        Vite[Vite + React / SPA]
    end

    subgraph GATEWAY [Application & Orchestration Layer]
        Laravel[Laravel 12 Engine]
        Sanctum[Sanctum Middleware]
        Queue[Database Queue Workers]
        Cashier[Stripe Cashier Billing]
    end

    subgraph PERSISTENCE [Persistence & Storage]
        MariaDB[(MariaDB / SQLite)]
        Cloudinary[Cloudinary CDN Storage]
    end

    subgraph AI_SERVICES [Compute & Inference Tier]
        ADMET[ADMET Microservice :8001]
        Repurposing[Drug Repurposing :8002]
        RAG[Chemical RAG :8003]
        ChemAgent[Chem Agent :8004]
        GenDock[Generation & Docking :8005]
        MDSim[GROMACS MD Engine :8006]
    end

    Vite -->|HTTP REST| Laravel
    Laravel --> Sanctum
    Laravel --> MariaDB
    Laravel --> Cloudinary
    Laravel --> Queue
    Queue --> MariaDB

    Laravel -->|Guzzle HTTP| ADMET
    Laravel -->|Guzzle HTTP| Repurposing
    Laravel -->|Guzzle HTTP| RAG
    Laravel -->|Guzzle HTTP| ChemAgent
    Queue -->|HTTP Async| GenDock
    Queue -->|HTTP Async| MDSim
```

### Component Responsibilities

1. **Laravel Orchestration Core (`:8000`)**:
   - Manages routing, rate-limiting, user authentication (Sanctum), and subscription limits (Stripe).
   - Serves as the central API gateway; external clients never interact directly with raw Python AI microservices.
   - Manages job state tracking, persistent logs, and user activity history.

2. **ADMET Inference Microservice (`:8001`)**:
   - Built on FastAPI and PyTorch.
   - Loads pre-trained graph neural network weights into memory at startup.
   - Exposes `/predict` and `/predict-file` endpoints for single SMILES or bulk CSV processing.

3. **Drug Repurposing Microservice (`:8002`)**:
   - Communicates with OpenTargets GraphQL API and UniProt REST API.
   - Converts protein sequences into 1D CNN representations and SMILES into graph neural representations.
   - Generates binding affinity scores ($K_d$, $K_i$, or $IC_{50}$).

4. **Chemical RAG System (`:8003`)**:
   - Maintains persistent in-memory FAISS binary vector indices mapped to a disk volume.
   - Calculates binary Hamming distance between bit-packed fingerprints.
   - Calls Hugging Face Inference API (Llama-3.1-8B-Instruct) for context generation.

5. **Molecular Generation & Docking (`:8005`)**:
   - Executes AutoDock Vina / AutoDock-GPU binary instances.
   - Runs reinforcement learning optimization loops (REINVENT) to iterate SMILES toward binding affinity targets.

6. **Protein-Ligand MD Simulation (`:8006`)**:
   - Wraps GROMACS execution suites inside Docker containers.
   - Parses trajectory outputs (`.xtc`, `.trr`) into summary statistics (RMSD, RMSF, Hydrogen Bond counts).

---

## 7. AI Pipeline

The core decision pipeline translates qualitative disease inputs into scored, validated lead candidates through multi-stage neural reasoning.

```mermaid
sequenceDiagram
    autonumber
    actor User as Scientist / User
    participant Gateway as Laravel API Gateway
    participant Queue as Worker Queue
    participant Repurp as Drug Repurposing (DeepPurpose)
    participant ADMET as ADMET Ensemble
    participant RAG as Chemical RAG (FAISS+LLM)
    participant DB as System Database

    User->>Gateway: POST /api/screening/submit {disease_id}
    Gateway->>DB: Create Screening Job Record (Status: PENDING)
    Gateway-->>User: 202 Accepted {job_id}
    Gateway->>Queue: Dispatch ProcessScreeningJob(job_id)

    activate Queue
    Queue->>Repurp: POST /api/v1/screen {disease_target}
    activate Repurp
    Note over Repurp: Fetch UniProt Sequence -> Encode CNN<br/>Encode Drug Library -> Run DeepPurpose MPNN
    Repurp-->>Queue: Return Top Ranked Candidates (SMILES + Affinity)
    deactivate Repurp

    Queue->>ADMET: POST /predict {SMILES_list}
    activate ADMET
    Note over ADMET: Convert SMILES to Graphs<br/>Run 5-Task MPNN Neural Ensemble
    ADMET-->>Queue: Return ADMET Risk Metrics & Scores
    deactivate ADMET

    Queue->>RAG: POST /search/full-rag {top_SMILES}
    activate RAG
    Note over RAG: FAISS Binary Vector Search -> Find Analogues<br/>Prompt Llama-3.1-8B -> SAR Explanation
    RAG-->>Queue: Return Structural Analogues + LLM Rationale
    deactivate RAG

    Queue->>DB: Save Final Synthesized Results & Set Status: COMPLETED
    deactivate Queue

    User->>Gateway: GET /api/screening/jobs/{job_id}
    Gateway-->>User: 200 OK {Complete Lead Compound Report}
```

---

## 8. RAG Pipeline

AILIXIR uses a domain-adapted Retrieval-Augmented Generation (RAG) architecture tailored for molecular structures rather than textual paragraphs.

```mermaid
flowchart TD
    subgraph INGESTION [1. Ingestion & Indexing Pipeline]
        RawSMILES[Raw Chemical Library / PubChem CSV]
        RDKitParse[RDKit SMILES Sanitization & Parsing]
        FP_Gen[Multi-Fingerprint Generator\nMorgan 2048-bit + MACCS + AtomPairs]
        BitPack[Bit-Packing Transformation to uint8]
        FAISS_Build[FAISS IndexBinaryFlat Construction]
        FAISS_Build -->|Persist to Volume| IndexDisk[chemical_rag_index.faiss]
    end

    subgraph RETRIEVAL [2. High-Speed Retrieval Phase]
        QuerySMILES[Query SMILES Input]
        QuerySMILES --> RDKitValidate[RDKit Parsing & Validation]
        RDKitValidate --> QueryFP[Generate Query Morgan Fingerprint]
        QueryFP --> FAISS_Search[FAISS Binary Vector Search\nHamming Distance Computation]
        IndexDisk --> FAISS_Search
        FAISS_Search --> TopK[Extract Top-K Closest Analogues]
    end

    subgraph GENERATION [3. Context Augmentation & LLM Reasoning]
        TopK --> PromptEng[Construct Chemist Prompt\nInject Targets, Analogues & Tanimoto Scores]
        PromptEng --> LLM_Inference[Hugging Face Inference API\nLlama-3.1-8B-Instruct]
        LLM_Inference --> StructReport[Generated SAR Explanation & Report]
    end
```

### Deep Dive into RAG Steps

1. **Ingestion & Sanitization**:
   - SMILES strings are parsed via RDKit, removing salts, standardizing tautomers, and assigning canonical SMILES representations.
2. **Fingerprint Featurization**:
   - Converts canonical SMILES into 2048-bit Morgan Fingerprints (radius=2, equivalent to ECFP4) alongside 166-bit MACCS keys.
3. **Binary Vector Indexing**:
   - Standard floating-point vector similarity (Cosine/Euclidean) is computationally inefficient for 2048-bit bit vectors. AILIXIR packs bits into `uint8` byte arrays and uses `FAISS.IndexBinaryFlat` to compute exact Hamming distances directly in hardware registers.
4. **Context-Rich Prompt Engineering**:
   - Retrieved chemical analogues, structural similarity metrics (Tanimoto coefficients derived from Hamming distance via $T = \frac{C}{A + B - C}$), and predicted ADMET profiles are formatted into a domain-restricted prompt context.
5. **LLM Generation**:
   - Llama-3.1-8B-Instruct generates natural language commentary highlighting pharmacophores, potential metabolic liabilities, and bioisosteric substitution suggestions.

---

## 9. Voice Pipeline

AILIXIR incorporates a real-time conversational chemistry interface for hands-free laboratory usage.

```mermaid
flowchart LR
    subgraph BROWSER [Client Browser]
        Mic[Microphone Input]
        AudioWorklet[Audio Worklet PCM Encoder]
        Player[Web Audio API Speaker Playback]
    end

    subgraph WS_GATEWAY [WebSocket & Streaming Engine]
        WS_Server[Chem Agent WebSocket Gateway]
        VAD[Voice Activity Detection / Silero VAD]
    end

    subgraph VOICE_AI [Voice Processing Services]
        STT[Whisper Speech-to-Text]
        AgentCore[LangChain / Chemistry Reasoning Agent]
        TTS[FastSpeech2 / XTTS Text-to-Speech]
    end

    Mic --> AudioWorklet
    AudioWorklet -->|PCM Audio Stream / WS| WS_Server
    WS_Server --> VAD
    VAD -->|Active Speech Chunks| STT
    STT -->|Transcribed Text| AgentCore
    AgentCore -->|RDKit / DB Query / Tool Call| AgentCore
    AgentCore -->|Response Text Stream| TTS
    TTS -->|Audio Chunk Stream| WS_Server
    WS_Server -->|Binary Audio Payload| Player
```

### Voice Execution Sequence
1. **Audio Capture**: Browser streams low-latency raw PCM audio over WebSockets.
2. **VAD Filtering**: Silero Voice Activity Detection filters out ambient laboratory noise, forwarding audio only during detected speech intervals.
3. **STT Transcription**: OpenAI Whisper transcribes incoming chemical names and spoken instructions into text.
4. **Agent Tool Orchestration**: The Chemistry Agent parses intent, using RDKit to resolve spoken common names (e.g., "Aspirin") to canonical SMILES (`O=C(C)Oc1ccccc1C(=O)O`) and querying the internal ADMET engine.
5. **TTS Audio Synthesis**: The response streams back as binary audio via XTTS/FastSpeech2 and plays instantly through the browser's Web Audio API.

---

## 10. Validation Engine

To prevent hallucinatory AI predictions in chemical safety assessments, AILIXIR employs a deterministic, rule-based computational validation layer before deep learning inference.

```
                  +-----------------------------------+
                  |      Raw SMILES Input String      |
                  +-----------------------------------+
                                    |
                                    v
                  +-----------------------------------+
                  |      RDKit Molecule Parsing       |
                  +-----------------------------------+
                                    |
                 /                                     \
        (Valid SMILES)                           (Invalid SMILES)
               |                                         |
               v                                         v
+-----------------------------+           +-----------------------------+
| Deterministic Validation    |           | Immediate Error Signal      |
| - Valence & Charge Checking |           | - HTTP 422 Unprocessable    |
| - Salt Strip & Canonicalize |           | - Exact Structural Error Msg|
| - Bredt's Rule & Ring Strain|           +-----------------------------+
+-----------------------------+
               |
               v
+-----------------------------+
| Deep Learning Model Infer   |
| - MPNN ADMET Prediction     |
| - DeepPurpose DTI Scoring   |
+-----------------------------+
               |
               v
+-----------------------------+
| Confidence Verification     |
| - Epistemic Variance Check  |
| - Out-of-Domain Detection   |
+-----------------------------+
```

### Deterministic Checks & Verification Rules

1. **Chemical Valence & Sanity Verification**:
   - Uses RDKit's explicit valence checking. Hypervalent carbons, illegal nitrogen bonds, or invalid aromatic rings trigger immediate validation failures prior to tensor featurization.
2. **Structural Canonicalization & Salt Removal**:
   - Removes counter-ions (e.g., $Na^+$, $Cl^-$) and normalizes charge states to ensure identical molecules yield identical vector representations.
3. **Substructure Hazard Filtering (PAINS & Brenk Filters)**:
   - Filters out Pan-Assay Interference Compounds (PAINS) and reactive functional groups (e.g., acyl halides, peroxides) using predefined SMARTS pattern matching.
4. **Model Confidence & Epistemic Uncertainty Estimation**:
   - ADMET models output variance bounds calculated across neural ensemble predictions. Predictions exceeding pre-configured variance thresholds are flagged with a low-confidence warning badge.

---

## 11. Technology Stack

AILIXIR deliberately avoids monolithic designs, employing specialized technologies selected for performance, reliability, and computational efficiency.

| Technology | Selected Tool | Alternative Evaluated | Technical Rationale for Selection |
|---|---|---|---|
| **API Gateway Core** | **Laravel 12 (PHP 8.3)** | Node.js / Express | Native support for robust queue workers, database migrations, Sanctum auth, Cashier Stripe integration, and structured enterprise architecture. |
| **Microservice Framework**| **FastAPI (Python 3.10+)** | Flask / Django | Asynchronous ASGI performance, automated OpenAPI generation, native Pydantic validation, and direct integration with PyTorch tensors. |
| **Cheminformatics Core** | **RDKit** | OpenBabel / CDDK | Industry standard for molecular sanitization, fingerprint generation, SMARTS matching, and 2D/3D structure depiction. |
| **Deep Learning Framework**| **PyTorch 2.0+** | TensorFlow | Dynamic computation graphs necessary for variable-size Message Passing Neural Networks (MPNN) and molecular graph architectures. |
| **Vector Similarity Index**| **FAISS (Binary)** | Milvus / Qdrant | Unmatched C++ hardware-accelerated binary Hamming distance calculation for bit-packed 2048-bit fingerprints. |
| **Molecular Docking Engine**| **AutoDock-GPU / Vina** | Glide (Schrödinger) | Open-source CUDA-accelerated docking engine allowing scalable parallel binding energy evaluations inside containerized environments. |
| **Primary Relational DB** | **MariaDB / SQLite** | PostgreSQL | Lightweight footprint, instant local development using SQLite, and seamless transition to production MariaDB setups. |

---

## 12. Project Structure

```
ailixir-backend/
├── app/                                  # Laravel Orchestration Core
│   ├── Http/Controllers/Api/             # API Endpoints (ADMET, Docking, Screening)
│   ├── Jobs/                             # Asynchronous Background Queue Jobs
│   ├── Models/                           # Eloquent Database Models
│   ├── Services/                         # Microservice Guzzle HTTP Clients
│   └── Traits/                           # Shared Response & Validation Traits
├── ai_apps/                              # Dedicated Python AI Microservices
│   ├── ADMIT/admet_inference/            # 5-Task ADMET MPNN Inference Engine
│   │   ├── app/                          # FastAPI Service Logic
│   │   ├── models/                       # Pre-trained PyTorch Checkpoints
│   │   └── Dockerfile                    # Containerization Spec (Port 7860 / 8000 mapped)
│   ├── Drug Reporposing/                 # Target-Based Virtual Screening Pipeline
│   │   ├── app/                          # DeepPurpose Pipeline & API Adapters
│   │   ├── docker/Dockerfile             # Full Docker Build (DeepPurpose + TDC)
│   │   ├── Dockerfile.hf                 # Lightweight HF Spaces Build (Mock-ready)
│   │   └── TDC_data/                     # Therapeutic Data Commons Local Caching
│   ├── chemical-rag-system/              # Vector Search & LLM SAR Engine
│   │   ├── chemical-rag-system/
│   │   │   ├── Dockerfile                # Containerization Spec (Port 7860 / API_PORT)
│   │   │   └── app/                      # FastAPI Vector Server
│   ├── chem_agent_api/                   # Conversational Chemistry Agent
│   │   └── Dockerfile                    # Containerization Spec (Port 7860)
│   ├── generation/                       # REINVENT4 & AutoDock-GPU Generator
│   │   ├── Dockerfile.api                # Docker CUDA Build (AutoDock-GPU CUDA)
│   │   ├── Dockerfile.dp                 # Docker DeepPurpose Affinity Sidecar
│   │   ├── Dockerfile.hf                 # Unified HF Spaces Build (CPU/OpenCL + DP)
│   │   └── start_hf.sh                   # HF Spaces Dual-Process Boot Script
│   └── Protein-Ligand-MD-Simulation/     # GROMACS MD Simulation Orchestrator
├── config/                               # Application Configurations
├── database/                             # Migrations, Seeders, & SQLite DB
├── docker/                               # Environment & Server Setup
│   ├── entrypoint.sh                     # Container Boot Script (RUN_MODE switch)
│   ├── laravel.env                       # Docker Compose Container Env Defaults
│   ├── supervisord.conf                  # Multi-Process Supervisor Spec (HF Mode)
│   └── nginx/                            # Reverse Proxy Server Configs
├── routes/                               # Route Registries (api.php)
├── .env.example                          # Unified Env Template (HF Default + Docker Comments)
├── Dockerfile                            # Multi-Stage Production Laravel Docker Build
├── README_HF.md                          # Hugging Face Space Card Frontmatter
├── docker-compose.yml                    # Main Microservice Compose Stack
├── docker-compose.gpu.yml                # CUDA-Enabled Production GPU Stack
└── composer.json                         # PHP Dependency Manifest
```

---

## 13. Installation Guide

### Prerequisites
- Python 3.10+ (for AI services)

### Local Development (Laravel only)

```bash
# Clone and enter the repository
git clone <repo-url> && cd ailixir-backend

# Install PHP dependencies
composer install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
touch database/ailixir.sqlite
php artisan migrate

# Frontend assets
npm install && npm run build

# Start development servers
composer dev
```

### Full Stack with Docker

```bash
# Build and start all services (uses docker/laravel.env and internal Docker DNS)
docker compose up -d

# Run migrations
docker compose exec laravel php artisan migrate

# Access the API at http://localhost:8080
```

> **Note on `.env.example`:** The unified `.env.example` file at the repository root uses **Hugging Face Spaces (`RUN_MODE=hf`) as the active default**, with Docker Compose and local development alternatives included as commented-out options and line-by-line documentation.

---

## Configuration

### Environment Variables

`.env.example` is the single canonical environment template for both Hugging Face Spaces and Docker/local setups (with HF values enabled by default):

| Variable | Description | Default (HF Mode) | Docker Alternative |
|---|---|---|---|
| `APP_KEY` | Laravel encryption key | auto-generated on first boot | auto-generated / `php artisan key:generate` |
| `APP_ENV` | Application environment | `production` | `production` |
| `APP_DEBUG` | Debug mode | `false` | `false` |
| `APP_URL` | Public base URL | `https://Ailixir-AI-Team-ailixir-api.hf.space` | `http://localhost:8080` |
| `RUN_MODE` | Boot mode (`hf` starts `supervisord`) | `hf` | unset / `docker` |
| `DB_CONNECTION` | Database driver | `sqlite` | `sqlite` |
| `DB_DATABASE` | SQLite file path | `/var/www/html/database/ailixir.sqlite` | `/var/www/html/database/ailixir.sqlite` (or `database/ailixir.sqlite` locally) |
| `QUEUE_CONNECTION` | Queue driver | `database` | `database` |
| `CACHE_STORE` | Cache driver | `database` | `database` |
| `SESSION_DRIVER` | Session driver | `database` | `database` |

### AI Service URLs

| Variable | Service | Default (HF Spaces) | Docker Internal DNS |
|---|---|---|---|
| `CHEMICAL_AI_URL` | Chemical RAG | `https://RottenShadow-ailixir-chemical-rag.hf.space` | `http://chemical-rag:5000` |
| `ADMET_AI_URL` | ADMET Inference | `https://shdwRow-ailixir-admet.hf.space` | `http://admet:8000` |
| `AI_ADMET_SERVICE_URL` | ADMET (alt) | `https://shdwRow-ailixir-admet.hf.space` | `http://admet:8000` |
| `DRUG_REPURPOSING_URL` | Drug Repurposing | `https://RottenShadow-ailixir-drug-repurposing.hf.space` | `http://drug-repurposing:8000` |
| `AI_SERVICE_URL` | Drug Repurposing (alt) | `https://RottenShadow-ailixir-drug-repurposing.hf.space` | `http://drug-repurposing:8000` |
| `GENERATION_SERVICE_URL` | Molecular Generation | `https://shdwRow-ailixir-generation.hf.space` | `http://generation:8000` |
| `CHEMISTRY_API_BASE_URL` | Chemistry Agent | `https://shdwrow-ailixir-chat-bot.hf.space/` | external / configurable |
| `MD_SIMULATION_URL` | MD Simulation | `http://localhost:5005` | `http://protein-ligand-md:5005` |

### External Integrations

| Variable | Service | Required |
|---|---|---|
| `CLOUDINARY_URL` | Cloudinary file storage | For file uploads (dummy values allowed) |
| `CLOUDINARY_CLOUD_NAME` | Cloudinary cloud name | For file uploads |
| `CLOUDINARY_API_KEY` | Cloudinary API key | For file uploads |
| `CLOUDINARY_API_SECRET` | Cloudinary secret | For file uploads |
| `MAIL_MAILER` / `MAIL_*` | SMTP mail transport | For OTP verification emails (`log` for dry runs) |
| `HF_TOKEN` | HuggingFace API token | For LLM explanations |
| `STRIPE_KEY` | Stripe publishable key | For subscriptions |
| `STRIPE_SECRET` | Stripe secret key | For subscriptions |

---

## Running the Project

### Development

```bash
# Laravel + Queue + Vite concurrently
composer dev
```

### Production (Docker)

```bash
docker compose up -d --build
```

### With GPU Acceleration

```bash
docker compose -f docker-compose.yml -f docker-compose.gpu.yml up -d
```

### Hugging Face Spaces

The project includes Supervisor configuration for running on Hugging Face Spaces. Set `RUN_MODE=hf` (active by default in `.env.example`), or deploy automatically via `.github/workflows/sync-hf-all.yml`.

---

## 14. Docker Build vs. Hugging Face Spaces Build

AILIXIR supports two distinct deployment topologies using the same codebase: a **full local/cloud Docker Compose stack** (`docker-compose.yml`) and a **distributed Hugging Face Spaces deployment** (`.github/workflows/sync-hf-all.yml`).

### Architecture & Topology Comparison

```mermaid
flowchart TB
    subgraph DOCKER_BUILD [Docker Compose Build - Single Host / Bridge Network]
        direction LR
        D_Web[laravel :8080->8000] -->|DB Queue| D_Queue[queue container]
        D_Web -->|http://admet:8000| D_ADMET[admet :8002->8000]
        D_Web -->|http://drug-repurposing:8000| D_DR[drug-repurposing :8001->8000]
        D_Web -->|http://chemical-rag:5000| D_RAG[chemical-rag :5000->5000]
        D_Web -->|http://generation:8000| D_Gen[generation CUDA :8005->8000]
        D_Gen -->|http://generation-deeppurpose:8001| D_DP[generation-deeppurpose :8006->8001]
    end

    subgraph HF_BUILD [Hugging Face Spaces Build - 6 Independent Spaces]
        direction LR
        HF_Web["Ailixir-AI-Team/ailixir-api\n(supervisord: artisan:7860 + queue)"]
        HF_Web -->|HTTPS :443| HF_ADMET["shdwRow/ailixir-admet\n(:7860)"]
        HF_Web -->|HTTPS :443| HF_DR["RottenShadow/ailixir-drug-repurposing\n(:7860, Mock-Ready)"]
        HF_Web -->|HTTPS :443| HF_RAG["RottenShadow/ailixir-chemical-rag\n(:7860)"]
        HF_Web -->|HTTPS :443| HF_Gen["shdwRow/ailixir-generation\n(:7860 OpenCL + 127.0.0.1:8001 DP)"]
        HF_Web -->|HTTPS :443| HF_Chat["shdwRow/ailixir-chat-bot\n(:7860)"]
    end
```

### Side-by-Side Comparison

| Dimension | Docker Compose Build (`docker-compose.yml`) | Hugging Face Spaces Build (`sync-hf-all.yml`) |
|---|---|---|
| **Deployment Topology** | 7 containers on one host connected via the `ailixir` Docker bridge network | 6 standalone HF Spaces distributed across 3 accounts (`Ailixir-AI-Team`, `RottenShadow`, `shdwRow`) |
| **Inter-Service Networking** | Private Docker internal DNS (`http://admet:8000`, `http://chemical-rag:5000`, etc.) | Public HTTPS Space endpoints (`https://<owner>-<space>.hf.space`) |
| **Container Ports** | Service-specific (`8000`, `8001`, `5000`) mapped to host ports (`8080`, `8001`–`8006`, `5000`) | Standardized on port `7860` across every Space |
| **Laravel Process Model** | Split into two containers: `laravel` (`php artisan serve --port=8000`) and `queue` (`php artisan queue:work`) | Single container triggered by `RUN_MODE=hf` in `docker/entrypoint.sh`, running `supervisord` (`docker/supervisord.conf`) to manage both `artisan serve --port=7860` and `queue:work` |
| **Environment Source** | `docker/laravel.env` + inline `environment:` overrides in `docker-compose.yml` | `.env.example` defaults (`RUN_MODE=hf`) / HF Space Secrets + `.env` generated during `.github/workflows/sync-hf-all.yml` |
| **Model & Binary Storage** | Git LFS checkout + local bind mounts/volumes (`./ai_apps/generation/bundle`, `chemical-rag-data`, `laravel-storage`) | Hugging Face Hub dataset download (`shdwRow/ailixir-generation-models`) + Git LFS with **HF Xet** (`git-xet`) for `.ckpt` files |
| **Hardware & Acceleration** | Supports NVIDIA CUDA GPU passthrough via `docker-compose.gpu.yml` (up to 8 GB per service) | CPU-only free tier (`2 vCPU, 16 GB RAM`); Spaces sleep after 15 minutes of inactivity |
| **Database Persistence** | Persisted in container/volumes across restarts (`RUN_MIGRATIONS=false` on `queue` container) | Live `database/ailixir.sqlite` preserved across code syncs via `--exclude=database/ailixir.sqlite`, but resets on full Space rebuild |

### Per-Service Dockerfile Differences

1. **Laravel Backend API (`Dockerfile` + `docker/entrypoint.sh`)**
   - Uses the exact same 3-stage `Dockerfile` (Composer 2.8 → Node 22 Vite build → PHP 8.3 CLI + Miniconda Python 3.10 + AutoDock Vina 1.2.5 + RDKit + Open Babel + Supervisor) and exposes both `8000` and `7860`.
   - **Docker Build**: `RUN_MODE` is unset, so `entrypoint.sh` executes `CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]`, while a second container (`queue`) runs `php artisan queue:work` with `RUN_MIGRATIONS=false`.
   - **HF Build**: `RUN_MODE=hf` causes `entrypoint.sh` to hand off to `exec supervisord -c /etc/supervisor/conf.d/supervisord.conf`, running the web server on port `7860` and the queue worker together in one container.

2. **Molecular Generation (`ai_apps/generation/`)**
   - **Docker Build (`Dockerfile.api` + `Dockerfile.dp`)**: Split across two dedicated containers:
     - `generation` (`Dockerfile.api`) builds on `nvidia/cuda:12.4.1-devel-ubuntu22.04` and compiles **AutoDock-GPU for CUDA** (`make DEVICE=CUDA NUMWI=64`, producing `/opt/AutoDock-GPU/bin/autodock_gpu_64wi`) on port `8000`.
     - `generation-deeppurpose` (`Dockerfile.dp`) runs in a separate `condaforge/miniforge3` Python 3.8 container serving DeepPurpose affinity prediction on port `8001` (`http://generation-deeppurpose:8001/reinvent_predict`).
   - **HF Build (`Dockerfile.hf` + `start_hf.sh`)**: Consolidated into a single `condaforge/miniforge3` container containing both Conda environments (`ailixir` Python 3.11 for REINVENT4 + `dp` Python 3.8 for DeepPurpose CPU). AutoDock-GPU is compiled in **CPU/OpenCL mode** (`make DEVICE=CPU NUMWI=1` with `pocl-opencl-icd`, producing `/opt/AutoDock-GPU/bin/autodock_cpu_1wi`). On boot, `start_hf.sh` launches DeepPurpose in the background on `127.0.0.1:8001`, waits for `/health`, and starts the main Generation FastAPI app on `0.0.0.0:7860`.

3. **Drug Repurposing (`ai_apps/Drug Reporposing/`)**
   - **Docker Build (`docker/Dockerfile`)**: Multi-stage `python:3.10-slim` build that compiles and installs full `DeepPurpose`, `TDC`, `descriptastorus`, and `torch`, downloading pretrained `MPNN_CNN_BindingDB` weights into `/app/save_folder` during image build (`USE_MOCK_MODEL=False`, `USE_MOCK_DRUGS=False`, port `8000`).
   - **HF Build (`Dockerfile.hf`)**: Renamed to `Dockerfile` by the sync workflow. Uses a lightweight single-stage `python:3.10-slim` image that omits heavy `torch`, `DeepPurpose`, and `TDC` installs to stay within HF Spaces build limits, defaulting to `USE_MOCK_MODEL=True` and `USE_MOCK_DRUGS=True` on port `7860`.

4. **ADMET (`ai_apps/ADMIT/admet_inference/Dockerfile`), Chemical RAG (`ai_apps/chemical-rag-system/chemical-rag-system/Dockerfile`), & Chemistry Agent (`ai_apps/chem_agent_api/Dockerfile`)**
   - **Shared CPU Images**: Each uses a single Dockerfile built for port `7860`.
   - **Docker Compose & HF Differences**: In `docker-compose.yml`, `chemical-rag` overrides `API_PORT=5000` (mapped to `5000:5000`), while `admet` maps `8002:8000` in Compose (`ai_apps/ADMIT/admet_inference/Dockerfile` exposes `7860` for HF Spaces, where `.ckpt` files are synced via `git-xet` and `git lfs`).

> For step-by-step operational details, see [DOCKER.md](DOCKER.md), [HF_SPACES_GUIDE.md](HF_SPACES_GUIDE.md), and [HUGGINGFACE_FLOW.md](HUGGINGFACE_FLOW.md).

---

## API Overview

### Authentication

All endpoints except `register`, `login`, and health checks require a Bearer token obtained via `POST /user/login` or `POST /user/register`. Tokens are issued as Laravel Sanctum API tokens.

### Endpoints

| Group | Endpoints | Auth |
|---|---|---|
| **Auth** | `POST /user/register`, `POST /user/login`, `POST /user/logout` | Partial |
| **Email Verification** | `POST /user/verify-email`, `POST /user/resend-verification` | No |
| **Password Reset** | `POST /user/forgot-password`, `POST /user/reset-password` | No |
| **Google OAuth** | `POST /user/auth/google` | No |
| **Profile** | `GET /user/profile`, `POST /user/update-profile` | Sanctum |
| **Drug Repurposing** | `POST /drug-repurposing/targets`, `POST /drug-repurposing/screen`, `GET /drug-repurposing/screen/{id}`, `GET /drug-repurposing/targets/{id}`, history endpoints | Sanctum |
| **Chemical Search** | `POST /chemical-search`, `POST /chemical-search/full-rag` | Sanctum |
| **Chemistry Agent** | `POST /chemistry/chat`, `POST /chemistry/thread`, `POST /chemistry/analyze/smiles`, `POST /chemistry/analyze/compare`, `POST /chemistry/analyze/docking`, `POST /chemistry/csv/upload` | Sanctum |
| **ADMET** | `POST /admet/predict` | Sanctum |
| **Molecular Generation** | `POST /ai/generation/run`, `GET /ai/generation/history`, `GET /ai/generation/status/{job_id}`, `GET /ai/generation/jobs/{job_id}/results` | Sanctum |
| **Molecular Docking** | `POST /docking/submit`, `GET /docking/history`, `GET /docking/{id}`, `GET /docking/download/{id}` | Sanctum |
| **MD Simulation** | `POST /md-simulation/process`, `GET /md-simulation/history`, `GET /md-simulation/status/{jobId}`, `POST /md-simulation/analyze/{jobId}` | Sanctum |
| **SMILES Conversion** | `POST /convert-smiles/convert`, `GET /convert-smiles/history`, `GET /convert-smiles/download/{id}` | Sanctum |
| **News** | `GET /news`, `GET /news/refresh`, `GET /news/categories`, `POST /news/{id}/save`, `GET /news/saved` | Sanctum |
| **Subscriptions** | `GET /plans`, `POST /subscription/checkout`, `GET /subscription/status`, `POST /subscription/swap`, `POST /subscription/cancel`, `POST /subscription/resume`, `GET /subscription/invoices` | Sanctum |
| **AI Integration** | `GET /ai-services/health`, `POST /ai-services/test/admet`, `POST /ai-services/test/chemical-search`, `GET /ai-services/test/drug-repurposing` | When enabled |
| **Scientist / Awards** | `GET /scientists`, `GET /scientists/{id}`, `GET /awards`, `GET /awards/{id}` | Public |

### Request Flow Example

```
POST /user/login  →  { email, password }  →  Bearer token
GET  /user/profile  →  Authorization: Bearer <token>  →  user data
POST /drug-repurposing/screen  →  { disease_name, top_n_targets }  →  job_id
GET  /drug-repurposing/screen/{id}  →  { status, output }
```

---

## AI Pipeline

### Drug Repurposing Pipeline

```
Input: disease_name + optional parameters
  │
  ├── 1. Disease Target Mapping (OpenTargets API)
  │     Identifies protein targets associated with the disease
  │
  ├── 2. Protein Sequence Retrieval (UniProt API)
  │     Fetches amino acid sequences for identified targets
  │
  ├── 3. Drug Library Loading (TDC / fallback)
  │     Loads FDA-approved drugs with SMILES notation
  │
  ├── 4. AI Virtual Screening (DeepPurpose MPNN-CNN)
  │     Predicts drug-target binding affinities
  │
  └── 5. Result Processing
        Ranks candidates, marks known vs. novel treatments
  │
Output: ranked list of drug-target pairs with affinity scores
```

### ADMET Prediction Pipeline

```
Input: SMILES string
  │
  ├── 1. SMILES Validation (RDKit)
  │     Validates and canonicalizes the input
  │
  ├── 2. Molecular Featurization
  │     Converts to molecular graph (SimpleMoleculeMolGraph)
  │
  ├── 3. Parallel MPNN Inference (5 models)
  │     Absorption · Distribution · Metabolism · Excretion · Toxicity
  │
  └── 4. Aggregation
  │
Output: { Absorption: 0.75, Distribution: 0.82, Metabolism: 0.68, ... }
```

### Chemical RAG Pipeline

```
Input: SMILES + top_k
  │
  ├── 1. Morgan Fingerprint (2048-bit binary)
  │     Converts SMILES to binary fingerprint
  │
  ├── 2. FAISS Binary Retrieval (IndexBinaryFlat)
  │     Hamming-distance search, top 200 candidates
  │
  ├── 3. Multi-Fingerprint Fusion
  │     Morgan · MACCS · Atom Pairs · Topological Torsions (weighted)
  │
  ├── 4. Chemical-Aware Reranking
  │     Aromaticity bonus · Ring-system bonus · Charge penalty · Fragment penalty
  │
  ├── 5. Z-Score Calibration → Sigmoid Probability [0, 1]
  │
  ├── 6. MMR Diversity Selection (λ=0.6)
  │
  └── 7. [Optional] LLM Explanation (Llama-3.1-8B)
  │
Output: ranked similar compounds with similarity scores and optional explanations
```

### Molecular Generation Pipeline

```
Input: preset (e.g. egfr_generator) + num_molecules
  │
  ├── 1. REINVENT Molecule Generation
  │     Generates novel molecules with desired properties
  │
  ├── 2. RDKit Validation + SA Score Calculation
  │
  ├── 3. [Optional] DeepPurpose Affinity Prediction
  │
  └── 4. [Optional] AutoDock-GPU Docking
  │
Output: SMILES strings, affinity scores, docked poses
```

---

## Database Design

### Models Overview

| Model | Table | Description |
|---|---|---|
| `User` | `users` | Registered users with OTP fields, plan, and Stripe integration |
| `Plan` | `plans` | Subscription plan definitions (free, pro, max) |
| `AiJob` | `ai_jobs` | Molecular generation job tracking (status, stage, files, ligands) |
| `DockingJob` | `docking_jobs` | Docking job records (input_type, SMILES, protein, scores) |
| `ScreeningResult` | `screening_results` | Drug repurposing screening results (input, output, status) |
| `TargetLookup` | `target_lookups` | Disease target lookup records |
| `ChemicalSearchJob` | `chemical_search_jobs` | Chemical similarity search history |
| `ChemicalCompound` | `chemical_compounds` | Individual compound results linked to search jobs |
| `ChemistryThread` | `chemistry_threads` | AI chemistry agent conversation threads |
| `ChemistryAnalysis` | `chemistry_analyses` | Molecule analysis records |
| `ChemistryCsvJob` | `chemistry_csv_jobs` | CSV batch processing jobs |
| `MdSimulationJob` | `md_simulation_jobs` | MD simulation job metadata |
| `Admet` | `admets` | ADMET prediction records |
| `NewsArticle` | `news_articles` | Cached RSS news articles |
| `SavedArticle` | `saved_articles` | User-saved articles |
| `Scientist` / `Award` | `scientists` / `awards` | Public directory data |

### Key Relationships

```mermaid
erDiagram
    User ||--o{ AiJob : has
    User ||--o{ DockingJob : has
    User ||--o{ ScreeningResult : has
    User ||--o{ TargetLookup : has
    User ||--o{ ChemicalSearchJob : has
    User ||--o{ ChemistryThread : has
    User ||--o{ ChemistryAnalysis : has
    User ||--o{ ChemistryCsvJob : has
    User ||--o{ SavedArticle : saves
    User ||--|| Plan : belongs-to
    ChemicalSearchJob ||--o{ ChemicalCompound : contains
    Scientist }o--|| Award : receives
```

---

## Example Workflow

### End-to-End Drug Repurposing Screening

```bash
# 1. Register a new user
curl -X POST http://localhost:8080/user/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Researcher","email":"r@example.com","password":"secret123"}'

# 2. Login to obtain a token
TOKEN=$(curl -s -X POST http://localhost:8080/user/login \
  -H "Content-Type: application/json" \
  -d '{"email":"r@example.com","password":"secret123"}' \
  | jq -r '.data.token')

# 3. Submit a drug repurposing screening job
JOB_ID=$(curl -s -X POST http://localhost:8080/drug-repurposing/screen \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"disease_name":"Type 2 Diabetes","top_n_targets":10}' \
  | jq -r '.data.job_id')

# 4. Poll for results
curl -s -X GET "http://localhost:8080/drug-repurposing/screen/$JOB_ID" \
  -H "Authorization: Bearer $TOKEN" | jq '.data.status'

# 5. Check ADMET properties of a candidate drug
curl -s -X POST http://localhost:8080/admet/predict \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"smiles":"CC(=O)Oc1ccccc1C(=O)O"}' | jq '.data.predictions'
```

---

## Screenshots

> Diagrams and screenshots are located in the `Diagrams/` directory. They include architecture diagrams, sequence diagrams, and state diagrams for the drug repurposing, docking, and authentication workflows.

---

## Performance Notes

### Caching
- AI service URLs are read from config (cached by Laravel)
- Laravel config and route caching enabled in production
- Compiled views and optimized autoloader in Docker build

### Concurrency
- Laravel queue workers process jobs sequentially per worker (horizontal scaling via multiple workers)
- ADMET inference uses `asyncio` for parallel model inference across 5 MPNN models
- Chemical RAG uses FAISS IVF for sub-millisecond binary search
- Chat agent and generation services are synchronous per request

### Scalability
- AI services are stateless and can be horizontally replicated behind a load balancer
- Database-backed queue for job durability
- FAISS index persisted to disk volume for fast reload
- Resource limits configurable per service in `docker-compose.yml`

### Known Bottlenecks
- DeepPurpose model loading requires 4-8 GB RAM per instance
- AutoDock-GPU docking is compute-intensive (minutes per molecule on CPU)
- REINVENT generation coupled with DeepPurpose prediction for filtering
- First-time Chemical RAG startup performs full compound ingestion and index build (~1M+ compounds)

---

## Future Improvements

- **GPU Acceleration** — Full GPU support for AutoDock-GPU, REINVENT, and DeepPurpose across all AI services
- **Distributed Queue** — Replace database queue with Redis for higher throughput
- **Model Registry** — Versioned model storage with A/B testing support
- **Result Caching** — Redis-based caching for identical SMILES queries to ADMET and search endpoints
- **WebSocket Notifications** — Real-time job status updates via Laravel WebSockets
- **Multi-Tenant Organizations** — Team-based collaboration with shared job history
- **Expanded ADMET Models** — Additional endpoints for CYP450 isoform-specific predictions
- **Automated Retraining** — Feedback loop for continuous model improvement from user-validated results

---

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-feature`)
3. Make your changes
4. Run the test suite (`composer test`)
5. Submit a pull request

### Development Guidelines

- Follow PSR-12 coding standards
- Use Laravel Pint for code style (`./vendor/bin/pint`)
- Add Pest tests for new functionality
- Document new API endpoints in the route definitions

---

## License

MIT
