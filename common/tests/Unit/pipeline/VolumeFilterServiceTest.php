<?php

declare(strict_types=1);

namespace common\tests\Unit\pipeline;

use Codeception\Test\Unit;
use common\components\pipeline\VolumeFilterService;
use common\models\ImportBatch;
use common\models\Keyword;
use common\models\Source;
use common\tests\Support\UnitTester;
use Yii;

final class VolumeFilterServiceTest extends Unit
{
    protected UnitTester $tester;

    public function testRejectsLowVolume(): void
    {
        $batchId = $this->createBatch();
        $this->createKeyword($batchId, 'low volume kw', 'low volume kw', 2, Keyword::STATUS_CLEANED);
        $this->createKeyword($batchId, 'high volume kw', 'high volume kw', 500, Keyword::STATUS_CLEANED);

        $service = new VolumeFilterService();
        $service->minVolume = 10;
        $rejected = $service->filter($batchId);

        verify($rejected)->equals(1);

        $low = Keyword::find()->where(['batch_id' => $batchId, 'volume' => 2])->one();
        verify($low->status)->equals(Keyword::STATUS_REJECTED);
        verify($low->rejection_reason)->stringContainsString('low_volume');

        $high = Keyword::find()->where(['batch_id' => $batchId, 'volume' => 500])->one();
        verify($high->status)->equals(Keyword::STATUS_CLEANED);
    }

    public function testKeepsLowVolumeInMultipleSources(): void
    {
        $batchId = $this->createBatch();
        $this->createKeyword($batchId, 'cross source kw', 'cross source kw', 2, Keyword::STATUS_CLEANED, $sourceId = 1);
        $this->createKeyword($batchId, 'cross source kw', 'cross source kw', 5, Keyword::STATUS_CLEANED, $sourceId = 2);
        $this->createKeyword($batchId, 'cross source kw', 'cross source kw', 3, Keyword::STATUS_CLEANED, $sourceId = 3);

        $service = new VolumeFilterService();
        $service->minVolume = 10;
        $service->minSourceCount = 3;
        $rejected = $service->filter($batchId);

        verify($rejected)->equals(0);

        $keywords = Keyword::find()->where(['batch_id' => $batchId])->all();
        foreach ($keywords as $kw) {
            verify($kw->status)->equals(Keyword::STATUS_CLEANED);
        }
    }

    private function createBatch(): int
    {
        $source = Source::findOne(['type' => Source::TYPE_GADS]);
        if ($source === null) {
            $source = new Source();
            $source->name = 'Volume Test Source';
            $source->type = Source::TYPE_GADS;
            $source->created_at = time();
            $source->save();
        }

        $batch = new ImportBatch();
        $batch->source_id = $source->id;
        $batch->filename = 'volume_test.csv';
        $batch->file_hash = 'volume_' . uniqid();
        $batch->imported_at = time();
        $batch->save();

        return (int) $batch->id;
    }

    private function createKeyword(int $batchId, string $rawText, string $normalizedText, ?int $volume, string $status, int $sourceId = 1): Keyword
    {
        $kw = new Keyword();
        $kw->batch_id = $batchId;
        $kw->source_id = $sourceId;
        $kw->raw_text = $rawText;
        $kw->normalized_text = $normalizedText;
        $kw->volume = $volume;
        $kw->status = $status;
        $kw->save();
        return $kw;
    }
}
