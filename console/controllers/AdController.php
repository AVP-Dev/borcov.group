<?php

declare(strict_types=1);

namespace console\controllers;

use common\components\pipeline\GroupingService;
use common\components\pipeline\TemplateAdGenerator;
use common\models\Ad;
use common\models\AdGroup;
use Yii;
use yii\console\Controller;

class AdController extends Controller
{
    public function actionFillMissing(): void
    {
        $groups = AdGroup::find()->all();
        $fixed = 0;

        foreach ($groups as $group) {
            $count = $group->getAds()->count();
            if ($count >= 3) {
                continue;
            }
            $this->stdout("Group #{$group->id} \"{$group->theme_label}\": {$count} ads, regenerating...\n");
            $service = new GroupingService(new TemplateAdGenerator());
            $created = $service->regenerateForGroup((int)$group->id);
            $this->stdout("  -> {$created} ads created\n");
            $fixed++;
        }

        $this->stdout("Done. {$fixed} groups fixed.\n");
    }
}
