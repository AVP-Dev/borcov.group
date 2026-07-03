<?php

declare(strict_types=1);

namespace common\components\pipeline;

use common\models\AdGroup;
use common\models\Keyword;
use Yii;

class LlmAdGenerator implements AdGeneratorInterface
{
    private const string API_URL = 'https://api.deepseek.com/v1/chat/completions';
    private const int TIMEOUT_SEC = 10;

    /**
     * @var float|null time limit for batch generation.
     * When set, generation stops after this many seconds and returns template ads.
     * Prevents timeout death during Generate All.
     */
    public ?float $timeBudget = null;

    /** @var float start time of the generation, set by first generate() call */
    private float $startTime = 0.0;

    public TemplateAdGenerator $fallback;

    /** @var callable(string, array, int): array HTTP transport: (url, headers, timeout) → [status, body] */
    private $httpTransport;

    public function __construct(?TemplateAdGenerator $fallback = null, ?callable $httpTransport = null)
    {
        $this->fallback = $fallback ?? new TemplateAdGenerator();
        $this->httpTransport = $httpTransport ?? function (string $url, array $headers, int $timeout): array {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $headers['__body__'] ?? '',
                CURLOPT_HTTPHEADER => $headers['__headers__'] ?? [],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => $timeout,
            ]);
            $body = curl_exec($ch);
            $status = curl_errno($ch) !== 0 ? 0 : (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return [$status, is_string($body) ? $body : ''];
        };
    }

    public function generate(AdGroup $group, Keyword $keyword): array
    {
        // Initialize start time on first call
        if ($this->startTime === 0.0) {
            $this->startTime = microtime(true);
        }

        // Time budget check: if exceeded, use template fallback immediately
        if ($this->timeBudget !== null && (microtime(true) - $this->startTime) > $this->timeBudget) {
            Yii::warning('LlmAdGenerator: time budget (' . $this->timeBudget . 's) exceeded, falling back to template', __METHOD__);
            return $this->markFallback($this->fallback->generate($group, $keyword));
        }

        $apiKey = Yii::$app->params['deepseekApiKey'] ?? '';
        if ($apiKey === '') {
            return $this->markFallback($this->fallback->generate($group, $keyword));
        }

        $lang = $keyword->language ?: $group->language ?: 'en';
        $prompt = $this->buildPrompt($keyword, $group, $lang);

        [$status, $body] = $this->callApi($apiKey, $prompt, $lang);

        if ($status !== 200 || $body === '') {
            return $this->markFallback($this->fallback->generate($group, $keyword));
        }

        $ads = $this->parseResponse($body, $keyword, $group);
        if ($ads === []) {
            return $this->markFallback($this->fallback->generate($group, $keyword));
        }

        // Pad with template ads if LLM returned fewer than 3
        if (count($ads) < 3) {
            $fallback = $this->fallback->generate($group, $keyword);
            foreach ($fallback as $ad) {
                if (count($ads) >= 3) break;
                $ads[] = new AdData(
                    headline1: $ad->headline1,
                    headline2: $ad->headline2,
                    headline3: $ad->headline3,
                    description1: $ad->description1,
                    description2: $ad->description2,
                    finalUrl: $ad->finalUrl,
                    path1: $ad->path1,
                    path2: $ad->path2,
                    source: AdData::SOURCE_LLM_FALLBACK,
                );
            }
        }

        return $ads;
    }

    /** @param AdData[] $ads */
    private function markFallback(array $ads): array
    {
        $result = [];
        foreach ($ads as $ad) {
            $result[] = new AdData(
                headline1: $ad->headline1,
                headline2: $ad->headline2,
                headline3: $ad->headline3,
                description1: $ad->description1,
                description2: $ad->description2,
                finalUrl: $ad->finalUrl,
                path1: $ad->path1,
                path2: $ad->path2,
                source: AdData::SOURCE_LLM_FALLBACK,
            );
        }
        return $result;
    }

    private function callApi(string $apiKey, string $prompt, string $lang): array
    {
        $adConfig = Yii::$app->params['adGeneration'] ?? [];
        $systemPromptKey = "system_prompt_{$lang}";
        $systemPrompt = $adConfig[$systemPromptKey] ?? $adConfig['system_prompt_en']
            ?? 'You are a Google Ads copywriter. Generate RSA ad components. Respond with JSON only.';

        $payload = json_encode([
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
            'max_tokens' => 800,
        ], JSON_UNESCAPED_UNICODE);

        $url = self::API_URL;
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ];

        return ($this->httpTransport)($url, [
            '__body__' => $payload,
            '__headers__' => $headers,
        ], self::TIMEOUT_SEC);
    }

    private function buildPrompt(Keyword $keyword, AdGroup $group, string $lang): string
    {
        $adConfig = Yii::$app->params['adGeneration'] ?? [];
        $brandName = $adConfig['brand_name'] ?? 'site.pro';
        $brandDescription = $adConfig['brand_description'] ?? 'online business platform';

        $categories = $adConfig['categories'] ?? [];
        $catConfig = $categories[$group->category] ?? [];

        $catDescription = $catConfig["description_{$lang}"] ?? $catConfig['description_en'] ?? '';
        $catUsp = $catConfig["usp_{$lang}"] ?? $catConfig['usp_en'] ?? '';

        $url = $this->fallback->categoryUrlMap[$group->category] ?? '/';

        return "You are writing Google RSA (Responsive Search Ads) for {$brandName} — {$brandDescription}.\n\n"
            . "PRODUCT LINE: {$catDescription}\n"
            . "USP: {$catUsp}\n"
            . "TARGET KEYWORD: \"{$keyword->raw_text}\" (normalized: \"{$keyword->normalized_text}\")\n"
            . "AUDIENCE: {$group->audience_segment}\n"
            . "LANGUAGE: {$lang} — respond in this language\n"
            . "URL: {$url}\n\n"
            . "RULES:\n"
            . "- Headlines max 30 chars, descriptions max 90 chars, paths max 15 chars\n"
            . "- Each ad must be relevant to BOTH the keyword AND the product line\n"
            . "- Include the keyword or a close variant in at least one headline per ad\n"
            . "- Path1: category slug (e.g. \"email\", \"domains\"), Path2: null\n\n"
            . "Return a JSON array of exactly 3 ads: "
            . "[{\"headline1\":\"...\",\"headline2\":\"...\",\"description1\":\"...\",\"path1\":\"...\",\"path2\":\"...\"}]";
    }

    /** @return AdData[] */
    private function parseResponse(string $body, Keyword $keyword, AdGroup $group): array
    {
        $data = json_decode($body, true);

        $content = '';
        if (is_array($data) && isset($data['choices'][0]['message']['content'])) {
            $content = $data['choices'][0]['message']['content'];
        } else {
            return [];
        }

        $cleaned = trim((string)preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content));
        $adsJson = json_decode($cleaned, true);
        if (!is_array($adsJson)) {
            return [];
        }

        $baseUrl = rtrim(Yii::$app->params['siteUrl'] ?? 'https://site.pro', '/');
        $targetUrl = $baseUrl . ($this->fallback->categoryUrlMap[$group->category] ?? '/');

        $results = [];
        foreach (array_slice($adsJson, 0, 3) as $item) {
            if (!is_array($item) || empty($item['headline1']) || empty($item['description1'])) {
                continue;
            }
            $results[] = new AdData(
                headline1: (string)$item['headline1'],
                headline2: (string)($item['headline2'] ?? $item['headline1']),
                headline3: isset($item['headline3']) ? (string)$item['headline3'] : null,
                description1: (string)$item['description1'],
                description2: isset($item['description2']) ? (string)$item['description2'] : null,
                finalUrl: $targetUrl,
                path1: isset($item['path1']) ? (string)$item['path1'] : null,
                path2: isset($item['path2']) ? (string)$item['path2'] : null,
                source: AdData::SOURCE_LLM,
            );
        }

        return $results;
    }
}
