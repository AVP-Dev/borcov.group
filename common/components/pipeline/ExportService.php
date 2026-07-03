<?php

declare(strict_types=1);

namespace common\components\pipeline;

use common\models\Ad;
use common\models\AdGroup;
use common\models\ExportBatch;
use Yii;

class ExportService
{
    public const EVENT_AFTER_EXPORT = 'afterExport';

    private function getCampaignPrefix(): string
    {
        $config = require Yii::getAlias('@common/config/ad_generation.php');
        return $config['brand_name'] ?? 'site.pro';
    }

    /**
     * Export all ads from selected ad groups.
     * @param int[] $groupIds
     * @return array{string, int, int} [filePath, adsCount, keywordsCount]
     */
    public function exportGroups(array $groupIds): array
    {
        $ads = Ad::find()
            ->where(['ad_group_id' => $groupIds])
            ->orderBy(['ad_group_id' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        if ($ads === []) {
            return ['', 0, 0];
        }

        return $this->doExport($ads);
    }

    /**
     * @param int[] $adIds
     * @return array{string, int, int} [filePath, adsCount, keywordsCount]
     */
    public function exportSelected(array $adIds): array
    {
        $ads = Ad::find()
            ->where(['id' => $adIds])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        if ($ads === []) {
            return ['', 0, 0];
        }

        return $this->doExport($ads);
    }

    /**
     * Export all draft ads.
     * @return array{string, int, int} [filePath, adsCount, keywordsCount]
     */
    public function export(): array
    {
        $draftAdIds = Ad::find()
            ->select('id')
            ->where(['status' => Ad::STATUS_DRAFT])
            ->column();

        if ($draftAdIds === []) {
            return ['', 0, 0];
        }

        $ads = Ad::find()
            ->where(['id' => $draftAdIds])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        return $this->doExport($ads);
    }

    /**
     * @param Ad[] $ads
     * @return array{string, int, int} [filePath, adsCount, keywordsCount]
     */
    private function doExport(array $ads): array
    {

        $exportDir = Yii::getAlias('@backend/runtime/exports');
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0775, true);
        }

        $filename = 'google_ads_export_' . date('Y-m-d_His') . '.csv';
        $filePath = $exportDir . '/' . $filename;

        $handle = fopen($filePath, 'w');
        if ($handle === false) {
            throw new \RuntimeException('Failed to create export file: ' . $filePath);
        }

        $headers = [
            'Campaign',
            'Campaign Type',
            'Campaign Daily Budget',
            'Networks',
            'Keyword',
            'Match Type',
        ];
        for ($i = 1; $i <= 15; $i++) {
            $headers[] = "Headline {$i}";
        }
        for ($i = 1; $i <= 4; $i++) {
            $headers[] = "Description {$i}";
        }
        $headers[] = 'Final URL';
        $headers[] = 'Path 1';
        $headers[] = 'Path 2';

        fputcsv($handle, $headers, escape: '');

        $adsCount = 0;
        $keywordCount = 0;

        foreach ($ads as $ad) {
            $group = AdGroup::findOne($ad->ad_group_id);
            if ($group === null) {
                continue;
            }

            $campaignName = $this->getCampaignPrefix() . ' — ' . $group->category
                . ' — ' . $group->audience_segment
                . ' — ' . $group->language;

            $keywords = $group->getKeywords()->all();
            if ($keywords === []) {
                Yii::warning("AdGroup #{$group->id} has no keywords, skipping ad #{$ad->id}", __METHOD__);
                continue;
            }
            $firstKeyword = $keywords[0];
            $keywordText = $firstKeyword->normalized_text ?? $firstKeyword->raw_text ?? '';
            $keywordCount += count($keywords);

            $row = [
                $campaignName,
                'Search',
                '',
                'Google search',
                $keywordText,
                'Broad',
            ];

            for ($i = 1; $i <= 15; $i++) {
                $row[] = $ad->getAttribute("headline_{$i}") ?? '';
            }
            for ($i = 1; $i <= 4; $i++) {
                $row[] = $ad->getAttribute("description_{$i}") ?? '';
            }

            $row[] = $ad->final_url;
            $row[] = $ad->path_1 ?? '';
            $row[] = $ad->path_2 ?? '';

            fputcsv($handle, $row, escape: '');
            $adsCount++;
        }

        fclose($handle);

        $batch = new ExportBatch();
        $batch->created_at = time();
        $batch->file_path = $filePath;
        $batch->ads_count = $adsCount;
        $batch->keywords_count = $keywordCount;
        $batch->save();

        \yii\base\Event::trigger(self::class, self::EVENT_AFTER_EXPORT, new \yii\base\Event(['sender' => $batch]));

        return [$filePath, $adsCount, $keywordCount];
    }

    /**
     * Get ad groups with ad counts, ordered by category.
     * @return array<int, array{group: AdGroup, total: int, draft: int, exported: int}>
     */
    public static function getGroupedStats(): array
    {
        $groups = AdGroup::find()->orderBy(['category' => SORT_ASC, 'language' => SORT_ASC])->all();
        $result = [];

        foreach ($groups as $group) {
            $total = Ad::find()->where(['ad_group_id' => $group->id])->count();
            $draft = Ad::find()->where(['ad_group_id' => $group->id, 'status' => Ad::STATUS_DRAFT])->count();
            $exported = Ad::find()->where(['ad_group_id' => $group->id, 'status' => Ad::STATUS_EXPORTED])->count();

            $result[$group->id] = [
                'group' => $group,
                'total' => (int)$total,
                'draft' => (int)$draft,
                'exported' => (int)$exported,
            ];
        }

        return $result;
    }

    /**
     * Reset exported ads in given groups back to draft.
     * @param int[] $groupIds
     * @return int number of ads reset
     */
    public static function resetGroupsToDraft(array $groupIds): int
    {
        return (int) Ad::updateAll(
            ['status' => Ad::STATUS_DRAFT],
            ['ad_group_id' => $groupIds, 'status' => Ad::STATUS_EXPORTED],
        );
    }
}
