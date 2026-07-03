<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var common\models\Source[] $sources */
/** @var string[] $statuses */
/** @var string[] $categories */
/** @var string[] $intents */
/** @var array $params */

use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = Yii::t('app', 'keywords.title');
?>
<div class="keyword-index">
    <h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small"><?= Yii::t('app', 'import.source_type') ?></label>
                    <select name="Keyword[source_id]" class="form-select form-select-sm">
                        <option value="">—</option>
                        <?php foreach ($sources as $source): ?>
                            <option value="<?= $source->id ?>" <?= ($params['Keyword']['source_id'] ?? '') == $source->id ? 'selected' : '' ?>><?= Html::encode($source->name) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small"><?= Yii::t('app', 'app.status') ?></label>
                    <select name="Keyword[status]" class="form-select form-select-sm">
                        <option value="">—</option>
                        <?php foreach ($statuses as $st): ?>
                            <option value="<?= $st ?>" <?= ($params['Keyword']['status'] ?? '') === $st ? 'selected' : '' ?>><?= Yii::t('app', 'status.' . $st) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small"><?= Yii::t('app', 'keywords.category') ?></label>
                    <select name="Keyword[category]" class="form-select form-select-sm">
                        <option value="">—</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>" <?= ($params['Keyword']['category'] ?? '') === $cat ? 'selected' : '' ?>><?= Yii::t('app', 'class.category.' . $cat) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small"><?= Yii::t('app', 'keywords.intent') ?></label>
                    <select name="Keyword[intent]" class="form-select form-select-sm">
                        <option value="">—</option>
                        <?php foreach ($intents as $int): ?>
                            <option value="<?= $int ?>" <?= ($params['Keyword']['intent'] ?? '') === $int ? 'selected' : '' ?>><?= Yii::t('app', 'class.intent.' . $int) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small"><?= Yii::t('app', 'app.search') ?></label>
                    <input type="text" name="Keyword[search]" class="form-control form-control-sm" value="<?= Html::encode($params['Keyword']['search'] ?? '') ?>" placeholder="normalized_text...">
                </div>
                <div class="col-md-12 mt-2">
                    <button type="submit" class="btn btn-sm btn-primary"><?= Yii::t('app', 'app.filter') ?></button>
                    <a href="<?= Url::to(['/keyword/index']) ?>" class="btn btn-sm btn-outline-secondary"><?= Yii::t('app', 'app.cancel') ?></a>
                </div>
            </form>
        </div>
    </div>

<?php
$overrideJs = <<<'JS'
document.querySelectorAll('.override-status').forEach(function(el) {
    el.addEventListener('click', function(e) {
        e.preventDefault();
        var form = document.createElement('form');
        form.method = 'post';
        form.action = this.dataset.url;
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'status';
        input.value = this.dataset.status;
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    });
});
JS;
$this->registerJs($overrideJs);
?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped table-bordered table-sm'],
        'columns' => [
            'id',
            [
                'attribute' => 'normalized_text',
                'label' => Yii::t('app', 'keywords.keyword'),
                'format' => 'text',
            ],
            [
                'attribute' => 'source_id',
                'label' => Yii::t('app', 'import.source_type'),
                'value' => fn($model) => $model->source->name ?? '-',
            ],
            [
                'attribute' => 'language',
                'label' => Yii::t('app', 'keywords.language'),
            ],
            [
                'attribute' => 'volume',
                'label' => Yii::t('app', 'keywords.volume'),
            ],
            [
                'attribute' => 'category',
                'label' => Yii::t('app', 'keywords.category'),
                'value' => fn($model) => $model->category ? Yii::t('app', 'class.category.' . $model->category) : '-',
            ],
            [
                'attribute' => 'intent',
                'label' => Yii::t('app', 'keywords.intent'),
                'value' => fn($model) => $model->intent ? Yii::t('app', 'class.intent.' . $model->intent) : '-',
            ],
            [
                'attribute' => 'audience_segment',
                'label' => Yii::t('app', 'keywords.audience'),
                'value' => fn($model) => $model->audience_segment ? Yii::t('app', 'class.audience.' . $model->audience_segment) : '-',
            ],
            [
                'attribute' => 'status',
                'label' => Yii::t('app', 'app.status'),
                'value' => fn($model) => Yii::t('app', 'status.' . $model->status),
            ],
            [
                'attribute' => 'rejection_reason',
                'label' => Yii::t('app', 'keywords.rejection_reason'),
                'value' => fn($model) => $model->rejection_reason
                    ? Yii::t('app', $model->rejection_reason)
                    : null,
            ],
            [
                'class' => \yii\grid\ActionColumn::class,
                'template' => '{override}',
                'buttons' => [
                    'override' => function ($url, $model, $key) {
                        $items = '';
                        $allStatuses = ['raw', 'cleaned', 'rejected', 'ready'];
                        $labels = [
                            'raw' => Yii::t('app', 'status.raw'),
                            'cleaned' => Yii::t('app', 'status.cleaned'),
                            'rejected' => Yii::t('app', 'status.rejected'),
                            'ready' => Yii::t('app', 'status.ready'),
                        ];
                        foreach ($allStatuses as $st) {
                            if ($st !== $model->status) {
                                $items .= Html::a($labels[$st], null, [
                                    'class' => 'dropdown-item override-status',
                                    'data-url' => Url::to(['/keyword/override', 'id' => $model->id]),
                                    'data-status' => $st,
                                ]);
                            }
                        }
                        return '<div class="dropdown">'
                            . Html::button(Yii::t('app', 'keywords.override'), ['class' => 'btn btn-sm btn-outline-primary dropdown-toggle', 'data-bs-toggle' => 'dropdown'])
                            . '<div class="dropdown-menu">' . $items . '</div></div>';
                    },
                ],
            ],
        ],
    ]) ?>
</div>
