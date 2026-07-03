<?php

declare(strict_types=1);

namespace common\components\pipeline;

use common\models\AdGroup;
use common\models\Keyword;
use Yii;

class TemplateAdGenerator implements AdGeneratorInterface
{
    public const string DEFAULT_PATH = 'online';

    /** @var array<string, string> category → target URL path */
    public array $categoryUrlMap = [
        Keyword::CATEGORY_WEBSITE_BUILDER => '/website-builder',
        Keyword::CATEGORY_EMAIL => '/email',
        Keyword::CATEGORY_DOMAINS => '/domains',
        Keyword::CATEGORY_ACCOUNTING => '/accounting',
        Keyword::CATEGORY_INVOICING => '/invoicing',
        Keyword::CATEGORY_RESELLER => '/reseller',
        Keyword::CATEGORY_GENERAL_BRAND => '/',
        Keyword::CATEGORY_UNCLASSIFIED => '/',
    ];

    public function generate(AdGroup $group, Keyword $keyword): array
    {
        $lang = $this->resolveLanguage($group, $keyword);
        $adConfig = Yii::$app->params['adGeneration'] ?? [];
        $categories = $adConfig['categories'] ?? [];
        $catConfig = $categories[$group->category] ?? $categories[Keyword::CATEGORY_UNCLASSIFIED] ?? [];

        $usp = $catConfig["usp_{$lang}"] ?? $catConfig['usp_en'] ?? '';
        $baseUrl = rtrim(Yii::$app->params['siteUrl'] ?? 'https://site.pro', '/');
        $targetUrl = $baseUrl . ($this->categoryUrlMap[$group->category] ?? '/');
        $path1 = $this->resolvePath1($group, $keyword);

        $keywordText = $keyword->normalized_text ?: $keyword->raw_text;

        $langHeadlines = $catConfig["headline_patterns_{$lang}"] ?? $catConfig['headline_patterns_en'] ?? [];
        $langDescriptions = $catConfig["description_patterns_{$lang}"] ?? $catConfig['description_patterns_en'] ?? [];

        if ($langHeadlines === []) {
            $fallbackCat = $categories[Keyword::CATEGORY_UNCLASSIFIED] ?? [];
            $langHeadlines = $fallbackCat["headline_patterns_{$lang}"] ?? $fallbackCat['headline_patterns_en'] ?? ['{keyword}'];
        }
        if ($langDescriptions === []) {
            $fallbackCat = $categories[Keyword::CATEGORY_UNCLASSIFIED] ?? [];
            $langDescriptions = $fallbackCat["description_patterns_{$lang}"] ?? $fallbackCat['description_patterns_en'] ?? ['{usp}'];
        }

        $ads = [];
        $pairCount = min(count($langHeadlines), count($langDescriptions));

        for ($i = 0; $i < $pairCount; $i++) {
            $h1 = $this->mbUcfirst($this->fill($langHeadlines[$i], $keywordText, $usp));
            $h2 = $this->mbUcfirst($this->fill($langHeadlines[($i + 1) % count($langHeadlines)], $keywordText, $usp));
            $d1 = $this->mbUcfirst($this->fill($langDescriptions[$i], $keywordText, $usp));

            $ads[] = new AdData(
                headline1: $this->truncateWordSafe($h1, self::MAX_HEADLINE_LENGTH),
                headline2: $this->truncateWordSafe($h2, self::MAX_HEADLINE_LENGTH),
                headline3: null,
                description1: $this->truncateWordSafe($d1, self::MAX_DESCRIPTION_LENGTH),
                description2: null,
                finalUrl: $targetUrl,
                path1: $this->truncateWordSafe($path1, self::MAX_PATH_LENGTH),
                path2: null,
                source: AdData::SOURCE_TEMPLATE,
            );
        }

        return $ads;
    }

    private function mbUcfirst(string $text): string
    {
        if ($text === '') {
            return $text;
        }
        return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }

    private function truncateWordSafe(string $text, int $maxLength): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }
        $truncated = mb_substr($text, 0, $maxLength);
        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace !== false) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }
        return $truncated;
    }

    private function resolveLanguage(AdGroup $group, Keyword $keyword): string
    {
        return $keyword->language ?: ($group->language ?: 'en');
    }

    private function resolvePath1(AdGroup $group, Keyword $keyword): string
    {
        $path = $keyword->category ?: $group->category;
        if ($path === Keyword::CATEGORY_GENERAL_BRAND || $path === Keyword::CATEGORY_UNCLASSIFIED) {
            return self::DEFAULT_PATH;
        }
        return str_replace('_', '-', $path);
    }

    private function fill(string $pattern, string $keyword, string $usp): string
    {
        return str_replace(
            ['{keyword}', '{usp}'],
            [$keyword, $usp],
            $pattern,
        );
    }
}
