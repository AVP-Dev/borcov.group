<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use common\models\ImportBatch;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app', 'import.batches');

$hasProcessing = false;
foreach ($dataProvider->getModels() as $model) {
    if ($model->status === ImportBatch::STATUS_PROCESSING) {
        $hasProcessing = true;
        break;
    }
}

if ($hasProcessing):
    $this->registerJs('
        setTimeout(function() { location.reload(); }, 3000);
    ');
endif;
?>
<div class="import-batches">
    <h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>

    <div class="mb-3">
        <?= Html::a(Yii::t('app', 'import.title'), ['/import/index'], ['class' => 'btn btn-primary']) ?>
    </div>

    <?php if ($hasProcessing): ?>
        <div class="alert alert-info d-flex align-items-center gap-2 py-2 px-3 mb-3" role="alert">
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            <span class="small"><?= Yii::t('app', 'import.processing_in_progress') ?></span>
        </div>
    <?php endif ?>

    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success py-2 px-3 mb-3">
            <?= Yii::$app->session->getFlash('success') ?>
        </div>
    <?php endif ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped table-bordered'],
        'columns' => [
            [
                'attribute' => 'id',
                'label' => '#',
            ],
            [
                'attribute' => 'source_id',
                'label' => Yii::t('app', 'import.source_type'),
                'value' => fn($model) => $model->source->name ?? '-',
            ],
            'filename',
            [
                'attribute' => 'rows_total',
                'label' => Yii::t('app', 'import.total'),
            ],
            [
                'attribute' => 'rows_accepted',
                'label' => Yii::t('app', 'import.accepted'),
            ],
            [
                'attribute' => 'rows_rejected',
                'label' => Yii::t('app', 'import.rejected'),
            ],
            [
                'attribute' => 'status',
                'label' => Yii::t('app', 'import.status'),
 'value' => function ($model) {
                    if ($model->status === ImportBatch::STATUS_PROCESSING) {
                        return '<span class="spinner-border spinner-border-sm me-1" role="status"></span> ' . Html::encode(Yii::t('app', 'import.status.' . $model->status));
                    }
                    return Yii::t('app', 'import.status.' . $model->status);
                },
                'format' => 'raw',
            ],
            [
                'attribute' => 'imported_at',
                'label' => Yii::t('app', 'import.imported_at'),
                'format' => ['datetime', 'php:Y-m-d H:i'],
            ],
        ],
    ]) ?>
</div>
