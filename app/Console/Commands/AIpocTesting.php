<?php

namespace App\Console\Commands;

use function Laravel\Prompts\spin;
use function Termwind\render;

use App\Ai\Agents\SalesCoach;
use Illuminate\Console\Command;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;

use App\Concerns\HtmlToCliConverter;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Enums\Lab;
use Spatie\LaravelMarkdown\MarkdownRenderer;

class AIpocTesting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ai';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(MarkdownRenderer $markdownRenderer)
    {
        // $aiQuestion = text("Ask a question to the AI:", placeholder: "e.g., How can I optimize my Laravel query performance?");
        // $aiContext = textarea("Provide the context.");
        // $httpResponse = spin(function () use ($aiQuestion, $aiContext) {
        //     return Http::connectTimeout(10000000)->withToken("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")
        //     ->post("https://openrouter.ai/api/v1/chat/completions", [
        //         "model" => "nvidia/nemotron-3-nano-30b-a3b:free",
        //         "messages" => [
        //             [
        //                 "role" => "user",
        //                 "content" => $this->getSystemPrompt() . "\n\n". $this->buildPrompt($aiQuestion, $aiContext),
        //             ]
        //         ]
        //     ])->throw()->json();
        // }, "Asking AI...");
        // $converter = new HtmlToCliConverter($this);
        // $converter->convert($markdownRenderer->toHtml($httpResponse['choices'][0]['message']['content']));
        $user = User::first();
        $checkPreviousConversations = Cache::get("previous_conversations_check", false);
        $agent = SalesCoach::make($user);
        if ($checkPreviousConversations) {
            $agent = $agent->continue($checkPreviousConversations, $user);
        } else {
            $agent = $agent->forUser($user);
        }
        $agent = $agent->prompt(
            'Analyze this sales transcript...',
            provider: Lab::OpenRouter,
            model: 'openrouter/free',
            timeout: 120,
        );
        if ($agent->conversationId && !$checkPreviousConversations) {
            Cache::put("previous_conversations_check", $agent->conversationId, now()->addMinutes(30));
        }
        dd((string) $agent);
        // Lab::OpenRouter;

        return;
    }

    private function getSystemPrompt(): string
    {
        return <<<PROMPT
            You are Rupesh Jr, a master-level in Software Engineering and Business analysis. Your mission: transform any user input into precision-crafted solution from the user given context.

            ## THE 4-D METHODOLOGY

            ### 1. DECONSTRUCT

            - Extract core intent, key entities, and context

            - Identify output requirements and constraints

            - Map what's provided vs. what's missing

            ### 2. DIAGNOSE

            - Audit for clarity gaps and ambiguity

            - Check specificity and completeness

            - Assess structure and complexity needs

            ### 3. DEVELOP

            - Select optimal techniques based on request type:

            - **Creative** → Multi-perspective + tone emphasis

            - **Technical** → Constraint-based + precision focus

            - **Educational** → Few-shot examples + clear structure

            - **Complex** → Chain-of-thought + systematic frameworks

            - Enhance context and implement logical structure

            ### 4. DELIVER

            - Construct optimized prompt

            - Format based on complexity

            - Provide implementation guidance

            ## OPERATING MODES

            **DETAIL MODE:**

            - Gather context with smart defaults

            - Ask 2-3 targeted clarifying questions if needed

            - Provide comprehensive optimization

            **BASIC MODE:**

            - Quick fix primary issues

            - Apply core techniques only

            - Deliver ready-to-use prompt

        PROMPT;
         // CRITICAL RULES:
            // 1. Answer always based on the laravel ecosystem.
            // 2. If you don't know the answer, say "I don't know" instead of making something up.
            // 3. Always provide structured answers with endpoints, methods, parameters, and use cases when applicable.
            // 4. Be concise and to the point. Avoid unnecessary explanations or verbose answers.
    }

    private function buildPrompt(string $question, string $context): string
    {
        return <<<PROMPT
            User Context:
            {$context}

            User Question: {$question}
        PROMPT;
    }
}
