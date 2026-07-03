<?php

declare(strict_types=1);

/**
 * @var \yii\web\View $this
 * @var \yii\data\ActiveDataProvider $dataProvider
 */

use common\models\ExportBatch;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = Yii::t('app', 'export.title');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="export-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
        <?= Html::a(
            Yii::t('app', 'export.create_btn'),
            ['create'],
            [
                'class' => 'btn btn-primary',
                'data-method' => 'post',
                'data-confirm' => Yii::t('app', 'export.confirm'),
                'id' => 'export-btn',
            ],
        ) ?>
    </div>

    <p class="text-muted"><?= Yii::t('app', 'export.description') ?></p>

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
                    'download' => fn($url, ExportBatch $model) => Html::a(
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
