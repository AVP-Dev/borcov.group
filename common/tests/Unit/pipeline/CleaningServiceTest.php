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
use Yii;

final class CleaningServiceTest extends Unit
{
    protected UnitTester $tester;
    private int $gadsSourceId;
    private int $ahrefsOrganicSourceId;
    private int $ahrefsPaidSourceId;

    protected function _setUp(): void
    {
        parent::_setUp();
        $this->seedBrandTerms();
        $this->seedForbiddenTerms();
        $this->seedSources();
        $this->seedBatch(1);
        $this->seedBatch(2);
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

    public function testAlreadyUsedDetectsGadsKeyword(): void
    {
        $existing = new Keyword();
        $existing->batch_id = 1;
        $existing->source_id = $this->gadsSourceId;
        $existing->raw_text = 'website builder';
        $existing->normalized_text = 'website builder';
        $existing->status = Keyword::STATUS_CLEANED;
        $existing->save();

        $keyword = $this->makeKeyword('website builder', 'website builder', 2);
        $service = new CleaningService();
        $result = $service->clean($keyword);

        verify($result['passed'])->false();
        verify($result['rejection_reason'])->stringContainsString('already_used');
    }

    public function testNonGadsKeywordDoesNotTriggerAlreadyUsed(): void
    {
        $existing = new Keyword();
        $existing->batch_id = 1;
        $existing->source_id = $this->ahrefsOrganicSourceId;
        $existing->raw_text = 'website builder';
        $existing->normalized_text = 'website builder';
        $existing->status = Keyword::STATUS_CLEANED;
        $existing->save();

        $keyword = $this->makeKeyword('website builder', 'website builder', 2, $this->ahrefsPaidSourceId);
        $service = new CleaningService();
        $result = $service->clean($keyword);

        verify($result['passed'])->true();
        verify($result['rejection_reason'])->null();
    }

    public function testAlreadyUsedIgnoresDifferentText(): void
    {
        $existing = new Keyword();
        $existing->batch_id = 1;
        $existing->source_id = $this->gadsSourceId;
        $existing->raw_text = 'email marketing';
        $existing->normalized_text = 'email marketing';
        $existing->status = Keyword::STATUS_CLEANED;
        $existing->save();

        $keyword = $this->makeKeyword('website builder', 'website builder', 2);
        $service = new CleaningService();
        $result = $service->clean($keyword);

        verify($result['passed'])->true();
        verify($result['rejection_reason'])->null();
    }

    private function makeKeyword(string $rawText, string $normalizedText, int $batchId = 1, ?int $sourceId = null): Keyword
    {
        $kw = new Keyword();
        $kw->batch_id = $batchId;
        $kw->source_id = $sourceId ?? $this->gadsSourceId;
        $kw->raw_text = $rawText;
        $kw->normalized_text = $normalizedText;
        $kw->status = Keyword::STATUS_RAW;
        return $kw;
    }

    private function seedSources(): void
    {
        $gads = Source::findOne(['type' => Source::TYPE_GADS]);
        if ($gads !== null) {
            $this->gadsSourceId = $gads->id;
        } else {
            $gads = new Source();
            $gads->name = 'Google Ads';
            $gads->type = Source::TYPE_GADS;
            $gads->created_at = time();
            $gads->save();
            $this->gadsSourceId = $gads->id;
        }

        $this->ahrefsOrganicSourceId = Source::findOne(['type' => Source::TYPE_AHREFS_ORGANIC])?->id ?? 0;
        $this->ahrefsPaidSourceId = Source::findOne(['type' => Source::TYPE_AHREFS_PAID])?->id ?? 0;
    }

    private function seedBatch(int $id = 1): void
    {
        if (ImportBatch::findOne($id) !== null) {
            return;
        }
        $b = new ImportBatch();
        $b->id = $id;
        $b->source_id = $this->gadsSourceId;
        $b->filename = "already_used_test_{$id}.csv";
        $b->file_hash = "already_used_test_{$id}";
        $b->imported_at = time();
        $b->status = ImportBatch::STATUS_DONE;
        $b->save();
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
