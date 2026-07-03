<?php

declare(strict_types=1);

namespace common\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use common\models\ImportBatch;
use common\models\Keyword;
use common\components\pipeline\ClassificationService;

class ClassificationJob extends BaseObject implements JobInterface
{
    public int $batchId;

    public function execute($queue): void
    {
        try {
            $this->doExecute($queue);
        } catch (\Throwable $e) {
            Yii::error("ClassificationJob #{$this->batchId} failed: " . $e->getMessage(), __METHOD__);
            throw $e;
        }
    }

    private function doExecute($queue): void
    {
        $batch = ImportBatch::findOne($this->batchId);
        if ($batch === null) {
            throw new \RuntimeException("ImportBatch #{$this->batchId} not found");
        }

        $classifier = new ClassificationService();

        $keywords = Keyword::find()
            ->where(['batch_id' => $this->batchId, 'status' => Keyword::STATUS_CLEANED])
            ->all();

        foreach ($keywords as $keyword) {
            $result = $classifier->classify($keyword);
            $keyword->category = $result['category'];
            $keyword->intent = $result['intent'];
            $keyword->audience_segment = $result['audience'];
            $keyword->status = Keyword::STATUS_READY;
            $keyword->save();
        }
    }
}
