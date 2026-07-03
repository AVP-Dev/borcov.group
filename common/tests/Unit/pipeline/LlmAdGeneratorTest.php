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
        Yii::$app->params['adGeneration'] = require __DIR__ . '/../../../config/ad_generation.php';

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
        unset(Yii::$app->params['adGeneration']);
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
    "description1": "Create a professional website in minutes with site.pro. Start your free trial now.",
    "description2": "Join 1M+ businesses using site.pro.",
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

    public function testPromptIncludesBrandContext(): void
    {
        $capturedPayload = '';
        $httpMock = function (string $url, array $headers, int $timeout) use (&$capturedPayload): array {
            $capturedPayload = $headers['__body__'] ?? '';
            return [200, '{}'];
        };

        $generator = new LlmAdGenerator(null, $httpMock);
        $generator->generate($this->group, $this->keyword);

        $payload = json_decode($capturedPayload, true);
        $prompt = $payload['messages'][1]['content'] ?? '';
        verify($prompt)->stringContainsString('site.pro');
        verify($prompt)->stringContainsString('online business platform');
    }

    public function testPromptIncludesCategoryContext(): void
    {
        $capturedPayload = '';
        $httpMock = function (string $url, array $headers, int $timeout) use (&$capturedPayload): array {
            $capturedPayload = $headers['__body__'] ?? '';
            return [200, '{}'];
        };

        $this->group->category = Keyword::CATEGORY_EMAIL;
        $generator = new LlmAdGenerator(null, $httpMock);
        $generator->generate($this->group, $this->keyword);

        $payload = json_decode($capturedPayload, true);
        $prompt = $payload['messages'][1]['content'] ?? '';
        verify($prompt)->stringContainsString('email');
        verify($prompt)->stringContainsString('IMAP');
    }

    public function testPromptUsesLanguage(): void
    {
        $capturedPayload = '';
        $httpMock = function (string $url, array $headers, int $timeout) use (&$capturedPayload): array {
            $capturedPayload = $headers['__body__'] ?? '';
            return [200, '{}'];
        };

        $this->group->language = 'ru';
        $this->keyword->language = 'ru';
        $generator = new LlmAdGenerator(null, $httpMock);
        $generator->generate($this->group, $this->keyword);

        $payload = json_decode($capturedPayload, true);
        $prompt = $payload['messages'][1]['content'] ?? '';
        verify($prompt)->stringContainsString('ru');
    }

    public function testPromptIncludesAudienceSegment(): void
    {
        $capturedPayload = '';
        $httpMock = function (string $url, array $headers, int $timeout) use (&$capturedPayload): array {
            $capturedPayload = $headers['__body__'] ?? '';
            return [200, '{}'];
        };

        $this->group->audience_segment = Keyword::AUDIENCE_B2B;
        $generator = new LlmAdGenerator(null, $httpMock);
        $generator->generate($this->group, $this->keyword);

        $payload = json_decode($capturedPayload, true);
        $prompt = $payload['messages'][1]['content'] ?? '';
        verify(mb_stripos($prompt, 'b2b') !== false);
    }

    public function testSystemMessageIsEnriched(): void
    {
        $capturedPayload = '';
        $httpMock = function (string $url, array $headers, int $timeout) use (&$capturedPayload): array {
            $capturedPayload = $headers['__body__'] ?? '';
            return [200, '{}'];
        };

        $generator = new LlmAdGenerator(null, $httpMock);
        $generator->generate($this->group, $this->keyword);

        $payload = json_decode($capturedPayload, true);
        $systemMsg = $payload['messages'][0]['content'] ?? '';
        verify($systemMsg)->stringContainsString('SaaS');
    }

    public function testLongDescriptionPassesFullTextToModel(): void
    {
        $longDesc = 'Launch your professional website quickly with Site.pro. No coding skills required. Start building your online presence today with our easy drag-and-drop tools.';
        verify(mb_strlen($longDesc))->greaterThan(90);

        $mockResponse = json_encode([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            [
                                'headline1' => 'Build Your Website Fast',
                                'headline2' => 'Best Website Builder 2026',
                                'description1' => $longDesc,
                                'path1' => 'website-builder',
                            ],
                        ]),
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
        // Generator passes full text without mid-word truncation — model handles it
        verify($ads[0]->description1)->equals($longDesc);
        verify($ads[0]->description1)->stringContainsString('drag-and-drop');
    }
}
