<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * RAG-enabled agent that accepts retrieved context injected into its system instructions.
 *
 * The retrieval step happens BEFORE this agent is called — the Livewire component
 * performs similarity search, builds a context string from the top-K documents,
 * and passes it here via withContext(). The agent then uses that context as its
 * grounding knowledge when answering the user's question.
 *
 * RemembersConversations automatically persists every turn to agent_conversations
 * and agent_conversation_messages in the database.
 */
#[Provider('openrouter')]
class RagAgent implements Agent, Conversational
{
    use Promptable;
    use RemembersConversations;

    private string $ragContext = '';

    /**
     * Inject the retrieved document context into the agent's system instructions.
     */
    public function withContext(string $context): static
    {
        $this->ragContext = $context;

        return $this;
    }

    /**
     * System instructions — includes the RAG context when provided.
     */
    public function instructions(): Stringable|string
    {
        if ($this->ragContext === '') {
            return 'You are a knowledgeable assistant. Answer questions clearly and concisely.';
        }

        return <<<INSTRUCTIONS
        You are a knowledgeable assistant. Use ONLY the provided context to answer the question.
        If the context does not contain enough information to answer, say so clearly.
        Do not make up information that is not in the context.

        === RETRIEVED CONTEXT (injected by RAG retrieval step) ===
        {$this->ragContext}
        === END CONTEXT ===
        INSTRUCTIONS;
    }
}
