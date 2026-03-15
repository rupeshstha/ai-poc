<?php

namespace App\Services;

use App\Models\RagDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * This is just a demo of RAG service but this is handled from external services like typesense, elastic search etc.
 */
class RagService
{
    /** Stop words to exclude from TF-IDF vectors */
    private const STOP_WORDS = [
        'a', 'an', 'the', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
        'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'shall',
        'should', 'may', 'might', 'must', 'can', 'could', 'to', 'of', 'in',
        'for', 'on', 'with', 'at', 'by', 'from', 'as', 'it', 'its', 'this',
        'that', 'these', 'those', 'and', 'but', 'or', 'so', 'yet', 'both',
        'not', 'no', 'nor', 'if', 'then', 'than', 'also', 'more', 'most',
        'other', 'some', 'such', 'into', 'up', 'out', 'about', 'which',
    ];

    /**
     * Tokenize text into meaningful words.
     *
     * @return array<int, string>
     */
    private function tokenize(string $text): array
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($words, fn ($w) => ! in_array($w, self::STOP_WORDS) && strlen($w) > 2));
    }

    /**
     * Build a TF-IDF vector for a piece of text, given the IDF weights.
     *
     * @param  array<string, float>  $idfWeights
     * @return array<string, float>
     */
    private function buildTfIdfVector(string $text, array $idfWeights): array
    {
        $tokens = $this->tokenize($text);
        $termCount = count($tokens);

        if ($termCount === 0) {
            return [];
        }

        $termFrequencies = array_count_values($tokens);
        $vector = [];

        foreach ($termFrequencies as $term => $count) {
            $tf = $count / $termCount;
            $idf = $idfWeights[$term] ?? log(2); // default IDF for unknown terms
            $vector[$term] = $tf * $idf;
        }

        return $vector;
    }

    /**
     * Compute cosine similarity between two TF-IDF vectors.
     *
     * @param  array<string, float>  $a
     * @param  array<string, float>  $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        $dotProduct = 0.0;
        $magnitudeA = 0.0;
        $magnitudeB = 0.0;

        $allTerms = array_unique(array_merge(array_keys($a), array_keys($b)));

        foreach ($allTerms as $term) {
            $valueA = $a[$term] ?? 0.0;
            $valueB = $b[$term] ?? 0.0;
            $dotProduct += $valueA * $valueB;
            $magnitudeA += $valueA ** 2;
            $magnitudeB += $valueB ** 2;
        }

        $denominator = sqrt($magnitudeA) * sqrt($magnitudeB);

        return $denominator > 0 ? $dotProduct / $denominator : 0.0;
    }

    /**
     * Pre-compute and store TF-IDF vectors for all documents.
     * Call this after seeding documents.
     */
    public function indexDocuments(): void
    {
        $documents = RagDocument::all();
        $allTokens = [];

        // Collect all tokens across all documents to compute IDF
        foreach ($documents as $doc) {
            $tokens = array_unique($this->tokenize($doc->title.' '.$doc->content));
            foreach ($tokens as $token) {
                $allTokens[$token] = ($allTokens[$token] ?? 0) + 1;
            }
        }

        $documentCount = $documents->count();
        $idfWeights = [];

        foreach ($allTokens as $term => $docFrequency) {
            $idfWeights[$term] = log($documentCount / $docFrequency);
        }

        foreach ($documents as $doc) {
            $vector = $this->buildTfIdfVector($doc->title.' '.$doc->content, $idfWeights);
            $doc->update(['tfidf_vector' => $vector]);
        }
    }

    /**
     * Retrieve the most relevant documents for a query.
     *
     * @return Collection<int, array{id: int, title: string, content: string, similarity: float}>
     */
    public function retrieve(string $query, int $topK = 3): Collection
    {
        $documents = RagDocument::whereNotNull('tfidf_vector')->get();

        if ($documents->isEmpty()) {
            return collect();
        }

        // Build IDF weights from stored vectors (union of all terms)
        $idfWeights = [];
        foreach ($documents as $doc) {
            foreach (array_keys($doc->tfidf_vector) as $term) {
                $idfWeights[$term] = 1.0; // weights already baked into stored vectors
            }
        }

        $queryVector = $this->buildTfIdfVector($query, $idfWeights);

        $scored = $documents->map(function (RagDocument $doc) use ($queryVector): array {
            return [
                'id' => $doc->id,
                'title' => $doc->title,
                'content' => $doc->content,
                'similarity' => $this->cosineSimilarity($queryVector, $doc->tfidf_vector),
            ];
        });

        return $scored
            ->sortByDesc('similarity')
            ->take($topK)
            ->filter(fn ($doc) => $doc['similarity'] > 0) // pass any overlap; LLM decides relevance
            ->values();
    }

    /**
     * Build the augmented prompt that gets sent to the LLM.
     *
     * @param  Collection<int, array{title: string, content: string, similarity: float}>  $retrievedDocs
     */
    public function buildAugmentedPrompt(string $query, Collection $retrievedDocs): string
    {
        $context = $retrievedDocs->map(function (array $doc, int $index): string {
            return '[Document '.($index + 1).': '.$doc['title']."]\n".$doc['content'];
        })->implode("\n\n---\n\n");

        return <<<PROMPT
        You are a knowledgeable assistant. Use ONLY the provided context to answer the question.
        If the context doesn't contain enough information, say so.

        === RETRIEVED CONTEXT ===
        {$context}
        === END CONTEXT ===

        Question: {$query}

        Answer based on the context above:
        PROMPT;
    }

    // /**
    //  * Send the augmented prompt to the LLM via OpenRouter and return the response.
    //  */
    // public function generate(string $augmentedPrompt): string
    // {
    //     $response = Http::withToken(config('services.openrouter.api_key'))
    //         ->withHeaders(['HTTP-Referer' => config('app.url')])
    //         ->post('https://openrouter.ai/api/v1/chat/completions', [
    //             'model' => 'openai/gpt-4o-mini',
    //             'messages' => [
    //                 ['role' => 'user', 'content' => $augmentedPrompt],
    //             ],
    //             'max_tokens' => 512,
    //         ]);

    //     if ($response->failed()) {
    //         return 'Error: Failed to get a response from the LLM. '.$response->body();
    //     }

    //     return $response->json('choices.0.message.content') ?? 'No response received.';
    // }
}
