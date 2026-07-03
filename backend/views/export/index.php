<?php

declare(strict_types=1);

/**
 * @var \yii\web\View $this
 * @var \yii\data\ActiveDataProvider $dataProvider
 * @var \yii\data\ActiveDataProvider $adsProvider
 * @var int $draftAdsCount
 */

use common\models\Ad;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = Yii::t('app', 'export.title');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="export-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
        <div>
            <?php if ($draftAdsCount > 0): ?>
                <?= Html::a(
                    Yii::t('app', 'export.create_btn') . " ({$draftAdsCount})",
                    ['create'],
                    [
                        'class' => 'btn btn-primary',
                        'data-method' => 'post',
                        'data-confirm' => Yii::t('app', 'export.confirm'),
                        'id' => 'export-btn',
                    ],
               ) ?>
            <?php else: ?>
                <button class="btn btn-secondary" disabled>
                    <?= Yii::t('app', 'export.nothing_to_export') ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($draftAdsCount > 0): ?>
        <div class="card mb-4">
            <div class="card-header bg-transparent fw-semibold">
                <?= Yii::t('app', 'export.draft_ads_title') ?> (<?= $draftAdsCount ?>)
            </div>
            <div class="card-body p-0">
                <?= GridView::widget([
                    'dataProvider' => $adsProvider,
                    'tableOptions' => ['class' => 'table table-hover mb-0'],
                    'columns' => [
                        'id',
                        [
                            'attribute' => 'adGroup.theme_label',
                            'label' => Yii::t('app', 'export.ad_group'),
                            'value' => fn(Ad $ad) => $ad->adGroup?->theme_label ?? '—',
                        ],
                        [
                            'attribute' => 'headline_1',
                            'label' => Yii::t('app', 'export.headline'),
                            'value' => fn(Ad $ad) => mb_substr($ad->headline_1, 0, 25) . (mb_strlen($ad->headline_1) > 25 ? '...' : ''),
                        ],
                        [
                            'attribute' => 'headline_2',
                            'label' => Yii::t('app', 'export.headline') . ' 2',
                            'value' => fn(Ad $ad) => mb_substr($ad->headline_2, 0, 25) . (mb_strlen($ad->headline_2) > 25 ? '...' : ''),
                        ],
                        [
                            'attribute' => 'description_1',
                            'label' => Yii::t('app', 'export.description_col'),
                            'value' => fn(Ad $ad) => mb_substr($ad->description_1, 0, 40) . (mb_strlen($ad->description_1) > 40 ? '...' : ''),
                        ],
                        'generator',
                    ],
                ]) ?>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <?= Yii::t('app', 'export.empty_hint') ?>
        </div>
    <?php endif; ?>

    <h4 class="mb-3"><?= Yii::t('app', 'export.history_title') ?></h4>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'id',
            [
                'attribute' => 'created_at',
                'format' => 'datetime',
                'label' => Yii::t('app', 'export.exported_at'),
            ],
            [
                'attribute' => 'ads_count',
                'label' => Yii::t('app', 'export.ads_count'),
            ],
            [
                'attribute' => 'keywords_count',
                'label' => Yii::t('app', 'export.keywords_count'),
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{download}',
                'buttons' => [
                    'download' => fn($url, \common\models\ExportBatch $model) => Html::a(
                        Yii::t('app', 'export.download'),
                        ['download', 'id' => $model->id],
                        ['class' => 'btn btn-sm btn-outline-success'],
                    ),
                ],
            ],
        ],
    ]) ?>
</div>

<?php
$this->registerJs(<<<JS
document.getElementById('export-btn')?.addEventListener('click', function(e) {
    this.classList.add('disabled');
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> ' + this.textContent.trim();
});
JS);
?>
