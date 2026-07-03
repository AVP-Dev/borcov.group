<?php

declare(strict_types=1);

namespace common\tests\Unit\pipeline;

use Codeception\Test\Unit;
use common\components\pipeline\DeduplicationService;
use common\models\ImportBatch;
use common\models\Keyword;
use common\models\Source;
use common\tests\Support\UnitTester;

final class DeduplicationServiceTest extends Unit
{
    protected UnitTester $tester;

    public function testFindsSimilarKeywords(): void
    {
        $batchId = $this->createBatch();
        $k1 = $this->createKeyword($batchId, 'website builder', 'website builder', 100);
        $k2 = $this->createKeyword($batchId, 'web site builder', 'web site builder', 50);
        $k3 = $this->createKeyword($batchId, 'email marketing', 'email marketing', 200);

        $service = new DeduplicationService();
        $service->setSimilarityThreshold(0.4);
        $duplicates = $service->deduplicate($batchId);

        verify($duplicates)->greaterThan(0);

        $k1->refresh();
        $k2->refresh();

        $rejected = ($k1->status === Keyword::STATUS_REJECTED) ? $k1 : $k2;
        $kept = ($k1->status !== Keyword::STATUS_REJECTED) ? $k1 : $k2;

        verify($rejected->status)->equals(Keyword::STATUS_REJECTED);
        verify($kept->status)->equals(Keyword::STATUS_RAW);
        verify($rejected->is_duplicate_of_id)->equals($kept->id);

        $k3->refresh();
        verify($k3->status)->equals(Keyword::STATUS_RAW);
    }

    public function testNoDuplicatesForDissimilarKeywords(): void
    {
        $batchId = $this->createBatch();
        $this->createKeyword($batchId, 'website builder', 'website builder', 100);
        $this->createKeyword($batchId, 'email marketing', 'email marketing', 200);
        $this->createKeyword($batchId, 'domain name', 'domain name', 150);

        $service = new DeduplicationService();
        $service->setSimilarityThreshold(0.9);
        $duplicates = $service->deduplicate($batchId);

        verify($duplicates)->equals(0);
    }

    private function createBatch(): int
    {
        $source = Source::findOne(['type' => Source::TYPE_GADS]);
        if ($source === null) {
            $source = new Source();
            $source->name = 'Dedup Test Source';
            $source->type = Source::TYPE_GADS;
            $source->created_at = time();
            $source->save();
        }

        $batch = new ImportBatch();
        $batch->source_id = $source->id;
        $batch->filename = 'dedup_test.csv';
        $batch->file_hash = 'dedup_' . uniqid();
        $batch->imported_at = time();
        $batch->save();

        return (int) $batch->id;
    }

    private function createKeyword(int $batchId, string $rawText, string $normalizedText, ?int $volume = null): Keyword
    {
        $kw = new Keyword();
        $kw->batch_id = $batchId;
        $kw->source_id = 1;
        $kw->raw_text = $rawText;
        $kw->normalized_text = $normalizedText;
        $kw->volume = $volume;
        $kw->status = Keyword::STATUS_RAW;
        $kw->save();
        return $kw;
    }
}
