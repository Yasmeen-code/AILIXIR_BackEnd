# services/agent_service.py
"""
Core agent service layer.

All routers call run_agent() to interact with the LangGraph agent.
Error handling is centralised in handle_llm_error() so every router
gets consistent HTTP error responses without duplicating logic.
"""

from fastapi import HTTPException
from langchain_core.messages import HumanMessage

from agent import agent


# ─────────────────────────────────────────────────────────────────────────────
# Text extraction
# ─────────────────────────────────────────────────────────────────────────────

def extract_text(content) -> str:
    """
    Normalise LLM response content to a plain string.

    Gemini 2.5 returns a list of content blocks:
        [{"type": "text", "text": "..."}, ...]
    Older models / Claude return a plain string.
    This handles both formats transparently.
    """
    if isinstance(content, str):
        return content
    if isinstance(content, list):
        return "\n".join(
            block["text"]
            for block in content
            if isinstance(block, dict) and block.get("type") == "text"
        )
    return str(content)


# ─────────────────────────────────────────────────────────────────────────────
# Agent runner
# ─────────────────────────────────────────────────────────────────────────────

def run_agent(thread_id: str, message: str) -> str:
    """
    Invoke the LangGraph chemistry agent on a given thread.

    Args:
        thread_id: Unique conversation identifier. Messages on the same
                   thread_id share memory (the agent sees prior turns).
        message:   The user's message or instruction.

    Returns:
        The agent's final reply as a plain string.

    Raises:
        Any exception from the LLM — callers should wrap with handle_llm_error().
    """
    config = {"configurable": {"thread_id": thread_id}}
    result = agent.invoke(
        {"messages": [HumanMessage(content=message)]},
        config=config,
    )
    return extract_text(result["messages"][-1].content)


# ─────────────────────────────────────────────────────────────────────────────
# Error handler
# ─────────────────────────────────────────────────────────────────────────────

def handle_llm_error(e: Exception) -> None:
    """
    Convert known LLM API errors into appropriate FastAPI HTTP exceptions.

    Call this inside an except block in any router that calls run_agent():

        try:
            reply = run_agent(thread_id, message)
        except HTTPException:
            raise                   # already converted, re-raise as-is
        except Exception as e:
            handle_llm_error(e)     # converts to HTTPException

    Handled cases:
        429 / RESOURCE_EXHAUSTED  → HTTP 429  (quota exceeded)
        503 / UNAVAILABLE         → HTTP 503  (LLM service down)
        anything else             → HTTP 500  (unexpected agent error)
    """
    err = str(e)

    if "429" in err or "RESOURCE_EXHAUSTED" in err:
        raise HTTPException(
            status_code=429,
            detail=(
                "LLM API quota exceeded. "
                "Please wait and retry, or upgrade your API plan."
            ),
        )

    if "503" in err or "UNAVAILABLE" in err:
        raise HTTPException(
            status_code=503,
            detail="LLM service temporarily unavailable. Retry in 30 seconds.",
        )

    raise HTTPException(status_code=500, detail=f"Agent error: {err}")