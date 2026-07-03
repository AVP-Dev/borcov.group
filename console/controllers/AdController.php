<?php

declare(strict_types=1);

namespace console\controllers;

use common\models\Ad;
use common\models\AdGroup;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Ad management commands.
 */
class AdController extends Controller
{
    private const int MAX_ADS_PER_GROUP = 3;

    /**
     * Deduplicate ads: keep only the 3 most recent ads per group, delete extras.
     */
    public function actionFixDuplicates(): int
    {
        $groups = AdGroup::find()->all();
        $totalDeleted = 0;
        $fixedGroups = 0;

        foreach ($groups as $group) {
            $ads = Ad::find()
                ->where(['ad_group_id' => $group->id])
                ->orderBy(['id' => SORT_ASC])
                ->all();

            $count = count($ads);
            if ($count <= self::MAX_ADS_PER_GROUP) {
                continue;
            }

            // Keep the newest MAX_ADS_PER_GROUP ads (last ones by id)
            $toDelete = array_slice($ads, 0, $count - self::MAX_ADS_PER_GROUP);
            $deletedIds = [];
            foreach ($toDelete as $ad) {
                $deletedIds[] = $ad->id;
                $ad->delete();
            }

            $totalDeleted += count($toDelete);
            $fixedGroups++;
            $this->stdout("Group #{$group->id} ({$group->theme_label}): {$count} ads → kept " . self::MAX_ADS_PER_GROUP . ", deleted " . count($toDelete) . " (IDs: " . implode(', ', $deletedIds) . ")\n");
        }

        if ($totalDeleted === 0) {
            $this->stdout("All groups have " . self::MAX_ADS_PER_GROUP . " or fewer ads. Nothing to fix.\n");
        } else {
            $this->stdout("Fixed {$fixedGroups} groups, deleted {$totalDeleted} duplicate ads.\n");
        }

        return ExitCode::OK;
    }
}
