<?php

declare(strict_types=1);

namespace common\components\pipeline;

use common\models\Keyword;
use common\models\Setting;
use Yii;
use yii\base\Component;

class DeduplicationService extends Component
{
    public float $similarityThreshold = 0.6;

    public function __construct($config = [])
    {
        parent::__construct($config);
        try {
            $dbValue = Setting::get('pipeline.dedup.similarity_threshold');
            if ($dbValue !== '') {
                $this->similarityThreshold = (float)$dbValue;
            }
        } catch (\Exception $e) {
            // settings table may not exist yet (e.g. in tests or first deploy)
        }
    }

    public function deduplicate(int $batchId): int
    {
        $db = Yii::$app->db;
        $duplicates = 0;

        $pairs = $db->createCommand("
            SELECT k1.id AS id1, k2.id AS id2,
                   k1.normalized_text AS text1, k2.normalized_text AS text2,
                   similarity(k1.normalized_text, k2.normalized_text) AS sim
            FROM {{%keywords}} k1
            JOIN {{%keywords}} k2
                ON k1.batch_id = :batchId
                AND k2.batch_id = :batchId
                AND k1.id < k2.id
                AND k1.status IN ('raw', 'cleaned')
                AND k2.status IN ('raw', 'cleaned')
                AND similarity(k1.normalized_text, k2.normalized_text) > :threshold
            ORDER BY sim DESC
        ", [
            ':batchId' => $batchId,
            ':threshold' => $this->similarityThreshold,
        ])->queryAll();

        $processed = [];
        foreach ($pairs as $pair) {
            if (isset($processed[$pair['id1']]) || isset($processed[$pair['id2']])) {
                continue;
            }

            $k1 = Keyword::findOne($pair['id1']);
            $k2 = Keyword::findOne($pair['id2']);
            if ($k1 === null || $k2 === null) {
                continue;
            }

            $keep = ($k2->volume ?: 0) > ($k1->volume ?: 0) ? $k2 : $k1;
            $drop = $keep->id === $k1->id ? $k2 : $k1;

            $drop->is_duplicate_of_id = $keep->id;
            $drop->status = Keyword::STATUS_REJECTED;
            $drop->rejection_reason = Yii::t('app', 'clean.reason.duplicate');
            if ($drop->save()) {
                $duplicates++;
            }

            $processed[$pair['id1']] = true;
            $processed[$pair['id2']] = true;
        }

        return $duplicates;
    }

    public function setSimilarityThreshold(float $threshold): void
    {
        $this->similarityThreshold = $threshold;
    }
}
