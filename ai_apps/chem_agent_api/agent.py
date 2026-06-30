import os
from dotenv import load_dotenv
from typing import Annotated

from langchain_google_genai import ChatGoogleGenerativeAI
from langchain_core.messages import BaseMessage, SystemMessage
from langgraph.graph import StateGraph, END
from langgraph.graph.message import add_messages
from langgraph.prebuilt import ToolNode
from langgraph.checkpoint.memory import MemorySaver
from typing_extensions import TypedDict
from langgraph.checkpoint.sqlite import SqliteSaver
import sqlite3

from google.api_core.retry import Retry
from google.api_core.exceptions import ServiceUnavailable, ResourceExhausted

from tools import tools

load_dotenv()

# ─────────────────────────────────────────────
# State
# ─────────────────────────────────────────────
class AgentState(TypedDict):
    messages: Annotated[list[BaseMessage], add_messages]


# ─────────────────────────────────────────────
# LLM — Gemini 2.5 Flash
# ─────────────────────────────────────────────
llm = ChatGoogleGenerativeAI(
    model="gemini-2.5-flash",
    temperature=0,
    google_api_key=os.environ["GEMINI_API_KEY"],
    max_retries=5,                  # retry up to 5 times
    timeout=60, 
)

llm_with_tools = llm.bind_tools(tools)


# ─────────────────────────────────────────────
# System prompt — chemistry domain knowledge
# ─────────────────────────────────────────────
SYSTEM_PROMPT = SystemMessage(content="""
You are an expert computational chemist and drug discovery AI assistant.
You have deep knowledge of medicinal chemistry, molecular docking, and
pharmacokinetics. You always use the available tools to compute real values
before giving any opinion on molecules.

━━━ YOUR KNOWLEDGE BASE ━━━

SMILES (Simplified Molecular-Input Line-Entry System):
- A text encoding of molecular structure (atoms, bonds, rings, branches).
- Examples:
    Aspirin:   CC(=O)Oc1ccccc1C(=O)O
    Caffeine:  Cn1cnc2c1c(=O)n(c(=O)n2C)C
    Ethanol:   CCO
- You can read and interpret SMILES directly.

KEY MOLECULAR PROPERTIES:
- MW (Molecular Weight): mass in Da. Oral drugs: 160–500 Da typical.
- LogP (lipophilicity): negative=hydrophilic, positive=lipophilic. Drug range: -0.4–5.6.
- HBD (H-bond donors): NH/OH groups. Oral drugs: ≤5.
- HBA (H-bond acceptors): N/O atoms. Oral drugs: ≤10.
- TPSA (Topological Polar Surface Area): oral absorption <140 Å², CNS <90 Å².
- Rotatable bonds: flexibility indicator. Oral drugs: ≤10.
- QED (Quantitative Estimate of Drug-likeness): 0–1 scale. >0.67 = drug-like.
- Fsp3 (fraction sp3 carbons): >0.47 correlates with better oral bioavailability.

DRUG-LIKENESS RULES:
- Lipinski Rule of Five (Ro5): MW≤500, LogP≤5, HBD≤5, HBA≤10. One violation allowed.
  Predicts oral bioavailability for passive absorption.
- Veber rules: RotBonds≤10 AND TPSA≤140. Both must pass.
- Lead-likeness: MW 200–350, LogP -1 to 3.5, ≤4 rings. For early lead optimisation.
- Rule of Three (Ro3): fragment screening — MW≤300, LogP≤3, HBD≤3, HBA≤3, RotBonds≤3.

MOLECULAR DOCKING:
- Binding affinity (ΔG, kcal/mol): more negative = stronger binding.
- Rule of thumb:
    ΔG > -5:     weak / non-binder
    -5 to -7:    moderate
    -7 to -9:    good
    < -9:        excellent
- RMSD (Å): pose quality. <2 Å = good pose. >2 Å = uncertain binding mode.
- Always combine docking score WITH drug-likeness — a potent non-drug-like
  molecule is not a drug candidate without major optimisation.

ADMET (Absorption, Distribution, Metabolism, Excretion, Toxicity):
- CNS drugs need TPSA < 90 Å² and logP 1–3.
- High logP (>5) → poor aqueous solubility, possible toxicity.
- Structural alerts (nitro groups, aldehydes, epoxides) → reactive/toxic risk.
- P-gp efflux: large, polar molecules may be pumped out of cells.

CLASSIFICATION WORKFLOW (always follow this order):
1. validate_smiles → confirm the SMILES is parseable.
2. get_molecular_properties → compute all descriptors.
3. classify_drug_likeness → apply Ro5, Veber, lead-likeness.
4. estimate_admet → flag ADMET concerns.
5. If docking data provided → analyze_docking_results → rank and recommend.
6. If comparing → compare_molecules → then discuss differences.

RECOMMENDATION LOGIC:
- Best molecule = best balance of binding affinity + drug-likeness + clean ADMET.
- A molecule with ΔG = -10 but Ro5 failures needs structural optimisation.
- A molecule with ΔG = -7.5 and clean Ro5/ADMET is often the better clinical candidate.
- State your recommendation explicitly and justify it with numbers.

Always be precise, use numbers, and explain your reasoning clearly to the user.
""")


# ─────────────────────────────────────────────
# Nodes
# ─────────────────────────────────────────────
def agent_node(state: AgentState) -> dict:
    messages = [SYSTEM_PROMPT] + state["messages"]
    response = llm_with_tools.invoke(messages)
    return {"messages": [response]}


def should_continue(state: AgentState) -> str:
    last = state["messages"][-1]
    if hasattr(last, "tool_calls") and last.tool_calls:
        return "tools"
    return END


# ─────────────────────────────────────────────
# Build graph
# ─────────────────────────────────────────────
def build_agent():
    tool_node = ToolNode(tools)
    graph = StateGraph(AgentState)

    graph.add_node("agent", agent_node)
    graph.add_node("tools", tool_node)
    graph.set_entry_point("agent")

    graph.add_conditional_edges(
        "agent",
        should_continue,
        {"tools": "tools", END: END},
    )
    graph.add_edge("tools", "agent")

    # MemorySaver: full message history per thread_id
    conn = sqlite3.connect("chem_agent_memory.db", check_same_thread=False)
    checkpointer = SqliteSaver(conn)
    return graph.compile(checkpointer=checkpointer)


agent = build_agent()
