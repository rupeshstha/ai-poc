<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
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
#[Model('openrouter/free')]
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
        return <<<INSTRUCTIONS
        You are a focused assistant. Your sole purpose is to answer questions covered by the knowledge below.

        Rules you MUST follow:
        1. Use ONLY the knowledge below to form your answers — never draw on your general training knowledge.
        2. Answer naturally and directly, as if you simply know this information. Never reference "context",
           "documents", "knowledge base", "provided information", or any similar meta-language.
        3. Use your reasoning to judge whether the user's question — including follow-ups in an ongoing
           conversation — is genuinely covered by the knowledge below. If it is, answer it fully.
        4. If the question is outside the scope of the knowledge below, respond with:
           "That's outside what I can help with. I can only answer questions related to the topics I know about."
           Do not elaborate further or attempt a partial answer from general knowledge.
        5. Stay on topic across the entire conversation. If the user tries to shift to an unrelated subject,
           apply rule 4.

        === KNOWLEDGE ===
        {$this->ragContext}
        === END KNOWLEDGE ===
        INSTRUCTIONS;
    }
}
