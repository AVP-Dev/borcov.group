<?php

declare(strict_types=1);

/**
 * @var \yii\web\View $this
 * @var \yii\data\ActiveDataProvider $brandProvider
 * @var \yii\data\ActiveDataProvider $forbiddenProvider
 * @var string $volumeMin
 * @var string $volumeSources
 * @var string $dedupThreshold
 */

use common\models\BrandTerm;
use common\models\ForbiddenTerm;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app', 'settings.title');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="settings-index">
    <h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>

    <div class="row">
        <!-- General Settings -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header fw-semibold"><?= Yii::t('app', 'settings.general') ?></div>
                <div class="card-body">
                    <?= Html::beginForm(['save-general'], 'post', ['class' => 'row g-3']) ?>
                        <div class="col-md-6">
                            <label class="form-label small"><?= Yii::t('app', 'settings.volume_min') ?></label>
                            <?= Html::input('number', 'volume_min', $volumeMin, ['class' => 'form-control form-control-sm', 'min' => 0, 'max' => 100000]) ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small"><?= Yii::t('app', 'settings.volume_sources') ?></label>
                            <?= Html::input('number', 'volume_sources', $volumeSources, ['class' => 'form-control form-control-sm', 'min' => 1, 'max' => 10]) ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small"><?= Yii::t('app', 'settings.dedup_threshold') ?></label>
                            <?= Html::input('number', 'dedup_threshold', $dedupThreshold, ['class' => 'form-control form-control-sm', 'min' => 0.1, 'max' => 1, 'step' => 0.05]) ?>
                        </div>
                        <div class="col-12 mt-3">
                            <?= Html::submitButton(Yii::t('app', 'app.save'), ['class' => 'btn btn-primary btn-sm']) ?>
                        </div>
                    <?= Html::endForm() ?>
                </div>
            </div>
        </div>

        <!-- Quick add panel -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header fw-semibold"><?= Yii::t('app', 'settings.quick_actions') ?></div>
                <div class="card-body">
                    <h6 class="mb-2"><?= Yii::t('app', 'settings.add_brand') ?></h6>
                    <?= Html::beginForm(['add-brand'], 'post', ['class' => 'row g-2 mb-3']) ?>
                        <div class="col-6">
                            <?= Html::textInput('term', '', ['class' => 'form-control form-control-sm', 'placeholder' => Yii::t('app', 'settings.term_placeholder')]) ?>
                        </div>
                        <div class="col-3">
                            <?= Html::dropDownList('is_own_brand', '0', [
                                '0' => Yii::t('app', 'settings.brand_competitor'),
                                '1' => Yii::t('app', 'settings.brand_own'),
                            ], ['class' => 'form-select form-select-sm']) ?>
                        </div>
                        <div class="col-3">
                            <?= Html::submitButton('+', ['class' => 'btn btn-success btn-sm w-100']) ?>
                        </div>
                    <?= Html::endForm() ?>

                    <h6 class="mb-2"><?= Yii::t('app', 'settings.add_forbidden') ?></h6>
                    <?= Html::beginForm(['add-forbidden'], 'post', ['class' => 'row g-2']) ?>
                        <div class="col-6">
                            <?= Html::textInput('term', '', ['class' => 'form-control form-control-sm', 'placeholder' => Yii::t('app', 'settings.term_placeholder')]) ?>
                        </div>
                        <div class="col-3">
                            <?= Html::dropDownList('match_type', 'contains', [
                                'exact' => Yii::t('app', 'settings.match_exact'),
                                'contains' => Yii::t('app', 'settings.match_contains'),
                                'regex' => Yii::t('app', 'settings.match_regex'),
                            ], ['class' => 'form-select form-select-sm']) ?>
                        </div>
                        <div class="col-3">
                            <?= Html::submitButton('+', ['class' => 'btn btn-success btn-sm w-100']) ?>
                        </div>
                    <?= Html::endForm() ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Brand Terms Table -->
    <div class="card mb-4">
        <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
            <span><?= Yii::t('app', 'settings.brand_terms') ?></span>
            <span class="badge bg-secondary"><?= $brandProvider->totalCount ?></span>
        </div>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $brandProvider,
                'tableOptions' => ['class' => 'table table-sm table-hover mb-0'],
                'layout' => "{items}\n{pager}",
                'pager' => ['class' => \yii\bootstrap5\LinkPager::class, 'maxButtonCount' => 5, 'options' => ['class' => 'pagination pagination-sm justify-content-center my-2']],
                'columns' => [
                    'term',
                    [
                        'attribute' => 'is_own_brand',
                        'value' => fn(BrandTerm $m) => $m->is_own_brand
                            ? '<span class="badge bg-success">' . Yii::t('app', 'settings.brand_own') . '</span>'
                            : '<span class="badge bg-danger">' . Yii::t('app', 'settings.brand_competitor') . '</span>',
                        'format' => 'raw',
                    ],
                    [
                        'class' => \yii\grid\ActionColumn::class,
                        'template' => '{delete}',
                        'buttons' => [
                            'delete' => fn($url, BrandTerm $model) => Html::a('×', ['delete-brand', 'id' => $model->id], [
                                'class' => 'btn btn-sm btn-outline-danger',
                                'data-method' => 'post',
                                'data-confirm' => Yii::t('app', 'settings.delete_confirm'),
                            ]),
                        ],
                    ],
                ],
            ]) ?>
        </div>
    </div>

    <!-- Forbidden Terms Table -->
    <div class="card mb-4">
        <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
            <span><?= Yii::t('app', 'settings.forbidden_terms') ?></span>
            <span class="badge bg-secondary"><?= $forbiddenProvider->totalCount ?></span>
        </div>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $forbiddenProvider,
                'tableOptions' => ['class' => 'table table-sm table-hover mb-0'],
                'layout' => "{items}\n{pager}",
                'pager' => ['class' => \yii\bootstrap5\LinkPager::class, 'maxButtonCount' => 5, 'options' => ['class' => 'pagination pagination-sm justify-content-center my-2']],
                'columns' => [
                    'term',
                    [
                        'attribute' => 'match_type',
                        'value' => fn(ForbiddenTerm $m) => match ($m->match_type) {
                            'exact' => '<span class="badge bg-warning text-dark">' . Yii::t('app', 'settings.match_exact') . '</span>',
                            'contains' => '<span class="badge bg-info">' . Yii::t('app', 'settings.match_contains') . '</span>',
                            'regex' => '<span class="badge bg-danger">' . Yii::t('app', 'settings.match_regex') . '</span>',
                            default => $m->match_type,
                        },
                        'format' => 'raw',
                    ],
                    [
                        'class' => \yii\grid\ActionColumn::class,
                        'template' => '{delete}',
                        'buttons' => [
                            'delete' => fn($url, ForbiddenTerm $model) => Html::a('×', ['delete-forbidden', 'id' => $model->id], [
                                'class' => 'btn btn-sm btn-outline-danger',
                                'data-method' => 'post',
                                'data-confirm' => Yii::t('app', 'settings.delete_confirm'),
                            ]),
                        ],
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div>
