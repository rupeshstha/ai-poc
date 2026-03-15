# LinkedIn Post

---

I'm starting something I've wanted to do for a while.

A public, working series — from the very first line of agentic AI code all the way to an enterprise-grade architecture. No hand-waving. No "here's the theory." Every step is a working proof of concept you can clone, run, and break.

This is Day 1.

---

**Why now?**

I've been building AI features for a while — Python microservices, LangChain pipelines, FastAPI wrappers around OpenAI. The classic stack. It works. But there's a gap I kept running into: most learning resources either stay at the "hello world" prompt level or jump straight into architecture diagrams with no code in between.

I want to fill that gap. Starting from scratch, in public, one POC at a time.

---

**The series: Scratch to Enterprise Agentic Development**

Here's the roadmap I'm working through. Each stage gets its own working codebase, its own repo, and a post explaining what changed and why.

```
Stage 1 — Foundations (this post)
  ↳ What is RAG? How does retrieval actually work?
  ↳ What is an AI agent vs a prompt?
  ↳ What is MCP and why does it matter?
  ↳ Conversation memory from scratch

Stage 2 — Intermediate (coming soon)
  ↳ Multi-agent orchestration
  ↳ Tool use and function calling
  ↳ Production retrieval with Typesense / vector databases
  ↳ Async agent jobs and queues
  ↳ Agent observability and logging

Stage 3 — Advanced (coming soon)
  ↳ Agent-to-agent communication
  ↳ Planner / executor / critic patterns
  ↳ Human-in-the-loop approval flows
  ↳ Fine-tuning and model routing strategies

Stage 4 — Enterprise (coming soon)
  ↳ Multi-tenant agent architecture
  ↳ RBAC over agent capabilities and tools
  ↳ Audit trails, compliance, and PII handling
  ↳ Cost control and rate limiting across providers
  ↳ Enterprise MCP — secure, scoped, monitored
  ↳ Production deployment patterns
```

Advanced and enterprise stages are being actively architected. I'll share them as working demonstrations, not just diagrams — you'll be able to run every stage locally.

---

**Stage 1: What I built**

To kick this off I built a Laravel POC covering the foundations of agentic development. Open source, intentionally transparent — every line is written to be read.

→ **RAG from scratch** — A pure-PHP TF-IDF retrieval implementation so you can see exactly what happens before reaching for a vector database. Tokenization, IDF weighting, cosine similarity — all in one readable service class.

→ **RAG + Laravel AI Agent** — The same retrieval pipeline wired into a proper Agent class. Retrieved documents get injected into system instructions at runtime. The LLM only knows what you give it.

→ **Multi-turn RAG Chat** — A conversational interface where the agent retrieves fresh context per message, maintains full history, and uses its own reasoning to stay on topic — not a hardcoded gate.

→ **MCP Server** — A live Model Context Protocol server with tools, resources, and prompts. Register it once and Claude Desktop, Cursor, or VS Code connects immediately. No glue code.

---

**Why Laravel for this?**

I'll be honest — I expected to hit walls. "PHP isn't the AI language." I've heard it. But working through this convinced me that the framework matters more than the runtime for application-layer AI work.

`RemembersConversations` persists every agent turn to the database automatically. One trait. No Redis, no session management, no custom middleware. Resuming a conversation is `$agent->continue($id, $user)`.

MCP registration is a route. `Mcp::web('handle', ServerClass::class)`. Tool classes have typed properties and a `handle()` method. The protocol negotiation happens for you. I built a full weather server with live geocoding in under an hour.

And because it's all Laravel — Eloquent, queues, policies, Livewire — you bring every pattern you already know. Nothing new to learn except the AI primitives themselves.

---

**The first lesson: stop gatekeeping with code**

The temptation in agentic development is to build elaborate rule-based logic around your LLMs. "If retrieval score is below 0.1, reject the query." I did exactly this at first.

That's backwards.

The LLM is the reasoning layer. It handles follow-up questions, paraphrasing, context from prior turns. A similarity threshold cannot. What you need is a strict, well-crafted system prompt that defines what the model is allowed to know and what to say when it doesn't know something. Let the model decide. Trust the reasoning — just constrain the knowledge.

That's the first real principle of agentic design. Every stage of this series builds on it.

---

**Stage 2 is already in progress.**

If you want to follow along — working code, real architecture decisions, honest writeups about what didn't work — this is the series.

Repo link in the comments. Star it if you want to track progress.

What part of the stack are you most interested in seeing covered? Drop it below — it'll shape how I prioritise the next stages.

#Laravel #PHP #AIEngineering #AgenticAI #RAG #MCP #ModelContextProtocol #LLM #SoftwareEngineering #OpenSource #AgenticDevelopment #BuildInPublic
