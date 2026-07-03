<?php

declare(strict_types=1);

namespace common\tests\Unit\pipeline;

use Codeception\Test\Unit;
use common\components\pipeline\CleaningService;
use common\models\BrandTerm;
use common\models\ForbiddenTerm;
use common\models\ImportBatch;
use common\models\Keyword;
use common\models\Source;
use common\tests\Support\UnitTester;

final class CleaningServiceTest extends Unit
{
    protected UnitTester $tester;

    protected function _setUp(): void
    {
        parent::_setUp();
        $this->seedBrandTerms();
        $this->seedForbiddenTerms();
    }

    public function testPassesValidKeyword(): void
    {
        $keyword = $this->makeKeyword('website builder', 'website builder');
        $service = new CleaningService();
        $result = $service->clean($keyword);

        verify($result['passed'])->true();
        verify($result['rejection_reason'])->null();
    }

    public function testRejectsTooShort(): void
    {
        $keyword = $this->makeKeyword('a', 'a');
        $service = new CleaningService();
        $result = $service->clean($keyword);

        verify($result['passed'])->false();
        verify($result['rejection_reason'])->stringContainsString('too_short');
    }

    public function testRejectsOnlyDigits(): void
    {
        $keyword = $this->makeKeyword('12345', '12345');
        $service = new CleaningService();
        $result = $service->clean($keyword);

        verify($result['passed'])->false();
        verify($result['rejection_reason'])->stringContainsString('only_digits');
    }

    public function testRejectsStopWord(): void
    {
        $keyword = $this->makeKeyword('free', 'free');
        $keyword2 = $this->makeKeyword('бесплатно', 'бесплатно');
        $service = new CleaningService();

        $result1 = $service->clean($keyword);
        verify($result1['passed'])->false();

        $result2 = $service->clean($keyword2);
        verify($result2['passed'])->false();
    }

    public function testDetectsCompetitorBrand(): void
    {
        $keyword = $this->makeKeyword('wix website builder', 'wix website builder');
        $service = new CleaningService();
        $result = $service->clean($keyword);

        verify($result['passed'])->false();
        verify($result['is_brand'])->true();
        verify($result['rejection_reason'])->stringContainsString('competitor_brand');
    }

    public function testPassesOwnBrand(): void
    {
        $keyword = $this->makeKeyword('site.pro builder', 'site.pro builder');
        $service = new CleaningService();
        $result = $service->clean($keyword);

        verify($result['passed'])->true();
        verify($result['is_brand'])->true();
    }

    public function testDetectsForbiddenExact(): void
    {
        $keyword = $this->makeKeyword('casino', 'casino');
        $service = new CleaningService();
        $result = $service->clean($keyword);

        verify($result['passed'])->false();
        verify($result['is_forbidden'])->true();
    }

    public function testDetectsForbiddenContains(): void
    {
        $keyword = $this->makeKeyword('best online casino games', 'best online casino games');
        $service = new CleaningService();
        $result = $service->clean($keyword);

        verify($result['passed'])->false();
        verify($result['is_forbidden'])->true();
    }

    public function testDetectsCompetitorBrandTypo(): void
    {
        $keyword = $this->makeKeyword('quarespace website builder', 'quarespace website builder');
        $service = new CleaningService();
        $service->brandFuzzyThreshold = 0.6;
        $result = $service->clean($keyword);

        verify($result['passed'])->false();
        verify($result['is_brand'])->true();
        verify($result['rejection_reason'])->stringContainsString('competitor_brand');
    }

    public function testDetectsAhrefsArtifact(): void
    {
        $keyword = $this->makeKeyword('/', '/');
        $service = new CleaningService();
        $result = $service->clean($keyword);

        verify($result['passed'])->false();
    }

    private function makeKeyword(string $rawText, string $normalizedText, int $batchId = 1): Keyword
    {
        $kw = new Keyword();
        $kw->batch_id = $batchId;
        $kw->source_id = 1;
        $kw->raw_text = $rawText;
        $kw->normalized_text = $normalizedText;
        $kw->status = Keyword::STATUS_RAW;
        return $kw;
    }

    private function seedBrandTerms(): void
    {
        if (BrandTerm::find()->count() > 0) {
            return;
        }
        $terms = [
            ['site.pro', true],
            ['sitepro', true],
            ['wix', false],
            ['tilda', false],
            ['wordpress', false],
            ['squarespace', false],
        ];
        foreach ($terms as [$term, $ownBrand]) {
            $t = new BrandTerm();
            $t->term = $term;
            $t->is_own_brand = $ownBrand;
            $t->save();
        }
    }

    private function seedForbiddenTerms(): void
    {
        if (ForbiddenTerm::find()->count() > 0) {
            return;
        }
        $terms = [
            ['casino', ForbiddenTerm::MATCH_CONTAINS, 'Gambling'],
            ['xxx', ForbiddenTerm::MATCH_CONTAINS, 'Adult content'],
            ['spam', ForbiddenTerm::MATCH_EXACT, 'Spam keyword'],
        ];
        foreach ($terms as [$term, $matchType, $reason]) {
            $t = new ForbiddenTerm();
            $t->term = $term;
            $t->match_type = $matchType;
            $t->reason = $reason;
            $t->save();
        }
    }
}
