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

$this->registerJs('
    document.querySelectorAll("[data-utc-time]").forEach(function(el) {
        var ts = parseInt(el.dataset.utcTime, 10);
        if (ts) {
            var d = new Date(ts * 1000);
            var pad = function(n) { return n.toString().padStart(2, "0"); };
            el.textContent = d.getFullYear() + "-" + pad(d.getMonth()+1) + "-" + pad(d.getDate())
                + " " + pad(d.getHours()) + ":" + pad(d.getMinutes());
        }
    });
    ' . ($hasProcessing ? '
    setTimeout(function() { location.reload(); }, 3000);
' : ''));
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
                    if ($model->status === ImportBatch::STATUS_FAILED) {
                        $errMsg = $model->getErrorText() ?? 'Check app logs for details';
                        return '<span class="text-danger">' . Html::encode(Yii::t('app', 'import.status.failed')) . '</span>'
                            . '<br><small class="text-muted">'
                            . Html::encode(mb_substr($errMsg, 0, 500)) . (mb_strlen($errMsg) > 500 ? '…' : '')
                            . '</small>';
                    }
                    return Yii::t('app', 'import.status.' . $model->status);
                },
                'format' => 'raw',
            ],
            [
                'attribute' => 'imported_at',
                'label' => Yii::t('app', 'import.imported_at'),
                'value' => fn($model) => $model->imported_at,
                'format' => 'raw',
                'contentOptions' => fn($model) => ['data-utc-time' => $model->imported_at],
            ],
            [
                'class' => \yii\grid\ActionColumn::class,
                'template' => '{delete}',
                'buttons' => [
                    'delete' => fn($url, \common\models\ImportBatch $model) => Html::a(
                        Yii::t('app', 'import.delete_btn'),
                        ['delete-batch', 'id' => $model->id],
                        [
                            'class' => 'btn btn-sm btn-outline-danger',
                            'data-method' => 'post',
                            'data-confirm' => Yii::t('app', 'import.delete_confirm', ['file' => $model->filename]),
                        ],
                    ),
                ],
            ],
        ],
    ]) ?>
</div>
