<?php

declare(strict_types=1);

namespace common\tests\Unit\pipeline;

use Codeception\Test\Unit;
use common\components\pipeline\GapAnalysisService;
use common\models\BrandTerm;
use common\models\ImportBatch;
use common\models\Source;
use common\models\Keyword;
use common\tests\Support\UnitTester;

final class GapAnalysisServiceTest extends Unit
{
    protected UnitTester $tester;
    private int $ahrefsPaidId;
    private int $gadsId;
    private int $scId;

    protected function _setUp(): void
    {
        parent::_setUp();
        $this->seedSources();
        $this->seedBatch();
        $this->seedBrandTerms();
        $this->seedKeywords();
    }

    public function testFindsGapCandidate(): void
    {
        $service = new GapAnalysisService();
        $service->minVolume = 10;
        $result = $service->analyze();

        $texts = array_column($result, 'normalized_text');
        verify(in_array('competitor exclusive tool', $texts))->true();
    }

    public function testExcludesExistingKeyword(): void
    {
        $service = new GapAnalysisService();
        $service->minVolume = 10;
        $result = $service->analyze();

        $texts = array_column($result, 'normalized_text');
        verify(in_array('best website builder', $texts))->false();
    }

    public function testExcludesFuzzyMatch(): void
    {
        $service = new GapAnalysisService();
        $service->minVolume = 10;
        $result = $service->analyze();

        $texts = array_column($result, 'normalized_text');
        verify(in_array('sitebuilder', $texts))->false();
    }

    public function testFiltersLowVolume(): void
    {
        $service = new GapAnalysisService();
        $service->minVolume = 10;
        $result = $service->analyze();

        $texts = array_column($result, 'normalized_text');
        verify(in_array('niche tool', $texts))->false();
    }

    public function testExcludesCompetitorBrand(): void
    {
        $service = new GapAnalysisService();
        $service->minVolume = 10;
        $result = $service->analyze();

        $texts = array_column($result, 'normalized_text');
        verify(in_array('wix конструктор', $texts))->false();
        verify(in_array('tilda конструктор', $texts))->false();
    }

    public function testResultStructure(): void
    {
        $service = new GapAnalysisService();
        $service->minVolume = 10;
        $result = $service->analyze();

        if ($result === []) {
            verify(true)->true();
            return;
        }
        $row = $result[0];
        verify(array_key_exists('id', $row))->true();
        verify(array_key_exists('raw_text', $row))->true();
        verify(array_key_exists('normalized_text', $row))->true();
        verify(array_key_exists('volume', $row))->true();
        verify(array_key_exists('category', $row))->true();
        verify(array_key_exists('intent', $row))->true();
        verify(array_key_exists('language', $row))->true();
    }

    private function seedSources(): void
    {
        $sources = [
            ['ahrefs_paid', 'Ahrefs Paid'],
            ['gads', 'Google Ads'],
            ['search_console', 'Search Console'],
            ['ahrefs_organic', 'Ahrefs Organic'],
        ];

        foreach ($sources as [$type, $name]) {
            $existing = Source::findOne(['type' => $type]);
            if ($existing !== null) {
                $$type = $existing->id;
                continue;
            }
            $s = new Source();
            $s->name = $name;
            $s->type = $type;
            $s->created_at = time();
            $s->save();
        }

        $this->ahrefsPaidId = Source::findOne(['type' => 'ahrefs_paid'])->id;
        $this->gadsId = Source::findOne(['type' => 'gads'])->id;
        $this->scId = Source::findOne(['type' => 'search_console'])->id;
    }

    private function seedBrandTerms(): void
    {
        if (BrandTerm::find()->count() > 0) {
            return;
        }
        foreach ([
            ['wix', false],
            ['tilda', false],
            ['site.pro', true],
        ] as [$term, $isOwn]) {
            $bt = new BrandTerm();
            $bt->term = $term;
            $bt->is_own_brand = $isOwn;
            $bt->save();
        }
    }

    private function seedBatch(): void
    {
        if (ImportBatch::findOne(1) !== null) {
            return;
        }
        $b = new ImportBatch();
        $b->id = 1;
        $b->source_id = $this->ahrefsPaidId;
        $b->filename = 'test.csv';
        $b->file_hash = 'test';
        $b->imported_at = time();
        $b->status = ImportBatch::STATUS_DONE;
        $b->save();
    }

    private function seedKeywords(): void
    {
        if (Keyword::find()->count() > 0) {
            return;
        }

        $batchId = 1;

        $keywords = [
            // Ahrefs Paid — competitor keywords (gap candidates)
            ['competitor exclusive tool', 500, $this->ahrefsPaidId, $batchId, Keyword::STATUS_READY],
            ['sitebuilder', 200, $this->ahrefsPaidId, $batchId, Keyword::STATUS_READY],
            ['niche tool', 5, $this->ahrefsPaidId, $batchId, Keyword::STATUS_READY],

            // Competitor brand keywords must NOT appear in gap candidates
            ['wix конструктор', 22000, $this->ahrefsPaidId, $batchId, Keyword::STATUS_REJECTED],
            ['tilda конструктор', 15000, $this->ahrefsPaidId, $batchId, Keyword::STATUS_REJECTED],

            // GAds — keywords site.pro already targets
            ['best website builder', 1000, $this->gadsId, $batchId, Keyword::STATUS_READY],
            ['site builder', 800, $this->gadsId, $batchId, Keyword::STATUS_READY],

            // Search Console — organic traffic
            ['buy cheap domain', 300, $this->scId, $batchId, Keyword::STATUS_READY],
        ];

        foreach ($keywords as [$text, $volume, $sourceId, $batchId, $status]) {
            $kw = new Keyword();
            $kw->batch_id = $batchId;
            $kw->source_id = $sourceId;
            $kw->raw_text = $text;
            $kw->normalized_text = $text;
            $kw->volume = $volume;
            $kw->status = $status;
            $kw->save();
        }
    }
}
