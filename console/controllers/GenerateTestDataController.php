<?php

declare(strict_types=1);

namespace console\controllers;

use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Generate large test data files for stress-testing the pipeline.
 */
class GenerateTestDataController extends Controller
{
    private const int TARGET_ROWS = 5000;

    private const array KEYWORD_PATTERNS = [
        // English website builder
        'en' => [
            'website_builder' => [
                'create a website', 'build a website', 'make a website', 'best website builder',
                'free website builder', 'website creator', 'online website builder',
                'how to build a website', 'easy website builder', 'professional website builder',
                'cheap website builder', 'website builder for small business',
                'responsive website builder', 'ecommerce website builder', 'landing page builder',
                'drag and drop website builder', 'website builder for photographers',
                'blog website builder', 'portfolio website builder', 'business website builder',
            ],
            'email' => [
                'business email', 'email hosting', 'professional email address',
                'cheap email hosting', 'email for domain', 'custom email address',
                'email hosting for small business', 'secure email hosting',
                'email hosting with domain', 'best email hosting',
            ],
            'domains' => [
                'buy domain name', 'cheap domain name', 'domain registration',
                'domain name search', 'register domain name', 'cheap domains',
                'domain hosting', 'transfer domain', 'domain name checker',
                'find domain name',
            ],
            'accounting' => [
                'online accounting software', 'small business accounting',
                'accounting software', 'cheap accounting software', 'accounting tools online',
                'accounting for freelancers', 'accounting for entrepreneurs',
            ],
            'invoicing' => [
                'invoicing software', 'online invoicing', 'invoice generator',
                'billing software', 'invoice maker', 'send invoices online',
                'professional invoices', 'invoicing for freelancers',
            ],
            'reseller' => [
                'reseller hosting', 'white label hosting', 'web hosting reseller',
                'reseller web hosting', 'white label web hosting', 'hosting reseller program',
                'reseller plan', 'reseller hosting company',
            ],
        ],
        // Russian
        'ru' => [
            'website_builder' => [
                'создать сайт', 'конструктор сайтов', 'создать сайт бесплатно',
                'как сделать сайт', 'конструктор сайтов бесплатно', 'сделать сайт',
                'как создать сайт', 'создание сайта', 'конструктор интернет магазина',
                'бесплатный конструктор сайтов', 'сайт визитка', 'лендинг пейдж',
                'профессиональный сайт', 'сайт для бизнеса', 'интернет магазин',
            ],
            'email' => [
                'корпоративная почта', 'почта для домена', 'бизнес почта',
                'email хостинг', 'почтовый ящик для домена', 'создать почту для домена',
                'профессиональная почта', 'почта для организации',
            ],
            'domains' => [
                'купить домен', 'домен для сайта', 'регистрация домена',
                'дешёвый домен', 'проверить домен', 'доменное имя',
                'купить доменное имя', 'домен в зоне',
            ],
            'accounting' => [
                'бухгалтерия онлайн', 'программа для бухгалтерии',
                'бухгалтерский учёт онлайн', 'автоматизация бухгалтерии',
                'сервис для бухгалтерии', 'онлайн бухгалтерия',
            ],
            'invoicing' => [
                'выставление счетов онлайн', 'счёт на оплату', 'сервис для счетов',
                'выставить счёт', 'онлайн счета', 'программа для счетов',
                'автоматизация выставления счетов',
            ],
            'reseller' => [
                'реселлер хостинг', 'white label хостинг', 'партнёрская программа хостинга',
                'реселлер программа', 'белый хостинг', 'хостинг для реселлеров',
            ],
        ],
    ];

    private const array BRANDS = ['site.pro', 'wix', 'tilda', 'wordpress', 'squarespace', 'jimdo', 'ucoz'];

    public function actionIndex(int $count = self::TARGET_ROWS): int
    {
        $filePath = dirname(__DIR__, 2) . '/docs/test-data/large_sample.csv';
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $handle = fopen($filePath, 'w');
        if ($handle === false) {
            $this->stderr("Failed to create file: $filePath\n");
            return ExitCode::IOERR;
        }

        fputcsv($handle, ['keyword', 'volume'], escape: '');

        $generated = 0;
        $categories = array_keys(self::KEYWORD_PATTERNS['en']);
        $langs = ['en', 'ru'];

        while ($generated < $count) {
            $lang = $langs[array_rand($langs)];
            $category = $categories[array_rand($categories)];
            $patterns = self::KEYWORD_PATTERNS[$lang][$category];

            // Pick one or two patterns and combine
            $pattern = $patterns[array_rand($patterns)];

            // Occasionally prepend a brand
            $keyword = '';
            if (mt_rand(0, 10) < 2) {
                $brand = self::BRANDS[array_rand(self::BRANDS)];
                $keyword = $brand . ' ' . $pattern;
            } else {
                $keyword = $pattern;
            }

            // Occasionally add a modifier
            if (mt_rand(0, 10) < 3) {
                $modifiers = $lang === 'ru'
                    ? ['онлайн', 'бесплатно', 'цена', 'отзывы', 'рейтинг', 'дешёвый']
                    : ['online', 'free', 'price', 'review', 'best', 'cheap', 'top', 'affordable'];
                $mod = $modifiers[array_rand($modifiers)];
                $keyword = mt_rand(0, 1) ? "$mod $keyword" : "$keyword $mod";
            }

            // Random volume
            $volume = (string) mt_rand(50, 50000);

            // Occasionally (5%) produce a variant with different case
            if (mt_rand(0, 100) < 5) {
                $words = explode(' ', $keyword);
                foreach ($words as $i => $w) {
                    if (mt_rand(0, 1)) {
                        $words[$i] = ucfirst($w);
                    }
                }
                $keyword = implode(' ', $words);
            }

            fputcsv($handle, [$keyword, $volume], escape: '');
            $generated++;
        }

        fclose($handle);

        $this->stdout("Generated $generated rows → $filePath\n");
        return ExitCode::OK;
    }
}
