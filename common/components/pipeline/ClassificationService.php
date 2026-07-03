<?php

declare(strict_types=1);

namespace common\components\pipeline;

use common\models\Keyword;

class ClassificationService
{
    public const EVENT_AFTER_CLASSIFICATION = 'afterClassification';

    private array $rules;

    public function __construct(?array $rules = null)
    {
        $this->rules = $rules ?? require dirname(__DIR__, 2) . '/config/classification.php';
    }

    public function classify(Keyword $keyword): array
    {
        $text = $keyword->normalized_text ?? $keyword->raw_text;
        $text = mb_strtolower(trim($text));

        $language = $keyword->language ?? 'en';

        $category = $this->classifyCategory($text, $language);
        $intent = $this->classifyIntent($text, $language, $category);
        $audience = $this->classifyAudience($text, $language);

        return compact('category', 'intent', 'audience');
    }

    private function classifyCategory(string $text, string $language): string
    {
        foreach ($this->rules['categories'] as $category => $patterns) {
            if ($this->textMatchesPatterns($text, $patterns, $language)) {
                return $category;
            }
        }
        return Keyword::CATEGORY_UNCLASSIFIED;
    }

    private function classifyIntent(string $text, string $language, string $category): string
    {
        if ($category === Keyword::CATEGORY_GENERAL_BRAND) {
            return Keyword::INTENT_NAVIGATIONAL;
        }

        $intentRules = $this->rules['intents'];
        $order = [Keyword::INTENT_NAVIGATIONAL, Keyword::INTENT_COMMERCIAL, Keyword::INTENT_INFORMATIONAL];

        foreach ($order as $intent) {
            if (!isset($intentRules[$intent])) {
                continue;
            }
            if ($this->textMatchesPatterns($text, $intentRules[$intent], $language)) {
                return $intent;
            }
        }

        return Keyword::INTENT_UNKNOWN;
    }

    private function classifyAudience(string $text, string $language): string
    {
        $b2bPatterns = $this->rules['audience_segments']['b2b'];
        if ($this->textMatchesPatterns($text, $b2bPatterns, $language)) {
            return Keyword::AUDIENCE_B2B;
        }
        return Keyword::AUDIENCE_B2C;
    }

    private function textMatchesPatterns(string $text, array $patterns, string $language): bool
    {
        if ($language === 'ru' && !empty($patterns['patterns_ru'])) {
            foreach ($patterns['patterns_ru'] as $pattern) {
                if ($this->matchesPattern($text, $pattern)) {
                    return true;
                }
            }
        }
        foreach ($patterns['patterns'] as $pattern) {
            if ($this->matchesPattern($text, $pattern)) {
                return true;
            }
        }
        return false;
    }

    private function matchesPattern(string $text, string $pattern): bool
    {
        return str_contains($text, $pattern);
    }
}
