<?php

declare(strict_types=1);

/**
 * @var \yii\web\View $this
 * @var \yii\data\ActiveDataProvider $dataProvider
 * @var bool $deepseekAvailable
 */

use yii\helpers\Html;
use yii\grid\GridView;
use common\models\AdGroup;

$this->title = Yii::t('app', 'ad_groups.title');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ad-groups-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
        <div>
            <?php if ($deepseekAvailable): ?>
                <span class="badge bg-info me-2"><?= Yii::t('app', 'ad_groups.generator_ai_available') ?></span>
            <?php endif; ?>
            <?= Html::a(
                Yii::t('app', 'ad_groups.generate_btn'),
                ['generate'],
                ['class' => 'btn btn-primary', 'data-method' => 'post'],
            ) ?>
        </div>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'theme_label',
            'category',
            'audience_segment',
            'language',
            'target_url',
            [
                'label' => Yii::t('app', 'ad_groups.keywords_count'),
                'value' => fn(AdGroup $m) => $m->getKeywords()->count(),
            ],
            [
                'label' => Yii::t('app', 'ad_groups.ads_count'),
                'value' => fn(AdGroup $m) => $m->getAds()->count(),
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view}',
                'buttons' => [
                    'view' => fn($url, $m) => Html::a(
                        Yii::t('app', 'ad_groups.preview'),
                        ['view', 'id' => $m->id],
                        ['class' => 'btn btn-sm btn-outline-primary'],
                    ),
                ],
            ],
        ],
    ]) ?>
</div>
