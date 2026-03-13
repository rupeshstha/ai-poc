<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider('openrouter')]
class RagChatAgent implements Agent, Conversational
{
    use Promptable;
    use RemembersConversations;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
        You are a knowledgeable and helpful assistant. Answer questions clearly and concisely.
        You have memory of the entire conversation history and should reference prior messages
        when relevant to provide coherent, contextual responses.
        INSTRUCTIONS;
    }
}
