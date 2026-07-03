<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app', 'import.batches');
?>
<div class="import-batches">
    <h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>

    <div class="mb-3">
        <?= Html::a(Yii::t('app', 'import.title'), ['/import/index'], ['class' => 'btn btn-primary']) ?>
    </div>

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
                'value' => fn($model) => Yii::t('app', 'import.status.' . $model->status),
            ],
            [
                'attribute' => 'imported_at',
                'label' => Yii::t('app', 'import.imported_at'),
                'format' => ['datetime', 'php:Y-m-d H:i'],
            ],
        ],
    ]) ?>
</div>
