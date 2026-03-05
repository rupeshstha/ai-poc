<?php

namespace App\Ai\Tools;

use App\Models\History;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class RetrievePreviousTranscripts implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Retrieves previous transcripts of the conversation for context. Takes a single string argument "value" which is a keyword to search for in the transcripts, and returns any relevant transcripts containing that keyword.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        return History::where('content', 'like', '%' . $request->input('value') . '%')
            ->latest()
            ->limit(5)
            ->get()
            ->pluck('content')
            ->implode("\n---\n");
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'value' => $schema->string()->required(),
        ];
    }
}
