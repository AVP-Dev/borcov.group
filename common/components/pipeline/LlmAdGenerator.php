<?php

declare(strict_types=1);

namespace common\components\pipeline;

use common\models\AdGroup;
use common\models\Keyword;
use Yii;

class LlmAdGenerator implements AdGeneratorInterface
{
    private const string API_URL = 'https://api.deepseek.com/v1/chat/completions';
    private const int TIMEOUT_SEC = 15;

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
        $apiKey = Yii::$app->params['deepseekApiKey'] ?? '';
        if ($apiKey === '') {
            return $this->markFallback($this->fallback->generate($group, $keyword));
        }

        $lang = $keyword->language ?: $group->language ?: 'en';
        $prompt = $this->buildPrompt($keyword, $group, $lang);

        [$status, $body] = $this->callApi($apiKey, $prompt);

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

    private function callApi(string $apiKey, string $prompt): array
    {
        $payload = json_encode([
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a Google Ads copywriter. Generate RSA ad components. Respond with JSON only.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
            'max_tokens' => 500,
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
        $url = $this->fallback->categoryUrlMap[$group->category] ?? '/';
        return "Generate 3 Google Responsive Search Ads for keyword \"{$keyword->raw_text}\" "
            . "(normalized: \"{$keyword->normalized_text}\") in {$lang} language. "
            . "Category: {$group->category}. "
            . "Return JSON array: [{\"headline1\":\"...\",\"headline2\":\"...\",\"headline3\":\"...\","
            . "\"description1\":\"...\",\"description2\":\"...\",\"path1\":\"...\",\"path2\":\"...\"}]. "
            . "Max 30 chars per headline, 90 per description, 15 per path. "
            . "URL: {$url}";
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
