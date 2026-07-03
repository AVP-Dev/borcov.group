<?php

declare(strict_types=1);

use yii\helpers\Html;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;
use common\models\Keyword;

/** @var \yii\web\View $this */
/** @var array $candidates */
/** @var array $categories */
/** @var array $intents */

$this->title = Yii::t('app', 'gap.title');
$this->params['breadcrumbs'][] = $this->title;

$dataProvider = new ArrayDataProvider([
    'allModels' => $candidates,
    'pagination' => ['pageSize' => 50],
    'sort' => [
        'attributes' => ['volume', 'category', 'intent', 'language', 'raw_text'],
        'defaultOrder' => ['volume' => SORT_DESC],
    ],
]);
?>
<div class="gap-analysis-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?= Yii::t('app', 'gap.description') ?></h5>
                    <p class="card-text">
                        <?= Yii::t('app', 'gap.hint') ?>
                    </p>
                    <?= Html::a(
                        Yii::t('app', 'gap.refresh'),
                        ['/gap-analysis/index'],
                        ['class' => 'btn btn-primary', 'id' => 'gap-refresh-btn']
                    ) ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($candidates)): ?>
        <div class="alert alert-info">
            <?= Yii::t('app', 'gap.no_candidates') ?>
        </div>
    <?php else: ?>
        <div class="card mb-3">
            <div class="card-body">
                <strong><?= Yii::t('app', 'gap.total') ?>:</strong>
                <?= count($candidates) ?>
                &nbsp;|&nbsp;
                <strong><?= Yii::t('app', 'gap.total_volume') ?>:</strong>
                <?= number_format(array_sum(array_column($candidates, 'volume'))) ?>
            </div>
        </div>

        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
                [
                    'attribute' => 'raw_text',
                    'label' => Yii::t('app', 'keywords.keyword'),
                ],
                [
                    'attribute' => 'volume',
                    'label' => Yii::t('app', 'keywords.volume'),
                    'format' => 'integer',
                ],
                [
                    'attribute' => 'language',
                    'label' => Yii::t('app', 'keywords.language'),
                ],
                [
                    'attribute' => 'category',
                    'label' => Yii::t('app', 'keywords.category'),
                    'value' => fn($row) => $row['category']
                        ? Yii::t('app', 'class.category.' . $row['category'])
                        : Yii::t('app', 'class.category.unclassified'),
                ],
                [
                    'attribute' => 'intent',
                    'label' => Yii::t('app', 'keywords.intent'),
                    'value' => fn($row) => $row['intent']
                        ? Yii::t('app', 'class.intent.' . $row['intent'])
                        : Yii::t('app', 'class.intent.unknown'),
                ],
                [
                    'attribute' => 'audience_segment',
                    'label' => Yii::t('app', 'keywords.audience'),
                    'value' => fn($row) => $row['audience_segment']
                        ? Yii::t('app', 'class.audience.' . $row['audience_segment'])
                        : Yii::t('app', 'class.audience.b2c'),
                ],
            ],
        ]) ?>
    <?php endif; ?>
</div>

<?php
$this->registerJs(<<<'JS'
document.getElementById('gap-refresh-btn')?.addEventListener('click', function(e) {
    this.classList.add('disabled');
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> ' + this.textContent.trim();
});
JS);
?>
