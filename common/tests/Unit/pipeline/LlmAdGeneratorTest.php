<?php

declare(strict_types=1);

namespace common\tests\Unit\pipeline;

use Codeception\Test\Unit;
use common\components\pipeline\AdData;
use common\components\pipeline\AdGeneratorInterface;
use common\components\pipeline\LlmAdGenerator;
use common\components\pipeline\TemplateAdGenerator;
use common\models\AdGroup;
use common\models\Keyword;
use common\tests\Support\UnitTester;
use Yii;

final class LlmAdGeneratorTest extends Unit
{
    protected UnitTester $tester;
    private AdGroup $group;
    private Keyword $keyword;

    protected function _setUp(): void
    {
        parent::_setUp();
        Yii::$app->params['deepseekApiKey'] = 'test-key';
        Yii::$app->params['siteUrl'] = 'https://site.pro';

        $this->group = new AdGroup();
        $this->group->category = Keyword::CATEGORY_WEBSITE_BUILDER;
        $this->group->audience_segment = Keyword::AUDIENCE_B2C;
        $this->group->language = 'en';

        $this->keyword = new Keyword();
        $this->keyword->raw_text = 'best website builder';
        $this->keyword->normalized_text = 'best website builder';
        $this->keyword->category = Keyword::CATEGORY_WEBSITE_BUILDER;
        $this->keyword->language = 'en';
        $this->keyword->volume = 500;
    }

    protected function _tearDown(): void
    {
        unset(Yii::$app->params['deepseekApiKey']);
        unset(Yii::$app->params['siteUrl']);
        parent::_tearDown();
    }

    public function testFallsBackOnFailedHttpCall(): void
    {
        $httpMock = function (string $url, array $headers, int $timeout): array {
            return [0, ''];
        };

        $fallback = new TemplateAdGenerator();
        $generator = new LlmAdGenerator($fallback, $httpMock);
        $ads = $generator->generate($this->group, $this->keyword);

        verify(count($ads) > 0);
        verify($ads[0]->source)->equals(AdData::SOURCE_LLM_FALLBACK);
    }

    public function testFallsBackOnEmptyApiKey(): void
    {
        unset(Yii::$app->params['deepseekApiKey']);

        $httpMock = function (): array { return [200, '{}']; };
        $generator = new LlmAdGenerator(null, $httpMock);
        $ads = $generator->generate($this->group, $this->keyword);

        verify(count($ads) > 0);
        verify($ads[0]->source)->equals(AdData::SOURCE_LLM_FALLBACK);
    }

    public function testFallsBackOnNonJsonResponse(): void
    {
        $httpMock = function (string $url, array $headers, int $timeout): array {
            return [200, '<html>error</html>'];
        };

        $generator = new LlmAdGenerator(null, $httpMock);
        $ads = $generator->generate($this->group, $this->keyword);

        verify(count($ads) > 0);
        verify($ads[0]->source)->equals(AdData::SOURCE_LLM_FALLBACK);
    }

    public function testFallsBackOnTimeout(): void
    {
        $httpMock = function (string $url, array $headers, int $timeout): array {
            return [200, '{"choices":[{"message":{"content":"invalid"}}]}'];
        };

        $generator = new LlmAdGenerator(null, $httpMock);
        $ads = $generator->generate($this->group, $this->keyword);

        verify(count($ads) > 0);
        verify($ads[0]->source)->equals(AdData::SOURCE_LLM_FALLBACK);
    }

    public function testHappyPathWithSourceLlm(): void
    {
        $mockResponse = json_encode([
            'choices' => [
                [
                    'message' => [
                        'content' => '```json
[
  {
    "headline1": "Build Your Website Fast",
    "headline2": "Best Website Builder 2026",
    "headline3": "Try Free Today",
    "description1": "Create a professional website in minutes with site.pro. No coding required. Start your free trial now.",
    "description2": "Join 1M+ businesses using site.pro for their online presence.",
    "path1": "website-builder",
    "path2": "free-trial"
  }
]
```',
                    ],
                ],
            ],
        ]);

        $httpMock = function (string $url, array $headers, int $timeout) use ($mockResponse): array {
            return [200, $mockResponse];
        };

        $generator = new LlmAdGenerator(null, $httpMock);
        $ads = $generator->generate($this->group, $this->keyword);

        verify(count($ads) === 3);
        verify($ads[0]->source)->equals(AdData::SOURCE_LLM);
        verify($ads[1]->source)->equals(AdData::SOURCE_LLM_FALLBACK);
        verify($ads[2]->source)->equals(AdData::SOURCE_LLM_FALLBACK);
        verify($ads[0]->headline1)->stringContainsString('Website');
        verify(mb_strlen($ads[0]->headline1) <= AdGeneratorInterface::MAX_HEADLINE_LENGTH);
        verify(mb_strlen($ads[0]->description1) <= AdGeneratorInterface::MAX_DESCRIPTION_LENGTH);
        verify($ads[0]->path1)->stringContainsString('website-builder');
    }
}
