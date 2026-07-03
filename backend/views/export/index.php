<?php

declare(strict_types=1);

/**
 * @var \yii\web\View $this
 * @var \yii\data\ActiveDataProvider $dataProvider
 * @var \yii\data\ActiveDataProvider $adsProvider
 * @var int $draftAdsCount
 * @var array $groupStats
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
    </div>

    <p class="text-muted"><?= Yii::t('app', 'export.description') ?></p>

    <?php if ($draftAdsCount > 0): ?>
        <div class="card mb-4">
            <div class="card-header bg-transparent fw-semibold d-flex justify-content-between align-items-center">
                <span><?= Yii::t('app', 'export.select_ads') ?></span>
                <span class="badge bg-primary"><?= $draftAdsCount ?> <?= Yii::t('app', 'export.total_draft') ?></span>
            </div>
            <div class="card-body">
                <?= Html::beginForm(['create'], 'post', ['id' => 'export-form']) ?>

                <div class="mb-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="select-all"><?= Yii::t('app', 'export.select_all') ?></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselect-all"><?= Yii::t('app', 'export.deselect_all') ?></button>
                    <span class="ms-2 text-muted" id="selected-count">0 <?= Yii::t('app', 'export.selected') ?></span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="check-all"></th>
                                <th><?= Yii::t('app', 'export.ad_group') ?></th>
                                <th><?= Yii::t('app', 'export.headline') ?> 1</th>
                                <th><?= Yii::t('app', 'export.headline') ?> 2</th>
                                <th><?= Yii::t('app', 'export.description_col') ?></th>
                                <th><?= Yii::t('app', 'export.generator_col') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adsProvider->getModels() as $ad): ?>
                                <tr>
                                    <td><input type="checkbox" name="ad_ids[]" value="<?= $ad->id ?>" class="ad-check"></td>
                                    <td><small class="text-muted"><?= Html::encode($ad->adGroup?->theme_label ?? '—') ?></small></td>
                                    <td><?= Html::encode(mb_substr($ad->headline_1, 0, 30)) ?></td>
                                    <td><?= Html::encode(mb_substr($ad->headline_2, 0, 30)) ?></td>
                                    <td><small><?= Html::encode(mb_substr($ad->description_1, 0, 50)) ?></small></td>
                                    <td><span class="badge bg-<?= $ad->generator === 'template' ? 'secondary' : 'info' ?>"><?= Html::encode($ad->generator) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <?= Html::submitButton(
                        Yii::t('app', 'export.export_selected'),
                        ['class' => 'btn btn-primary', 'id' => 'export-btn', 'disabled' => true],
                    ) ?>
                    <?= Html::submitButton(
                        Yii::t('app', 'export.export_all'),
                        ['class' => 'btn btn-outline-primary', 'name' => 'export_all', 'value' => '1'],
                    ) ?>
                </div>

                <?= Html::endForm() ?>
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
const checkAll = document.getElementById('check-all');
const adChecks = document.querySelectorAll('.ad-check');
const selectAllBtn = document.getElementById('select-all');
const deselectAllBtn = document.getElementById('deselect-all');
const selectedCount = document.getElementById('selected-count');
const exportBtn = document.getElementById('export-btn');

function updateCount() {
    const count = document.querySelectorAll('.ad-check:checked').length;
    selectedCount.textContent = count + ' <?= Yii::t('app', 'export.selected') ?>';
    exportBtn.disabled = count === 0;
}

checkAll?.addEventListener('change', function() {
    adChecks.forEach(cb => cb.checked = this.checked);
    updateCount();
});

adChecks.forEach(cb => cb.addEventListener('change', updateCount));

selectAllBtn?.addEventListener('click', function() {
    adChecks.forEach(cb => cb.checked = true);
    checkAll.checked = true;
    updateCount();
});

deselectAllBtn?.addEventListener('click', function() {
    adChecks.forEach(cb => cb.checked = false);
    checkAll.checked = false;
    updateCount();
});

document.getElementById('export-btn')?.addEventListener('click', function() {
    if (!this.form.checkValidity()) return;
    this.classList.add('disabled');
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Exporting...';
});
JS);
?>
