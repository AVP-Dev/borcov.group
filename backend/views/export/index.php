<?php

declare(strict_types=1);

/**
 * @var \yii\web\View $this
 * @var \yii\data\ActiveDataProvider $historyProvider
 * @var array<int, array{group: \common\models\AdGroup, total: int, draft: int, exported: int}> $groupStats
 */

use common\models\AdGroup;
use common\models\Keyword;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app', 'export.title');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="export-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
    </div>

    <p class="text-muted"><?= Yii::t('app', 'export.description') ?></p>

    <?php if ($groupStats === []): ?>
        <div class="alert alert-info">
            <?= Yii::t('app', 'export.no_groups') ?>
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-header bg-transparent fw-semibold d-flex justify-content-between align-items-center">
                <span><?= Yii::t('app', 'export.select_groups') ?></span>
                <span class="text-muted small" id="selected-count">0 <?= Yii::t('app', 'export.selected_count') ?></span>
            </div>
            <div class="card-body">
                <?= Html::beginForm(['create'], 'post', ['id' => 'export-form']) ?>
                <?= Html::beginForm(['reset'], 'post', ['id' => 'reset-form', 'style' => 'display:none']) ?>

                <div class="mb-3 d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="select-all"><?= Yii::t('app', 'export.select_all') ?></button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselect-all"><?= Yii::t('app', 'export.deselect_all') ?></button>
                    <span class="ms-2 text-muted small align-self-center" id="selected-detail"></span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width:40px"><input type="checkbox" id="check-all"></th>
                                <th><?= Yii::t('app', 'export.group_name') ?></th>
                                <th><?= Yii::t('app', 'export.category') ?></th>
                                <th><?= Yii::t('app', 'export.language') ?></th>
                                <th class="text-center"><?= Yii::t('app', 'export.total_ads') ?></th>
                                <th class="text-center"><?= Yii::t('app', 'export.draft_col') ?></th>
                                <th class="text-center"><?= Yii::t('app', 'export.exported_col') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groupStats as $groupId => $stats): ?>
                                <?php
                                $group = $stats['group'];
                                $categoryLabels = [
                                    Keyword::CATEGORY_WEBSITE_BUILDER => Yii::t('app', 'class.category.website_builder'),
                                    Keyword::CATEGORY_EMAIL => Yii::t('app', 'class.category.email'),
                                    Keyword::CATEGORY_DOMAINS => Yii::t('app', 'class.category.domains'),
                                    Keyword::CATEGORY_ACCOUNTING => Yii::t('app', 'class.category.accounting'),
                                    Keyword::CATEGORY_INVOICING => Yii::t('app', 'class.category.invoicing'),
                                    Keyword::CATEGORY_RESELLER => Yii::t('app', 'class.category.reseller'),
                                    Keyword::CATEGORY_GENERAL_BRAND => Yii::t('app', 'class.category.general_brand'),
                                ];
                                $catLabel = $categoryLabels[$group->category] ?? $group->category;
                                $audienceLabel = $group->audience_segment === Keyword::AUDIENCE_B2B
                                    ? Yii::t('app', 'class.audience.b2b')
                                    : Yii::t('app', 'class.audience.b2c');
                                $groupName = $catLabel . ' · ' . $audienceLabel . ' · ' . strtoupper($group->language);
                                if ($group->theme_label) {
                                    $groupName = Html::encode($group->theme_label) . ' <small class="text-muted">(' . Html::encode($catLabel) . ' · ' . strtoupper($group->language) . ')</small>';
                                }
                                ?>
                                <tr class="group-row" data-group-id="<?= $groupId ?>">
                                    <td><input type="checkbox" name="group_ids[]" value="<?= $groupId ?>" class="group-check"></td>
                                    <td>
                                        <?= Html::a($groupName, ['/ad-groups/view', 'id' => $groupId], ['class' => 'text-decoration-none']) ?>
                                    </td>
                                    <td><span class="badge bg-info"><?= Html::encode($catLabel) ?></span></td>
                                    <td><span class="badge bg-secondary"><?= strtoupper($group->language) ?></span></td>
                                    <td class="text-center"><?= $stats['total'] ?></td>
                                    <td class="text-center">
                                        <?php if ($stats['draft'] > 0): ?>
                                            <span class="badge bg-success"><?= $stats['draft'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($stats['exported'] > 0): ?>
                                            <span class="badge bg-secondary"><?= $stats['exported'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="d-flex gap-2">
                        <?= Html::submitButton(
                            Yii::t('app', 'export.export_groups'),
                            ['class' => 'btn btn-primary', 'id' => 'export-btn', 'disabled' => true, 'form' => 'export-form'],
                        ) ?>
                        <?= Html::submitButton(
                            Yii::t('app', 'export.reset_btn'),
                            ['class' => 'btn btn-outline-warning', 'id' => 'reset-btn', 'disabled' => true, 'form' => 'reset-form'],
                        ) ?>
                    </div>
                    <?= Html::submitButton(
                        Yii::t('app', 'export.export_all_drafts'),
                        ['class' => 'btn btn-outline-primary', 'name' => 'export_all', 'value' => '1', 'form' => 'export-form'],
                    ) ?>
                </div>

                <?= Html::endForm() ?>
                <?= Html::endForm() ?>
            </div>
        </div>
    <?php endif; ?>

    <h4 class="mb-3"><?= Yii::t('app', 'export.history_title') ?></h4>

    <?= \yii\grid\GridView::widget([
        'dataProvider' => $historyProvider,
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
$this->registerJs(<<<'JS'
var checkAll = document.getElementById('check-all');
var groupChecks = document.querySelectorAll('.group-check');
var selectAllBtn = document.getElementById('select-all');
var deselectAllBtn = document.getElementById('deselect-all');
var selectedCount = document.getElementById('selected-count');
var exportBtn = document.getElementById('export-btn');
var resetBtn = document.getElementById('reset-btn');
var selectedText = '';

function updateCount() {
    var checked = document.querySelectorAll('.group-check:checked');
    var count = checked.length;
    var draftTotal = 0;
    var exportedTotal = 0;

    checked.forEach(function(cb) {
        var row = cb.closest('.group-row');
        if (row) {
            var draftCell = row.querySelector('td:nth-child(6) .badge');
            var exportedCell = row.querySelector('td:nth-child(7) .badge');
            if (draftCell) draftTotal += parseInt(draftCell.textContent);
            if (exportedCell) exportedTotal += parseInt(exportedCell.textContent);
        }
    });

    if (selectedCount) {
        selectedCount.textContent = count + ' ' + selectedText;
    }

    if (exportBtn) exportBtn.disabled = count === 0;
    if (resetBtn) resetBtn.disabled = count === 0 || exportedTotal === 0;
}

if (checkAll) {
    checkAll.addEventListener('change', function() {
        groupChecks.forEach(function(cb) { cb.checked = checkAll.checked; });
        updateCount();
    });
}

groupChecks.forEach(function(cb) {
    cb.addEventListener('change', updateCount);
});

if (selectAllBtn) {
    selectAllBtn.addEventListener('click', function() {
        groupChecks.forEach(function(cb) { cb.checked = true; });
        if (checkAll) checkAll.checked = true;
        updateCount();
    });
}

if (deselectAllBtn) {
    deselectAllBtn.addEventListener('click', function() {
        groupChecks.forEach(function(cb) { cb.checked = false; });
        if (checkAll) checkAll.checked = false;
        updateCount();
    });
}

if (exportBtn) {
    exportBtn.addEventListener('click', function() {
        this.classList.add('disabled');
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Exporting...';
    });
}

if (resetBtn) {
    resetBtn.addEventListener('click', function(e) {
        if (!confirm('Reset exported ads in selected groups back to draft?')) {
            e.preventDefault();
            return;
        }
        this.classList.add('disabled');
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Resetting...';
    });
}
JS);
?>
