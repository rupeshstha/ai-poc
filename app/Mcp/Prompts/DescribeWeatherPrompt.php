<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class DescribeWeatherPrompt extends Prompt
{
    /**
     * The prompt's description.
     */
    protected string $description = <<<'MARKDOWN'
        Generates a system prompt instructing the AI to describe weather conditions in a specified tone.
    MARKDOWN;

    /**
     * Handle the prompt request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'tone' => 'required|string',
        ]);

        $tone = $validated['tone'];

        return Response::text(
            "You are a weather reporter. Describe the current weather conditions in a {$tone} tone. "
            .'Include the temperature, how it feels outside, wind conditions, humidity, and any precipitation. '
            .'Keep the description concise, accurate, and engaging.'
        );
    }

    /**
     * Get the prompt's arguments.
     *
     * @return array<int, \Laravel\Mcp\Server\Prompts\Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'tone',
                description: 'The tone for the weather description (e.g. formal, casual, dramatic, poetic).',
                required: true,
            ),
        ];
    }
}
