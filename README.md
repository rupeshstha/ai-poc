# Laravel AI — Agentic Application POC

A proof-of-concept demonstrating how to build **production-grade agentic AI applications** using the Laravel ecosystem. This repo covers Retrieval-Augmented Generation (RAG), the Model Context Protocol (MCP), Laravel AI agents, and multi-turn conversational interfaces — all within a standard Laravel 12 application.

> **Purpose**: This is a learning and demonstration repository. Every layer of the stack is intentionally transparent so you can see exactly how each piece works before abstracting it away in a real product.

---

## What This Repo Covers

| Feature | Description |
|---|---|
| **RAG (bare)** | Manual retrieval → augmented prompt → Prompt Generation for LLM to reason based on provided context |
| **RAG + Agent** | Same pipeline wired into a Laravel AI agent with conversation persistence |
| **RAG Chat** | Multi-turn conversational interface grounded strictly in your knowledge base |
| **MCP Server** | A Model Context Protocol server with tools, resources, and prompts |
| **MCP Explorer** | UI to browse registered MCP servers and copy connection configs |
| **TF-IDF Retrieval** | A from-scratch vector search implementation (production uses Typesense,Elasticsearch etc) |
| **Conversation Memory** | Automatic persistence of every agent turn via `RemembersConversations` |
| **Markdown Rendering** | AI responses rendered as rich markdown in the chat UI |

---

## Tech Stack

- **PHP 8.5** / **Laravel 12**
- **[laravel/ai](https://github.com/laravel/ai)** — Agent API, provider abstraction, conversation persistence
- **[laravel/mcp](https://github.com/laravel/mcp)** — MCP server framework
- **[laravel/fortify](https://github.com/laravel/fortify)** — Headless authentication (login, 2FA, password reset)
- **[livewire/livewire](https://livewire.laravel.com/)** v4 — Reactive server-driven UI
- **[livewire/flux](https://fluxui.dev/)** v2 — Flux UI component library
- **[spatie/laravel-markdown](https://github.com/spatie/laravel-markdown)** — Markdown rendering
- **[pestphp/pest](https://pestphp.com/)** v4 — Testing
- **Tailwind CSS** v4
- **OpenRouter** — LLM provider (free tier used throughout)
- **Open-Meteo** — Free weather API (no key required)

---

## Pages & Routes

| Route | Page | What It Demonstrates |
|---|---|---|
| `/rag-demo` | RAG Demo | Raw RAG pipeline: retrieve → augment → generate |
| `/rag-ai` | RAG + Agent | Pipeline wired into a Laravel AI agent with visual step-by-step breakdown |
| `/ai-chat` | AI Chat | Multi-turn chat grounded in `rag_documents`, LLM enforces topic scope |
| `/mcp-explorer` | MCP Explorer | Browse registered MCP servers, inspect tools/resources/prompts, copy Claude/Cursor/VS Code configs |

---

## Prerequisites

- PHP >= 8.2
- Composer
- Node.js + npm
- A database (SQLite works fine for local development)
- An [OpenRouter](https://openrouter.ai/) API key (free tier is sufficient)

---

## Getting Started

### 1. Clone the repo

```bash
git clone https://github.com/your-username/laravel-ai-poc.git
cd laravel-ai-poc
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and configure:

```env
# Database (SQLite is simplest for local dev)
DB_CONNECTION=sqlite

# OpenRouter — get a free key at openrouter.ai
OPENROUTER_API_KEY=sk-or-...

# App URL (used as HTTP-Referer header for OpenRouter)
APP_URL=http://localhost:8000
```

Also confirm `config/services.php` has:

```php
'openrouter' => [
    'api_key' => env('OPENROUTER_API_KEY'),
],
```

### 4. Database setup

```bash
php artisan migrate --seed
// OR
php artisan db:seed --class=RagDocumentSeeder
```

The seeder inserts 10 sample documents on topics like climate, physics, biology, and technology, and pre-computes their TF-IDF vectors.

### 5. Build assets and run

```bash
npm run build
php artisan serve
```

Or use the full dev stack (server + queue + Vite hot reload):

```bash
composer run dev
```

Visit `http://localhost:8000`, register an account, and start exploring.

---

## Architecture Deep Dive

### What Is Agentic Development?

An **agent** is an LLM that can reason, use tools, remember context, and take actions across multiple steps — rather than just answering a single isolated prompt. Agentic development means structuring your application so that:

1. The LLM has **tools** it can invoke (MCP tools, database queries, API calls)
2. It has **memory** (conversation history, retrieved documents)
3. It **reasons** across multiple turns rather than treating each message independently
4. You **constrain** what it knows and can do (RAG grounding, strict system prompts)

Laravel's `laravel/ai` package provides the scaffolding: Agent classes, conversation persistence, provider routing, and tool registration.

---

### RAG — Retrieval-Augmented Generation

RAG solves the core limitation of LLMs: they only know what they were trained on. By retrieving relevant documents at query time and injecting them into the prompt, you ground the model in your own data without fine-tuning.

```
User Query
    │
    ▼
┌─────────────────────────┐
│   TF-IDF Retrieval      │  ← Finds the top-K most relevant documents
│   RagService::retrieve()│     from the rag_documents table
└─────────────────────────┘
    │  top-3 docs + similarity scores
    ▼
┌─────────────────────────┐
│   Context Injection     │  ← Documents formatted and injected
│   RagAgent::instruc...  │     into the agent's system instructions
└─────────────────────────┘
    │  system prompt + conversation history + user question
    ▼
┌─────────────────────────┐
│   LLM via OpenRouter    │  ← Generates an answer using ONLY
│                         │     the injected knowledge
└─────────────────────────┘
    │
    ▼
┌─────────────────────────┐
│   Conversation Store    │  ← RemembersConversations saves every
│   agent_conv_messages   │     turn to the database automatically
└─────────────────────────┘
```

This repo demonstrates RAG at **three levels of abstraction**:

#### Level 1 — Bare RAG (`/rag-demo`)

No framework. Just code. See `RagDemo.php` + `RagService.php`:

```php
// 1. Retrieve relevant documents
$retrieved = $ragService->retrieve($query, topK: 3);

// 2. Build an augmented prompt
$prompt = $ragService->buildAugmentedPrompt($query, $retrieved);

// 3. Call the LLM directly via HTTP
$response = $ragService->generate($prompt);
```

This is the most educational view — every step is visible, including the raw HTTP call to OpenRouter.

#### Level 2 — RAG + Agent (`/rag-ai`)

Same retrieval, but generation goes through a proper `Agent` class. See `RagAi.php` + `RagAgent.php`:

```php
// Retrieve
$retrieved = $ragService->retrieve($query, topK: 3);

// Build context string
$context = $retrieved->map(fn ($doc, $i) =>
    '[Document '.($i + 1).': '.$doc['title']."]\n".$doc['content']
)->implode("\n\n---\n\n");

// Inject into agent + call
$agent = RagAgent::make()->withContext($context)->setQuery($query);
$agent->forUser(Auth::user());
$response = $agent->prompt($query, provider: Lab::OpenRouter, model: 'openrouter/free');
```

The UI visualises every step — retrieved docs with similarity scores, the full system prompt with injected context, and the LLM response.

#### Level 3 — RAG Chat (`/ai-chat`)

Multi-turn, persistent, and strictly topic-scoped. See `AiChat.php`:

- Every message triggers fresh retrieval against `rag_documents`
- The LLM receives the best available context for each turn
- `RemembersConversations` stores the full history automatically
- The agent's instructions make the LLM the gatekeeper — it reasons about whether the question is in scope and refuses off-topic requests rather than hallucinating an answer

---

### How TF-IDF Retrieval Works

`RagService` implements a from-scratch TF-IDF vector search. In production you would swap this for Typesense, Elasticsearch, or a purpose-built vector database — but the logic is identical.

**Indexing** (run once via seeder):

```
For each document:
    1. Tokenize: lowercase → strip punctuation → remove stop words
    2. Compute Term Frequency (TF):  count(term) / total_terms
    3. Compute Inverse Doc Freq (IDF): log(total_docs / docs_containing_term)
    4. TF-IDF vector = TF × IDF  →  stored as JSON in rag_documents.tfidf_vector
```

**Retrieval** (at query time):

```
1. Tokenize the query using the same pipeline
2. Build a TF-IDF vector for the query
3. Compute cosine similarity between the query vector and every document vector
4. Return top-K documents sorted by similarity score (score > 0 only)
```

**Cosine Similarity**:

```
similarity = dot_product(query, doc) / (|query| × |doc|)
```

Range: `1.0` = identical vectors, `0.0` = completely unrelated.

---

### Laravel AI Agents

An agent in `laravel/ai` is a class implementing `Agent` (and optionally `Conversational`). It defines its own system instructions and is decorated with provider/model attributes:

```php
#[Provider('openrouter')]
#[Model('openrouter/free')]
class RagAgent implements Agent, Conversational
{
    use Promptable;
    use RemembersConversations;

    private string $ragContext = '';

    public function withContext(string $context): static
    {
        $this->ragContext = $context;
        return $this;
    }

    public function instructions(): string
    {
        return <<<INSTRUCTIONS
        You are a focused assistant.
        Use ONLY the knowledge below — never draw on general training knowledge.
        ...

        === KNOWLEDGE ===
        {$this->ragContext}
        === END KNOWLEDGE ===
        INSTRUCTIONS;
    }
}
```

**Key concepts demonstrated:**

| Concept | How It Is Used |
|---|---|
| `Promptable` trait | Provides `make()` factory and `prompt()` method |
| `RemembersConversations` | Auto-persists every turn to `agent_conversations` + `agent_conversation_messages` |
| `forUser($user)` | Starts a new conversation scoped to a user |
| `continue($id, $user)` | Resumes an existing conversation with full history |
| `currentConversation()` | Returns the UUID of the just-created conversation |
| System instructions | Define behaviour, scope, tone — injected as system role on every call |
| Context injection | Retrieved documents injected into instructions at runtime per message |

---

### MCP — Model Context Protocol

MCP is an open protocol that lets AI clients (Claude Desktop, Cursor, VS Code Copilot) connect to your application and use its tools, read its resources, and apply its prompts — without any custom integration code on either side.

`laravel/mcp` makes it trivial to expose your Laravel app as an MCP server.

#### Registering a Server

```php
// routes/ai.php
Mcp::web('mcp/weather', WeatherServer::class);
```

#### Defining a Server

```php
class WeatherServer extends McpServer
{
    protected string $name = 'Weather Server';
    protected string $version = '0.0.1';

    protected array $tools     = [CurrentWeatherTool::class];
    protected array $resources = [WeatherGuideLinesResource::class];
    protected array $prompts   = [DescribeWeatherPrompt::class];
}
```

#### Writing a Tool

```php
class CurrentWeatherTool extends Tool
{
    public string $description = 'Get current weather for any location';

    #[Schema(description: 'City or location name')]
    public string $location;

    #[Schema(description: 'Temperature unit', enum: ['celsius', 'fahrenheit'])]
    public string $units = 'celsius';

    public function handle(): string
    {
        // 1. Geocode location via Open-Meteo geocoding API
        // 2. Fetch weather via Open-Meteo forecast API
        // 3. Format and return result
    }
}
```

The three MCP primitives are:

| Primitive | Purpose | Example |
|---|---|---|
| **Tool** | Callable action the AI can invoke | `CurrentWeatherTool` — fetches live weather |
| **Resource** | Read-only data the AI can access | `WeatherGuideLinesResource` — markdown docs |
| **Prompt** | Dynamic prompt template | `DescribeWeatherPrompt` — tone-aware weather description |

Once registered, the **MCP Explorer** page (`/mcp-explorer`) discovers all servers at runtime and generates ready-to-paste connection configs for Claude Desktop, Cursor, and VS Code.

---

### Conversation Persistence

Every agent using `RemembersConversations` writes to two tables automatically:

**`agent_conversations`**

| Column | Description |
|---|---|
| `id` | UUID |
| `user_id` | Owning user |
| `title` | Auto-generated from first message |
| `created_at / updated_at` | Timestamps |

**`agent_conversation_messages`**

| Column | Description |
|---|---|
| `conversation_id` | Foreign key to `agent_conversations` |
| `user_id` | Owning user |
| `agent` | Fully-qualified agent class name |
| `role` | `user` or `assistant` |
| `content` | Message text |

Resuming a conversation:

```php
$agent->continue($conversationId, $user);
```

The full history is reloaded and replayed as conversation history on every call, enabling coherent multi-turn reasoning.

---

## Project Structure

```
app/
├── Ai/
│   └── Agents/
│       ├── RagAgent.php              # Context-injected agent (RAG AI + Chat)
│       └── RagChatAgent.php          # Stateless conversational agent
├── Livewire/
│   ├── AiChat.php                    # Multi-turn RAG-grounded chat
│   ├── RagAi.php                     # RAG + Agent pipeline with step visualisation
│   ├── RagDemo.php                   # Bare RAG demo (no agent framework)
│   ├── McpExplorer.php               # MCP server browser
│   └── Settings/                     # Profile, password, 2FA, appearance
├── Mcp/
│   ├── Servers/WeatherServer.php
│   ├── Tools/CurrentWeatherTool.php
│   ├── Resources/WeatherGuideLinesResource.php
│   └── Prompts/DescribeWeatherPrompt.php
├── Models/
│   └── RagDocument.php               # title + content + tfidf_vector (JSON)
└── Services/
    ├── RagService.php                # TF-IDF indexing, retrieval, and generation
    └── McpDiscoveryService.php       # Discovers registered MCP servers at runtime

database/
├── migrations/
│   └── ..._create_rag_documents_table.php
└── seeders/
    └── RagDocumentSeeder.php         # Seeds 10 sample documents + indexes vectors

resources/views/livewire/
├── rag-demo.blade.php
├── rag-ai.blade.php
├── ai-chat.blade.php
└── mcp-explorer.blade.php

routes/
├── web.php                           # Application routes
└── ai.php                            # MCP server registration
```

---

## Adding Your Own Documents

1. Insert rows into `rag_documents` with a `title` and `content`
2. Re-index to compute TF-IDF vectors:

```bash
php artisan tinker --execute "app(App\Services\RagService::class)->indexDocuments();"
```

3. Your new content is immediately part of the knowledge base on `/ai-chat`

---

## Replacing TF-IDF with a Vector Database

Only `RagService::retrieve()` needs to change. Replace it with your vector store client:

```php
public function retrieve(string $query, int $topK = 3): Collection
{
    // Swap in Typesense, Pinecone, pgvector, Elasticsearch, etc.
    return $this->typesense->search($query, limit: $topK);
}
```

Everything else — agents, conversation persistence, the chat UI — stays exactly the same.

---

## Key Agentic Development Principles Applied

1. **Grounding over hallucination** — The agent only has access to documents in `rag_documents`. It cannot fabricate answers from general knowledge.

2. **LLM as the reasoning layer** — Rather than hardcoding "if no docs found, reject query", the LLM itself reasons about whether the question is in scope. This handles paraphrasing, follow-ups, and edge cases far better than brittle rule-based gates.

3. **Retrieval is not enough** — Retrieval finds candidates. The system prompt determines what the LLM does with them. Tight, explicit instructions are what keep the agent on topic.

4. **Conversation = state across turns** — `RemembersConversations` makes agents stateful. Every prior message is replayed as history so the agent understands follow-up questions in context.

5. **MCP = tool exposure without coupling** — Your tools live in your Laravel codebase. MCP gives any compatible AI client access to them without custom integrations per client.

---

## Running Tests

```bash
php artisan test --compact
```

---

## License

MIT — use it, fork it, build on it.
