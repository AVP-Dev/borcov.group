<?php

declare(strict_types=1);

namespace common\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use common\models\ImportBatch;
use common\models\Keyword;
use common\components\pipeline\NormalizationService;
use common\components\pipeline\CleaningService;
use common\components\pipeline\DeduplicationService;
use common\components\pipeline\VolumeFilterService;

class CleanJob extends BaseObject implements JobInterface
{
    public int $batchId;

    public function execute($queue): void
    {
        try {
            $this->doExecute($queue);
        } catch (\Throwable $e) {
            Yii::error("CleanJob #{$this->batchId} failed: " . $e->getMessage(), __METHOD__);
            throw $e;
        }
    }

    private function doExecute($queue): void
    {
        $batch = ImportBatch::findOne($this->batchId);
        if ($batch === null) {
            throw new \RuntimeException("ImportBatch #{$this->batchId} not found");
        }

        $keywords = Keyword::find()
            ->where(['batch_id' => $this->batchId, 'status' => Keyword::STATUS_RAW])
            ->all();

        $normalizer = new NormalizationService();
        $cleaner = new CleaningService();
        $stats = ['cleaned' => 0, 'rejected' => 0];

        foreach ($keywords as $keyword) {
            $keyword->normalized_text = $normalizer->normalize($keyword->raw_text);

            $result = $cleaner->clean($keyword);

            if ($result['passed']) {
                $keyword->status = Keyword::STATUS_CLEANED;
                $keyword->is_brand = $result['is_brand'];
                $stats['cleaned']++;
            } else {
                $keyword->status = Keyword::STATUS_REJECTED;
                $keyword->rejection_reason = $result['rejection_reason'];
                $keyword->is_brand = $result['is_brand'];
                $keyword->is_forbidden = $result['is_forbidden'];
                $stats['rejected']++;
            }

            $keyword->save();
        }

        $dedup = new DeduplicationService();
        $dedup->deduplicate($this->batchId);

        $volumeFilter = new VolumeFilterService();
        $volumeFilter->filter($this->batchId);

        Yii::$app->queue->push(new ClassificationJob([
            'batchId' => $this->batchId,
        ]));
    }
}
