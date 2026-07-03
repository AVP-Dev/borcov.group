<?php

declare(strict_types=1);

namespace common\components\pipeline;

use common\models\Keyword;
use common\models\Setting;
use Yii;
use yii\base\Component;

class VolumeFilterService extends Component
{
    public int $minVolume = 10;
    public int $minSourceCount = 3;

    public function __construct($config = [])
    {
        parent::__construct($config);
        try {
            $dbVolume = Setting::get('pipeline.volume.min');
            $dbSources = Setting::get('pipeline.volume.min_source_count');
            if ($dbVolume !== '') {
                $this->minVolume = (int)$dbVolume;
            }
            if ($dbSources !== '') {
                $this->minSourceCount = (int)$dbSources;
            }
        } catch (\Exception $e) {
            // settings table may not exist yet (e.g. in tests or first deploy)
        }
    }

    public function filter(int $batchId): int
    {
        $db = Yii::$app->db;
        $rejected = 0;

        $lowVolume = $db->createCommand("
            SELECT k.id, k.normalized_text
            FROM {{%keywords}} k
            WHERE k.batch_id = :batchId
              AND k.status = 'cleaned'
              AND k.volume IS NOT NULL
              AND k.volume < :minVolume
        ", [
            ':batchId' => $batchId,
            ':minVolume' => $this->minVolume,
        ])->queryAll();

        foreach ($lowVolume as $row) {
            $sourceCount = (int) $db->createCommand("
                SELECT COUNT(DISTINCT source_id)
                FROM {{%keywords}}
                WHERE normalized_text = :text
                  AND batch_id = :batchId
            ", [
                ':text' => $row['normalized_text'],
                ':batchId' => $batchId,
            ])->queryScalar();

            if ($sourceCount >= $this->minSourceCount) {
                continue;
            }

            $db->createCommand()->update('{{%keywords}}', [
                'status' => Keyword::STATUS_REJECTED,
                'rejection_reason' => Yii::t('app', 'clean.reason.low_volume'),
            ], ['id' => $row['id']])->execute();
            $rejected++;
        }

        return $rejected;
    }
}
