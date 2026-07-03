<?php

declare(strict_types=1);

namespace common\tests\Unit\pipeline;

use Codeception\Test\Unit;
use common\components\pipeline\NormalizationService;
use common\tests\Support\UnitTester;

final class NormalizationServiceTest extends Unit
{
    protected UnitTester $tester;

    public function testLowercase(): void
    {
        $service = new NormalizationService();
        verify($service->normalize('Hello World'))->equals('hello world');
        verify($service->normalize('WEBSITE BUILDER'))->equals('website builder');
        verify($service->normalize('Конструктор Сайтов'))->equals('конструктор сайтов');
    }

    public function testTrim(): void
    {
        $service = new NormalizationService();
        verify($service->normalize('  hello  '))->equals('hello');
        verify($service->normalize("\t test \n"))->equals('test');
    }

    public function testCollapseWhitespace(): void
    {
        $service = new NormalizationService();
        verify($service->normalize('hello   world'))->equals('hello world');
        verify($service->normalize("hello\t\nworld"))->equals('hello world');
    }

    public function testUnifySpecialChars(): void
    {
        $service = new NormalizationService();
        verify($service->normalize("hello\u{2019}world"))->equals("hello'world");
        verify($service->normalize("hello\u{201C}world\u{201D}"))->equals('hello"world"');
        verify($service->normalize("top\u{2014}quality"))->equals('top-quality');
    }

    public function testMixedCyrillicLatin(): void
    {
        $service = new NormalizationService();
        verify($service->normalize('SEO конструктор'))->equals('seo конструктор');
        verify($service->normalize('Best хостинг 2024'))->equals('best хостинг 2024');
    }

    public function testEmptyString(): void
    {
        $service = new NormalizationService();
        verify($service->normalize(''))->equals('');
        verify($service->normalize('   '))->equals('');
    }

    public function testNormalizeBatch(): void
    {
        $service = new NormalizationService();
        $result = $service->normalizeBatch(['Hello', '  WORLD  ', 'Test']);
        verify($result)->equals(['hello', 'world', 'test']);
    }

    public function testDetectLanguageRussian(): void
    {
        $service = new NormalizationService();
        verify($service->detectLanguage('как сделать сайт'))->equals('ru');
        verify($service->detectLanguage('создать сайт бесплатно'))->equals('ru');
        verify($service->detectLanguage('бесплатный конструктор'))->equals('ru');
        verify($service->detectLanguage('конструктор интернет магазина'))->equals('ru');
        verify($service->detectLanguage('почта для домена'))->equals('ru');
    }

    public function testDetectLanguageEnglish(): void
    {
        $service = new NormalizationService();
        verify($service->detectLanguage('how to build a website'))->equals('en');
        verify($service->detectLanguage('best website builder'))->equals('en');
        verify($service->detectLanguage('buy domain name'))->equals('en');
        verify($service->detectLanguage('email hosting'))->equals('en');
    }

    public function testDetectLanguageMixed(): void
    {
        $service = new NormalizationService();
        verify($service->detectLanguage('SEO оптимизация'))->equals('ru');
        verify($service->detectLanguage('купить domain name'))->equals('ru');
    }
}
