<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IntakeTriageService
{
    private const CATEGORIES = [
        'Bug Report',
        'Feature Request',
        'Billing Issue',
        'Technical Question',
        'Incident/Outage',
    ];

    private const PRIORITIES = [
        'Low',
        'Medium',
        'High',
    ];

    private const ROUTING_MAP = [
        'Bug Report' => 'Engineering',
        'Feature Request' => 'Product',
        'Billing Issue' => 'Billing',
        'Technical Question' => 'IT/Security',
        'Incident/Outage' => 'Engineering',
    ];

    /**
     * @return array<string, mixed>
     */
    public function process(string $source, string $rawMessage): array
    {
        $rawMessage = trim($rawMessage);
        $modelResult = $this->classifyWithLlm($rawMessage);
        $modelUsed = $modelResult['model_used'] ?? 'rules-fallback';

        if ($modelResult === null) {
            $modelResult = $this->classifyWithRules($rawMessage);
            $modelUsed = 'rules-fallback';
        }

        $category = $this->sanitizeCategory($modelResult['category'] ?? null);
        $priority = $this->sanitizePriority($modelResult['priority'] ?? null, $category, $rawMessage);
        $confidence = $this->sanitizeConfidence($modelResult['confidence_score'] ?? 0);
        $coreIssue = $this->sanitizeCoreIssue($modelResult['core_issue'] ?? null, $rawMessage);
        $identifiers = $this->sanitizeIdentifiers($modelResult['identifiers'] ?? null);
        $urgencySignal = $this->sanitizeUrgencySignal($modelResult['urgency_signal'] ?? null, $category, $rawMessage);

        $ruleIdentifiers = $this->extractIdentifiers($rawMessage);
        $identifiers = array_merge($ruleIdentifiers, $identifiers);

        $discrepancy = $this->extractBillingDiscrepancy($rawMessage);
        if ($discrepancy !== null) {
            $identifiers['billing_difference'] = round($discrepancy, 2);
        }

        [$queue, $escalationReasons] = $this->determineRoutingAndEscalation(
            $category,
            $confidence,
            $rawMessage,
            $discrepancy
        );

        $summary = $this->buildSummary($category, $priority, $confidence, $coreIssue, $queue, $escalationReasons);

        return [
            'source' => $source,
            'raw_message' => $rawMessage,
            'category' => $category,
            'priority' => $priority,
            'confidence_score' => $confidence,
            'core_issue' => $coreIssue,
            'identifiers' => $identifiers,
            'urgency_signal' => $urgencySignal,
            'routing_queue' => $queue,
            'escalation_flag' => $escalationReasons !== [],
            'escalation_reasons' => $escalationReasons,
            'human_summary' => $summary,
            'model_used' => $modelUsed,
            'processed_at' => now(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function classifyWithLlm(string $rawMessage): ?array
    {
        $apiKey = config('services.llm.api_key');
        if (! is_string($apiKey) || $apiKey === '') {
            return null;
        }

        $provider = strtolower((string) config('services.llm.provider', 'openai'));
        $model = (string) config('services.llm.model', 'gpt-4o-mini');
        $baseUrl = $this->resolveLlmBaseUrl(
            $provider,
            config('services.llm.base_url')
        );

        $systemPrompt = <<<'PROMPT'
You are a support triage assistant. Return strict JSON only (no markdown).
Allowed category values: Bug Report, Feature Request, Billing Issue, Technical Question, Incident/Outage.
Allowed priority values: Low, Medium, High.
confidence_score must be an integer from 0 to 100.
identifiers must be an object. Use arrays when multiple values exist.
human_summary must be 2-3 sentences and suitable for a receiving team.
PROMPT;

        $userPrompt = <<<PROMPT
Classify and enrich this inbound customer message:

{$rawMessage}

Return this JSON shape exactly:
{
  "category": "...",
  "priority": "...",
  "confidence_score": 0,
  "core_issue": "...",
  "identifiers": {},
  "urgency_signal": "Low|Medium|High",
  "human_summary": "..."
}
PROMPT;

        try {
            $response = Http::timeout(25)
                ->retry(1, 200)
                ->withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->post($baseUrl.'/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.1,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                ]);
        } catch (\Throwable $exception) {
            Log::warning('LLM triage request failed; using fallback rules.', [
                'provider' => $provider,
                'model' => $model,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('LLM triage returned non-success response; using fallback rules.', [
                'provider' => $provider,
                'model' => $model,
                'status' => $response->status(),
                'error_type' => data_get($response->json(), 'error.type'),
                'error_code' => data_get($response->json(), 'error.code'),
                'error_message' => data_get($response->json(), 'error.message'),
            ]);

            return null;
        }

        $content = data_get($response->json(), 'choices.0.message.content');
        if (! is_string($content)) {
            Log::warning('LLM triage response missing content; using fallback rules.', [
                'provider' => $provider,
                'model' => $model,
            ]);

            return null;
        }

        $parsed = $this->extractJsonObject($content);
        if (! is_array($parsed)) {
            Log::warning('LLM triage response not parseable JSON; using fallback rules.', [
                'provider' => $provider,
                'model' => $model,
                'content_preview' => Str::limit($content, 240),
            ]);

            return null;
        }

        $parsed['model_used'] = "{$provider}:{$model}";

        return $parsed;
    }

    private function resolveLlmBaseUrl(string $provider, mixed $configuredBaseUrl): string
    {
        if (is_string($configuredBaseUrl) && trim($configuredBaseUrl) !== '') {
            return rtrim(trim($configuredBaseUrl), '/');
        }

        return match ($provider) {
            'groq' => 'https://api.groq.com/openai/v1',
            default => 'https://api.openai.com/v1',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function classifyWithRules(string $rawMessage): array
    {
        $message = Str::lower($rawMessage);

        $category = match (true) {
            $this->containsAny($message, ['outage', 'down for all users', 'multiple users affected', 'stopped loading', 'service down']) => 'Incident/Outage',
            $this->containsAny($message, ['invoice', 'billing', 'charge', 'contract rate']) => 'Billing Issue',
            $this->containsAny($message, ['feature', 'would love to see', 'save us hours', 'bulk export']) => 'Feature Request',
            $this->containsAny($message, ['is there a way', 'how do', 'sso', 'okta', 'auth provider', 'not sure if this is the right place']) => 'Technical Question',
            default => 'Bug Report',
        };

        $priority = match ($category) {
            'Incident/Outage' => 'High',
            'Billing Issue' => 'High',
            'Feature Request' => 'Low',
            'Technical Question' => 'Medium',
            default => 'Medium',
        };

        $confidence = match ($category) {
            'Feature Request' => 92,
            'Billing Issue' => 90,
            'Incident/Outage' => 93,
            'Technical Question' => 84,
            default => 88,
        };

        if ($this->containsAny($message, ['not sure', 'maybe', 'i think'])) {
            $confidence = max(70, $confidence - 10);
        }

        $coreIssue = $this->firstSentence($rawMessage);
        $identifiers = $this->extractIdentifiers($rawMessage);
        $urgencySignal = $this->deriveUrgencySignal($category, $rawMessage);

        return [
            'category' => $category,
            'priority' => $priority,
            'confidence_score' => $confidence,
            'core_issue' => $coreIssue,
            'identifiers' => $identifiers,
            'urgency_signal' => $urgencySignal,
            'human_summary' => $this->buildSummary(
                $category,
                $priority,
                $confidence,
                $coreIssue,
                self::ROUTING_MAP[$category] ?? 'General Triage',
                []
            ),
        ];
    }

    /**
     * @return array{0: string, 1: array<int, string>}
     */
    private function determineRoutingAndEscalation(
        string $category,
        int $confidence,
        string $rawMessage,
        ?float $billingDiscrepancy
    ): array {
        $queue = self::ROUTING_MAP[$category] ?? 'General Triage';
        $reasons = [];

        if ($confidence < 70) {
            $reasons[] = 'Confidence below 70%';
        }

        $message = Str::lower($rawMessage);
        if ($this->containsAny($message, ['outage', 'down for all users', 'multiple users affected'])) {
            $reasons[] = 'Outage language detected';
        }

        if ($billingDiscrepancy !== null && $billingDiscrepancy > 500) {
            $reasons[] = 'Billing discrepancy above $500';
        }

        if ($reasons !== []) {
            $queue = 'Escalation Queue';
        }

        return [$queue, $reasons];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractIdentifiers(string $rawMessage): array
    {
        $identifiers = [];

        if (preg_match('/arcvault\.io\/user\/[a-z0-9._-]+/i', $rawMessage, $accountMatch)) {
            $identifiers['account_reference'] = rtrim($accountMatch[0], '.,;:!?');
        }

        if (preg_match('/invoice\s*#?\s*(\d+)/i', $rawMessage, $invoiceMatch)) {
            $identifiers['invoice_number'] = $invoiceMatch[1];
        }

        if (preg_match_all('/\b([45]\d{2})\b/', $rawMessage, $errorMatches)) {
            $identifiers['error_codes'] = array_values(array_unique($errorMatches[1]));
        }

        if (preg_match_all('/\$(\d{1,3}(?:,\d{3})*(?:\.\d{1,2})?)/', $rawMessage, $amountMatches)) {
            $identifiers['amounts_usd'] = array_map(
                fn (string $value): float => (float) str_replace(',', '', $value),
                array_values(array_unique($amountMatches[1]))
            );
        }

        if (preg_match('/\b(okta|azure ad|google workspace)\b/i', $rawMessage, $providerMatch)) {
            $identifiers['auth_provider'] = Str::title($providerMatch[1]);
        }

        if (preg_match('/\b(\d{1,2}\s?(?:am|pm)\s?(?:est|pst|cst|mst|utc)?)\b/i', $rawMessage, $timeMatch)) {
            $identifiers['reported_time'] = strtoupper($timeMatch[1]);
        }

        return $identifiers;
    }

    private function extractBillingDiscrepancy(string $rawMessage): ?float
    {
        if (! preg_match_all('/\$(\d{1,3}(?:,\d{3})*(?:\.\d{1,2})?)/', $rawMessage, $amountMatches)) {
            return null;
        }

        $amounts = array_map(
            fn (string $value): float => (float) str_replace(',', '', $value),
            $amountMatches[1]
        );

        if (count($amounts) < 2) {
            return null;
        }

        return abs($amounts[0] - $amounts[1]);
    }

    private function sanitizeCategory(mixed $category): string
    {
        if (is_string($category) && in_array($category, self::CATEGORIES, true)) {
            return $category;
        }

        return 'Technical Question';
    }

    private function sanitizePriority(mixed $priority, string $category, string $rawMessage): string
    {
        if (is_string($priority) && in_array($priority, self::PRIORITIES, true)) {
            return $priority;
        }

        return match ($category) {
            'Incident/Outage' => 'High',
            'Feature Request' => 'Low',
            'Billing Issue' => 'High',
            default => $this->deriveUrgencySignal($category, $rawMessage),
        };
    }

    private function sanitizeConfidence(mixed $confidence): int
    {
        if (is_float($confidence) && $confidence > 0 && $confidence <= 1) {
            return (int) round($confidence * 100);
        }

        if (is_numeric($confidence)) {
            return (int) max(0, min(100, round((float) $confidence)));
        }

        return 70;
    }

    private function sanitizeCoreIssue(mixed $coreIssue, string $rawMessage): string
    {
        if (is_string($coreIssue) && trim($coreIssue) !== '') {
            return trim($coreIssue);
        }

        return $this->firstSentence($rawMessage);
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitizeIdentifiers(mixed $identifiers): array
    {
        if (is_array($identifiers)) {
            return $identifiers;
        }

        return [];
    }

    private function sanitizeUrgencySignal(mixed $urgencySignal, string $category, string $rawMessage): string
    {
        if (is_string($urgencySignal) && in_array($urgencySignal, self::PRIORITIES, true)) {
            return $urgencySignal;
        }

        return $this->deriveUrgencySignal($category, $rawMessage);
    }

    private function deriveUrgencySignal(string $category, string $rawMessage): string
    {
        $message = Str::lower($rawMessage);

        if ($category === 'Incident/Outage' || $this->containsAny($message, ['urgent', 'asap', 'multiple users affected'])) {
            return 'High';
        }

        if ($category === 'Feature Request') {
            return 'Low';
        }

        return 'Medium';
    }

    private function firstSentence(string $rawMessage): string
    {
        $parts = preg_split('/(?<=[.!?])\s+/', trim($rawMessage));
        if ($parts === false || $parts === []) {
            return Str::limit($rawMessage, 180);
        }

        return trim((string) $parts[0]);
    }

    /**
     * @param  array<int, string>  $escalationReasons
     */
    private function buildSummary(
        string $category,
        string $priority,
        int $confidence,
        string $coreIssue,
        string $queue,
        array $escalationReasons
    ): string {
        $line1 = "Classified as {$category} with {$priority} priority ({$confidence}% confidence).";
        $line2 = "Core issue: {$coreIssue}";
        $line2 = rtrim($line2, " .!?\t\n\r\0\x0B");
        $line2 .= '.';

        if ($escalationReasons !== []) {
            $line3 = 'Escalated to '.$queue.' because: '.implode('; ', $escalationReasons).'.';

            return "{$line1} {$line2} {$line3}";
        }

        $line3 = "Routed to {$queue} queue for action.";

        return "{$line1} {$line2} {$line3}";
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        return Str::contains($haystack, $needles);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractJsonObject(string $content): ?array
    {
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (! preg_match('/\{.*\}/s', $content, $jsonMatch)) {
            return null;
        }

        $decoded = json_decode($jsonMatch[0], true);

        return is_array($decoded) ? $decoded : null;
    }
}
