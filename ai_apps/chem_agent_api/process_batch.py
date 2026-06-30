import pandas as pd
from agent import agent
from langchain_core.messages import HumanMessage
import uuid

df = pd.read_csv("compounds.csv")   # columns: name, smiles

results = []
for _, row in df.iterrows():
    thread_id = str(uuid.uuid4())
    config = {"configurable": {"thread_id": thread_id}}
    result = agent.invoke(
        {"messages": [HumanMessage(
            content=f"Classify this drug candidate: {row['smiles']}"
        )]},
        config=config,
    )
    results.append({
        "name":   row["name"],
        "smiles": row["smiles"],
        "analysis": result["messages"][-1].content
    })

pd.DataFrame(results).to_csv("results.csv", index=False)
print("Done — results saved to results.csv")