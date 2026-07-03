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

    /** @var array<string, array<string, string>> language → category → USP */
    public array $uspMap = [
        'en' => [
            Keyword::CATEGORY_WEBSITE_BUILDER => 'Create stunning websites in minutes. No coding needed.',
            Keyword::CATEGORY_EMAIL => 'Professional email for your domain. 10GB+ storage.',
            Keyword::CATEGORY_DOMAINS => 'Find your perfect domain name. Free privacy protection.',
            Keyword::CATEGORY_ACCOUNTING => 'Smart accounting for small business. VAT ready.',
            Keyword::CATEGORY_INVOICING => 'Professional invoices in seconds. Get paid faster.',
            Keyword::CATEGORY_RESELLER => 'White-label hosting platform. Your brand, our infrastructure.',
            Keyword::CATEGORY_GENERAL_BRAND => 'All-in-one online business platform.',
            Keyword::CATEGORY_UNCLASSIFIED => 'Powerful tools for your online business.',
        ],
        'ru' => [
            Keyword::CATEGORY_WEBSITE_BUILDER => 'Создайте сайт за минуты. Без кода и дизайнера.',
            Keyword::CATEGORY_EMAIL => 'Профессиональная почта для вашего домена. 10ГБ+.',
            Keyword::CATEGORY_DOMAINS => 'Найдите идеальное доменное имя. Защита конфиденциальности.',
            Keyword::CATEGORY_ACCOUNTING => 'Умная бухгалтерия для малого бизнеса. С НДС.',
            Keyword::CATEGORY_INVOICING => 'Счета за секунды. Получайте оплату быстрее.',
            Keyword::CATEGORY_RESELLER => 'White-label платформа. Ваш бренд, наша инфраструктура.',
            Keyword::CATEGORY_GENERAL_BRAND => 'Всё для онлайн-бизнеса в одной платформе.',
            Keyword::CATEGORY_UNCLASSIFIED => 'Мощные инструменты для вашего онлайн-бизнеса.',
        ],
    ];

    /** @var array<string, string[]> language → headline patterns */
    public array $headlinePatterns = [
        'en' => [
            '{keyword}',
            '{keyword} — Try It Free',
            'Best {keyword}',
            '{keyword} Online',
            '{keyword} Today',
        ],
        'ru' => [
            '{keyword}',
            '{keyword} — попробуйте бесплатно',
            'Лучший {keyword}',
            '{keyword} онлайн',
            '{keyword} сегодня',
        ],
    ];

    /** @var array<string, string[]> language → description patterns */
    public array $descriptionPatterns = [
        'en' => [
            '{usp} Build your online presence with site.pro. Start free today.',
            'Looking for {keyword}? {usp} Get started in minutes.',
            '{keyword} — {usp} Join 1M+ businesses worldwide.',
        ],
        'ru' => [
            '{usp} Создайте свой онлайн-бизнес с site.pro. Начните бесплатно.',
            'Ищете {keyword}? {usp} Начните за минуты.',
            '{keyword} — {usp} Присоединяйтесь к 1M+ компаний по всему миру.',
        ],
    ];

    public function generate(AdGroup $group, Keyword $keyword): array
    {
        $lang = $this->resolveLanguage($group, $keyword);
        $usp = $this->uspMap[$lang][$group->category] ?? $this->uspMap['en'][Keyword::CATEGORY_UNCLASSIFIED];
        $baseUrl = rtrim(Yii::$app->params['siteUrl'] ?? 'https://site.pro', '/');
        $targetUrl = $baseUrl . ($this->categoryUrlMap[$group->category] ?? '/');
        $path1 = $this->resolvePath1($group, $keyword);

        $keywordText = $keyword->normalized_text ?: $keyword->raw_text;

        $headlines = $this->headlinePatterns[$lang] ?? $this->headlinePatterns['en'];
        $descriptions = $this->descriptionPatterns[$lang] ?? $this->descriptionPatterns['en'];

        $ads = [];
        $pairCount = min(count($headlines), count($descriptions));

        for ($i = 0; $i < $pairCount; $i++) {
            $h1 = $this->fill($headlines[$i], $keywordText, $usp);
            $h2 = $this->fill($headlines[($i + 1) % count($headlines)], $keywordText, $usp);

            $d1 = $this->fill($descriptions[$i], $keywordText, $usp);

            $ads[] = new AdData(
                headline1: mb_substr($h1, 0, self::MAX_HEADLINE_LENGTH),
                headline2: mb_substr($h2, 0, self::MAX_HEADLINE_LENGTH),
                headline3: null,
                description1: mb_substr($d1, 0, self::MAX_DESCRIPTION_LENGTH),
                description2: null,
                finalUrl: $targetUrl,
                path1: mb_substr($path1, 0, self::MAX_PATH_LENGTH),
                path2: null,
                source: AdData::SOURCE_TEMPLATE,
            );
        }

        return $ads;
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
