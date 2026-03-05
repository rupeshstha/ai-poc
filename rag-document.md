Everything is working. Here's what was built:

---
What was built

Visit /rag-demo (must be logged in). The UI shows the full RAG pipeline in 4 visible steps:

Step 1 — Query: Type any question (e.g. "How do black holes form?", "Why is the ocean salty?")

Step 2 — Retrieval: The 3 most similar documents appear, each with a similarity score bar. On the left panel, matching documents highlight in blue.

Step 3 — Augmented prompt: Collapsible view of exactly what gets sent to the LLM — your question plus the retrieved context chunks injected in.

Step 4 — LLM response: The answer, grounded only in the retrieved context.

---
To directly answer your original question:

No, RAG is not the same as MCP. Your MCP understanding was correct — that's tool calling. RAG is different:

- MCP/tool calling: LLM decides at runtime to call a tool with arguments → gets live data
- RAG: Before the LLM even sees your question, a search finds relevant chunks from a knowledge base → those chunks are injected into the prompt automatically

RAG uses vector similarity (or TF-IDF in this POC) — documents are turned into numbers representing their meaning, then your query is compared mathematically to find the closest match. No
LLM decision-making involved in the retrieval step.
