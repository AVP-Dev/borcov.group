<?php

declare(strict_types=1);

namespace common\components\pipeline;

use Yii;
use yii\base\Component;
use common\models\Source;
use common\models\Keyword;

class GapAnalysisService extends Component
{
    public float $similarityThreshold = 0.6;
    public int $minVolume = 10;

    public function analyze(): array
    {
        $ahrefsPaid = Source::findOne(['type' => Source::TYPE_AHREFS_PAID]);
        $gads = Source::findOne(['type' => Source::TYPE_GADS]);
        $searchConsole = Source::findOne(['type' => Source::TYPE_SEARCH_CONSOLE]);

        if ($ahrefsPaid === null || $gads === null || $searchConsole === null) {
            return [];
        }

        return $this->findGapCandidates($ahrefsPaid->id, [$gads->id, $searchConsole->id]);
    }

    private function findGapCandidates(int $ahrefsId, array $existingIds): array
    {
        $placeholders = [];
        $params = [
            ':ahrefsId' => $ahrefsId,
            ':minVolume' => $this->minVolume,
            ':simThreshold' => $this->similarityThreshold,
        ];

        foreach ($existingIds as $i => $id) {
            $key = ":existingId{$i}";
            $placeholders[] = $key;
            $params[$key] = $id;
        }

        $existingList = implode(', ', $placeholders);

        return Yii::$app->db->createCommand("
            SELECT a.id, a.raw_text, a.normalized_text,
                   a.volume, a.category, a.intent,
                   a.language, a.audience_segment,
                   COALESCE(s.name, 'ahrefs_paid') AS source_name
            FROM {{%keywords}} a
            LEFT JOIN {{%sources}} s ON s.id = a.source_id
            WHERE a.source_id = :ahrefsId
              AND (a.volume IS NULL OR a.volume >= :minVolume)
              AND NOT EXISTS (
                SELECT 1 FROM {{%keywords}} e
                WHERE e.source_id IN ({$existingList})
                  AND similarity(a.normalized_text, e.normalized_text) > :simThreshold
              )
            ORDER BY a.volume DESC NULLS LAST
            LIMIT 500
        ", $params)->queryAll();
    }
}
