<?php

declare(strict_types=1);

namespace common\components\pipeline;

use common\models\Ad;
use common\models\AdGroup;
use common\models\ExportBatch;
use Yii;

class ExportService
{
    private const string CAMPAIGN_PREFIX = 'site.pro';

    /**
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

        $exportDir = Yii::getAlias('@common/runtime/exports');
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

        fputcsv($handle, $headers);

        $adsCount = 0;
        $keywordCount = 0;

        foreach ($ads as $ad) {
            $group = AdGroup::findOne($ad->ad_group_id);
            if ($group === null) {
                continue;
            }

            $campaignName = self::CAMPAIGN_PREFIX . ' — ' . $group->category
                . ' — ' . $group->audience_segment
                . ' — ' . $group->language;

            $keywords = $group->getKeywords()->all();
            $keywordText = $keywords[0]->normalized_text ?? $keywords[0]->raw_text ?? '';
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

            fputcsv($handle, $row);
            $adsCount++;
        }

        fclose($handle);

        Ad::updateAll(
            ['status' => Ad::STATUS_EXPORTED],
            ['id' => $draftAdIds],
        );

        $batch = new ExportBatch();
        $batch->created_at = time();
        $batch->file_path = $filePath;
        $batch->ads_count = $adsCount;
        $batch->keywords_count = $keywordCount;
        $batch->save();

        return [$filePath, $adsCount, $keywordCount];
    }
}
