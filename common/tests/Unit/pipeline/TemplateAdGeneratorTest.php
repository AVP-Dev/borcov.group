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
