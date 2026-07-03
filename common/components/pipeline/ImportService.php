<?php

declare(strict_types=1);

namespace common\components\pipeline;

use Yii;
use yii\base\Component;
use common\models\Source;
use common\models\ImportBatch;
use common\jobs\ImportJob;

class ImportService extends Component
{
    public function import(string $filePath, string $sourceType): ImportBatch
    {
        $source = Source::findOne(['type' => $sourceType]);
        if ($source === null) {
            throw new \InvalidArgumentException(Yii::t('app', 'import.error.unknown_source', ['type' => $sourceType]));
        }

        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \RuntimeException(Yii::t('app', 'import.error.invalid_file'));
        }

        $hash = hash_file('sha256', $filePath);
        if ($hash === false) {
            throw new \RuntimeException(Yii::t('app', 'import.error.cannot_hash'));
        }

        $existing = ImportBatch::findOne(['file_hash' => $hash]);
        if ($existing !== null) {
            if ($existing->status === ImportBatch::STATUS_FAILED) {
                // Re-process failed batch: reset and push new job
                $existing->status = ImportBatch::STATUS_PROCESSING;
                $existing->error_message = null;
                $existing->rows_total = 0;
                $existing->rows_accepted = 0;
                $existing->rows_rejected = 0;
                $existing->save();
                \Yii::$app->queue->push(new \common\jobs\ImportJob([
                    'batchId' => $existing->id,
                    'filePath' => $filePath,
                ]));
            }
            return $existing;
        }

        $batch = new ImportBatch();
        $batch->source_id = $source->id;
        $batch->filename = basename($filePath);
        $batch->file_hash = $hash;
        $batch->imported_at = time();
        $batch->status = ImportBatch::STATUS_PROCESSING;

        if (!$batch->save()) {
            $errors = json_encode($batch->getErrors(), JSON_UNESCAPED_UNICODE);
            throw new \RuntimeException(Yii::t('app', 'import.error.save_batch') . ': ' . $errors);
        }

        Yii::$app->queue->push(new ImportJob([
            'batchId' => (int)$batch->id,
            'filePath' => $filePath,
        ]));

        return $batch;
    }
}
