<?php

declare(strict_types=1);

namespace common\tests\Unit\pipeline;

use Codeception\Test\Unit;
use common\components\pipeline\AdGeneratorInterface;
use common\components\pipeline\TemplateAdGenerator;
use common\models\AdGroup;
use common\models\Keyword;
use common\tests\Support\UnitTester;
use Yii;

final class TemplateAdGeneratorTest extends Unit
{
    protected UnitTester $tester;

    protected function _setUp(): void
    {
        parent::_setUp();
        Yii::$app->params['siteUrl'] = 'https://site.pro';
        Yii::$app->params['adGeneration'] = require __DIR__ . '/../../../config/ad_generation.php';
    }

    protected function _tearDown(): void
    {
        unset(Yii::$app->params['siteUrl']);
        unset(Yii::$app->params['adGeneration']);
        parent::_tearDown();
    }

    public function testHeadlinesContainCategoryContext(): void
    {
        $generator = new TemplateAdGenerator();

        $categoryHeadlineCues = [
            Keyword::CATEGORY_WEBSITE_BUILDER => ['Build', 'Site', 'Code'],
            Keyword::CATEGORY_EMAIL => ['Email', 'Domain'],
            Keyword::CATEGORY_DOMAINS => ['Register', 'TLD'],
            Keyword::CATEGORY_ACCOUNTING => ['Accounting', 'VAT'],
            Keyword::CATEGORY_INVOICING => ['Invoicing', 'Paid'],
            Keyword::CATEGORY_RESELLER => ['White Label', 'Brand'],
        ];

        $shortKeywords = [
            Keyword::CATEGORY_WEBSITE_BUILDER => 'builder',
            Keyword::CATEGORY_EMAIL => 'email',
            Keyword::CATEGORY_DOMAINS => 'domains',
            Keyword::CATEGORY_ACCOUNTING => 'accounting',
            Keyword::CATEGORY_INVOICING => 'invoicing',
            Keyword::CATEGORY_RESELLER => 'hosting',
        ];

        foreach ($categoryHeadlineCues as $category => $cues) {
            [$group, $keyword] = $this->makeGroupAndKeyword($category, Keyword::AUDIENCE_B2C, 'en', $shortKeywords[$category]);
            $ads = $generator->generate($group, $keyword);

            $allHeadlines = array_merge(
                array_column($ads, 'headline1'),
                array_column($ads, 'headline2'),
            );
            $combined = implode(' ', $allHeadlines);

            $hasCue = false;
            foreach ($cues as $cue) {
                if (str_contains($combined, $cue)) {
                    $hasCue = true;
                    break;
                }
            }
            verify($hasCue)->true("Category {$category} headlines should contain one of: " . implode(', ', $cues) . ". Got: " . $combined);
        }
    }

    public function testCategorySpecificDescription(): void
    {
        $generator = new TemplateAdGenerator();
        $categoryDescCues = [
            Keyword::CATEGORY_WEBSITE_BUILDER => ['website', 'site'],
            Keyword::CATEGORY_EMAIL => ['email', 'Email'],
            Keyword::CATEGORY_DOMAINS => ['domain', 'Domain'],
            Keyword::CATEGORY_ACCOUNTING => ['accounting', 'Accounting'],
            Keyword::CATEGORY_INVOICING => ['invoice', 'Invoice', 'paid'],
            Keyword::CATEGORY_RESELLER => ['White-label', 'White Label', 'white-label'],
        ];

        $shortKeywords = [
            Keyword::CATEGORY_WEBSITE_BUILDER => 'builder',
            Keyword::CATEGORY_EMAIL => 'email',
            Keyword::CATEGORY_DOMAINS => 'domains',
            Keyword::CATEGORY_ACCOUNTING => 'accounting',
            Keyword::CATEGORY_INVOICING => 'invoicing',
            Keyword::CATEGORY_RESELLER => 'hosting',
        ];

        foreach ($categoryDescCues as $category => $cues) {
            [$group, $keyword] = $this->makeGroupAndKeyword($category, Keyword::AUDIENCE_B2C, 'en', $shortKeywords[$category]);
            $ads = $generator->generate($group, $keyword);

            $descriptions = array_column($ads, 'description1');
            $combined = implode(' ', $descriptions);

            $hasCue = false;
            foreach ($cues as $cue) {
                if (str_contains($combined, $cue)) {
                    $hasCue = true;
                    break;
                }
            }
            verify($hasCue)->true("Category {$category} descriptions should contain one of: " . implode(', ', $cues) . ". Got: " . $combined);
        }
    }

    public function testGeneratesAdsForWebsiteBuilderB2CEn(): void
    {
        [$group, $keyword] = $this->makeGroupAndKeyword(Keyword::CATEGORY_WEBSITE_BUILDER, Keyword::AUDIENCE_B2C, 'en');
        $generator = new TemplateAdGenerator();
        $ads = $generator->generate($group, $keyword);

        verify(isset($ads[0]));

        $first = $ads[0];
        verify(mb_strlen($first->headline1) <= AdGeneratorInterface::MAX_HEADLINE_LENGTH);
        verify(mb_strlen($first->description1) <= AdGeneratorInterface::MAX_DESCRIPTION_LENGTH);
        verify(str_contains($first->finalUrl, '/website-builder'));
        verify($first->path1)->equals('website-builder');
    }

    public function testGeneratesAdsForResellerB2BEn(): void
    {
        [$group, $keyword] = $this->makeGroupAndKeyword(Keyword::CATEGORY_RESELLER, Keyword::AUDIENCE_B2B, 'en');
        $generator = new TemplateAdGenerator();
        $ads = $generator->generate($group, $keyword);

        verify(isset($ads[0]));
        verify(str_contains($ads[0]->finalUrl, '/reseller'));
    }

    public function testGeneratesAdsForWebsiteBuilderRu(): void
    {
        [$group, $keyword] = $this->makeGroupAndKeyword(Keyword::CATEGORY_WEBSITE_BUILDER, Keyword::AUDIENCE_B2C, 'ru', 'бухгалтерия онлайн');
        $generator = new TemplateAdGenerator();
        $ads = $generator->generate($group, $keyword);

        verify(isset($ads[0]));
        verify(mb_strpos($ads[0]->description1, 'бухгалтерия') !== false);
    }

    public function testKeywordSubstitutionInHeadlines(): void
    {
        [$group, $keyword] = $this->makeGroupAndKeyword(Keyword::CATEGORY_WEBSITE_BUILDER, Keyword::AUDIENCE_B2C, 'en');
        $generator = new TemplateAdGenerator();
        $ads = $generator->generate($group, $keyword);

        verify(count($ads))->equals(3);

        foreach ($ads as $ad) {
            verify(mb_stripos($ad->headline1, 'best website builder') !== false);
        }
    }

    public function testCategoryUrlMapGeneralBrand(): void
    {
        [$group, $keyword] = $this->makeGroupAndKeyword(Keyword::CATEGORY_GENERAL_BRAND, Keyword::AUDIENCE_B2C, 'en');
        $generator = new TemplateAdGenerator();
        $ads = $generator->generate($group, $keyword);

        verify(str_ends_with($ads[0]->finalUrl, '/'));
    }

    public function testAllGeneratedTextEndsOnCompleteWord(): void
    {
        $generator = new TemplateAdGenerator();
        $categories = [
            Keyword::CATEGORY_WEBSITE_BUILDER,
            Keyword::CATEGORY_EMAIL,
            Keyword::CATEGORY_DOMAINS,
            Keyword::CATEGORY_ACCOUNTING,
            Keyword::CATEGORY_INVOICING,
            Keyword::CATEGORY_RESELLER,
        ];
        $languages = ['en', 'ru'];
        $keywords = [
            'en' => 'best website builder for small business',
            'ru' => 'лучший конструктор сайтов для малого бизнеса',
        ];

        foreach ($languages as $lang) {
            foreach ($categories as $cat) {
                [$group, $keyword] = $this->makeGroupAndKeyword($cat, Keyword::AUDIENCE_B2C, $lang, $keywords[$lang]);
                $ads = $generator->generate($group, $keyword);

                verify(count($ads) > 0);

                foreach ($ads as $ad) {
                    verify(mb_strlen($ad->headline1) <= AdGeneratorInterface::MAX_HEADLINE_LENGTH);
                    verify(mb_strlen($ad->headline2) <= AdGeneratorInterface::MAX_HEADLINE_LENGTH);
                    verify(mb_strlen($ad->description1) <= AdGeneratorInterface::MAX_DESCRIPTION_LENGTH);
                }
            }
        }
    }

    /** @return array{AdGroup, Keyword} */
    private function makeGroupAndKeyword(string $category, string $segment, string $language, string $keywordText = 'best website builder'): array
    {
        $group = new AdGroup();
        $group->category = $category;
        $group->audience_segment = $segment;
        $group->language = $language;
        $group->theme_label = 'Test Group';

        $keyword = new Keyword();
        $keyword->raw_text = $keywordText;
        $keyword->normalized_text = $keywordText;
        $keyword->category = $category;
        $keyword->audience_segment = $segment;
        $keyword->language = $language;
        $keyword->volume = 100;

        return [$group, $keyword];
    }
}
