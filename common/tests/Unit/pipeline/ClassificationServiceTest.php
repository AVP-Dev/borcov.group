<?php

declare(strict_types=1);

namespace common\tests\Unit\pipeline;

use Codeception\Test\Unit;
use common\components\pipeline\ClassificationService;
use common\models\Keyword;

final class ClassificationServiceTest extends Unit
{
    private function makeKeyword(string $text, string $language = 'en'): Keyword
    {
        $keyword = new Keyword();
        $keyword->raw_text = $text;
        $keyword->normalized_text = mb_strtolower(trim($text));
        $keyword->language = $language;
        return $keyword;
    }

    // Category tests

    public function testWebsiteBuilderCategory(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('best website builder'));
        verify($result['category'])->equals(Keyword::CATEGORY_WEBSITE_BUILDER);
    }

    public function testWebsiteBuilderRussian(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('конструктор сайтов', 'ru'));
        verify($result['category'])->equals(Keyword::CATEGORY_WEBSITE_BUILDER);
    }

    public function testEmailCategory(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('business email hosting'));
        verify($result['category'])->equals(Keyword::CATEGORY_EMAIL);
    }

    public function testEmailRussian(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('корпоративная почта', 'ru'));
        verify($result['category'])->equals(Keyword::CATEGORY_EMAIL);
    }

    public function testDomainsCategory(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('register domain name'));
        verify($result['category'])->equals(Keyword::CATEGORY_DOMAINS);
    }

    public function testDomainsRussian(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('регистрация домена', 'ru'));
        verify($result['category'])->equals(Keyword::CATEGORY_DOMAINS);
    }

    public function testAccountingCategory(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('online accounting software'));
        verify($result['category'])->equals(Keyword::CATEGORY_ACCOUNTING);
    }

    public function testAccountingRussian(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('бухгалтерия онлайн', 'ru'));
        verify($result['category'])->equals(Keyword::CATEGORY_ACCOUNTING);
    }

    public function testInvoicingCategory(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('free invoice generator'));
        verify($result['category'])->equals(Keyword::CATEGORY_INVOICING);
    }

    public function testInvoicingRussian(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('выставить счёт', 'ru'));
        verify($result['category'])->equals(Keyword::CATEGORY_INVOICING);
    }

    public function testResellerCategory(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('reseller hosting program'));
        verify($result['category'])->equals(Keyword::CATEGORY_RESELLER);
    }

    public function testResellerRussian(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('партнёрская программа хостинг', 'ru'));
        verify($result['category'])->equals(Keyword::CATEGORY_RESELLER);
    }

    public function testUnclassifiedCategory(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('random generic keyword'));
        verify($result['category'])->equals(Keyword::CATEGORY_UNCLASSIFIED);
    }

    // Intent tests

    public function testCommercialIntent(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('buy website builder'));
        verify($result['intent'])->equals(Keyword::INTENT_COMMERCIAL);
    }

    public function testCommercialRussian(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('купить конструктор сайтов', 'ru'));
        verify($result['intent'])->equals(Keyword::INTENT_COMMERCIAL);
    }

    public function testInformationalIntent(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('how to build a website'));
        verify($result['intent'])->equals(Keyword::INTENT_INFORMATIONAL);
    }

    public function testInformationalRussian(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('как создать сайт', 'ru'));
        verify($result['intent'])->equals(Keyword::INTENT_INFORMATIONAL);
    }

    public function testNavigationalIntent(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('site.pro'));
        verify($result['intent'])->equals(Keyword::INTENT_NAVIGATIONAL);
    }

    public function testNavigationalRussian(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('site pro', 'ru'));
        verify($result['intent'])->equals(Keyword::INTENT_NAVIGATIONAL);
    }

    public function testUnknownIntent(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('banana republic'));
        verify($result['intent'])->equals(Keyword::INTENT_UNKNOWN);
    }

    // Audience segment tests

    public function testB2BAudience(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('reseller hosting for agencies'));
        verify($result['audience'])->equals(Keyword::AUDIENCE_B2B);
    }

    public function testB2BRussian(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('партнёрская программа для агентств', 'ru'));
        verify($result['audience'])->equals(Keyword::AUDIENCE_B2B);
    }

    public function testB2CAudience(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('create a personal website'));
        verify($result['audience'])->equals(Keyword::AUDIENCE_B2C);
    }

    // Edge cases

    public function testEmptyText(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword(''));
        verify($result['category'])->equals(Keyword::CATEGORY_UNCLASSIFIED);
        verify($result['intent'])->equals(Keyword::INTENT_UNKNOWN);
        verify($result['audience'])->equals(Keyword::AUDIENCE_B2C);
    }

    public function testNullLanguage(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('buy email account', ''));
        verify($result['category'])->equals(Keyword::CATEGORY_EMAIL);
        verify($result['intent'])->equals(Keyword::INTENT_COMMERCIAL);
    }

    public function testWhitespaceOnly(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('   '));
        verify($result['category'])->equals(Keyword::CATEGORY_UNCLASSIFIED);
    }

    public function testGeneralBrandBecomesNavigational(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('site.pro pricing'));
        verify($result['category'])->equals(Keyword::CATEGORY_GENERAL_BRAND);
        verify($result['intent'])->equals(Keyword::INTENT_NAVIGATIONAL);
    }

    public function testSiteproWithoutDot(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('site pro builder'));
        verify($result['category'])->equals(Keyword::CATEGORY_GENERAL_BRAND);
    }

    public function testEmailIntentClassification(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('cheap email hosting'));
        verify($result['category'])->equals(Keyword::CATEGORY_EMAIL);
        verify($result['intent'])->equals(Keyword::INTENT_COMMERCIAL);
    }

    public function testMixedLanguagePattern(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('купить domain name', 'ru'));
        verify($result['category'])->equals(Keyword::CATEGORY_DOMAINS);
        verify($result['intent'])->equals(Keyword::INTENT_COMMERCIAL);
    }

    // Regression tests — specific phrases from bug reports

    public function testRegressionHowToBuildSiteRu(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('как сделать сайт', 'ru'));
        verify($result['category'])->equals(Keyword::CATEGORY_WEBSITE_BUILDER);
        verify($result['intent'])->equals(Keyword::INTENT_INFORMATIONAL);
    }

    public function testRegressionCreateSiteFreeRu(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('создать сайт бесплатно', 'ru'));
        verify($result['category'])->equals(Keyword::CATEGORY_WEBSITE_BUILDER);
        verify($result['intent'])->equals(Keyword::INTENT_INFORMATIONAL);
    }

    public function testRegressionFreeBuilderRu(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('бесплатный конструктор', 'ru'));
        verify($result['category'])->equals(Keyword::CATEGORY_WEBSITE_BUILDER);
        verify($result['intent'])->equals(Keyword::INTENT_INFORMATIONAL);
    }

    public function testRegressionOnlineStoreBuilderRu(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('конструктор интернет магазина', 'ru'));
        verify($result['category'])->equals(Keyword::CATEGORY_WEBSITE_BUILDER);
    }

    public function testRegressionEmailForDomainRu(): void
    {
        $service = new ClassificationService();
        $result = $service->classify($this->makeKeyword('почта для домена', 'ru'));
        verify($result['category'])->equals(Keyword::CATEGORY_EMAIL);
    }
}
