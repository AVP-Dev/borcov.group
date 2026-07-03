<?php

declare(strict_types=1);

/**
 * @var \yii\web\View $this
 * @var \yii\data\ActiveDataProvider $historyProvider
 * @var array<int, array{group: \common\models\AdGroup, total: int, draft: int, exported: int}> $groupStats
 * @var array<int, \common\models\Ad[]> $groupAds
 */

use common\models\Ad;
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
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:40px"><input type="checkbox" id="check-all"></th>
                                <th style="width:32px"></th>
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
                                $adsInGroup = $groupAds[$groupId] ?? [];
                                ?>
                                <tr class="group-row" data-group-id="<?= $groupId ?>" data-ad-ids="<?= Html::encode(implode(',', array_map(fn(Ad $a) => $a->id, $adsInGroup))) ?>">
                                    <td><input type="checkbox" name="group_ids[]" value="<?= $groupId ?>" class="group-check"></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-link p-0 toggle-ads"
                                            data-target="#group-ads-<?= $groupId ?>"
                                            aria-expanded="false"
                                            title="<?= Yii::t('app', 'export.toggle_ads') ?>">
                                            ▶
                                        </button>
                                    </td>
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
                                <?php if ($adsInGroup !== []): ?>
                                    <tr class="group-ads-row" id="group-ads-<?= $groupId ?>" style="display:none">
                                        <td colspan="8" class="p-0">
                                            <table class="table table-sm table-borderless mb-0 bg-light">
                                                <tbody>
                                                    <?php foreach ($adsInGroup as $ad): ?>
                                                        <tr>
                                                            <td style="width:40px; padding-left:32px">
                                                                <input type="checkbox"
                                                                    name="ad_ids[]"
                                                                    value="<?= $ad->id ?>"
                                                                    class="ad-check"
                                                                    data-group-id="<?= $groupId ?>">
                                                            </td>
                                                            <td colspan="2" style="padding-left:8px">
                                                                <small>
                                                                    <?= Html::encode(mb_substr($ad->headline_1, 0, 30)) ?>
                                                                    <span class="text-muted">|</span>
                                                                    <?= Html::encode(mb_substr($ad->headline_2, 0, 30)) ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted"><?= Html::encode(mb_substr($ad->description_1, 0, 50)) ?></small>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-<?= $ad->generator === 'template' ? 'secondary' : 'info' ?>"><?= Html::encode($ad->generator) ?></span>
                                                            </td>
                                                            <td class="text-center">
                                                                <?php if ($ad->status === Ad::STATUS_DRAFT): ?>
                                                                    <span class="badge bg-success"><?= Yii::t('app', 'export.status_draft') ?></span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary"><?= Yii::t('app', 'export.status_exported') ?></span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-end">
                                                                <?= Html::a('✏️', ['/ad-groups/view', 'id' => $groupId], ['class' => 'text-decoration-none small']) ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                <?php endif; ?>
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
var adChecks = document.querySelectorAll('.ad-check');
var selectAllBtn = document.getElementById('select-all');
var deselectAllBtn = document.getElementById('deselect-all');
var selectedCount = document.getElementById('selected-count');
var exportBtn = document.getElementById('export-btn');
var resetBtn = document.getElementById('reset-btn');

function updateCount() {
    var checkedAds = document.querySelectorAll('.ad-check:checked');
    var checkedGroups = document.querySelectorAll('.group-check:checked');
    var totalCount = checkedAds.length;
    var draftCount = 0;
    var exportedCount = 0;

    checkedAds.forEach(function(cb) {
        var row = cb.closest('tr');
        if (row) {
            var statusBadge = row.querySelector('.badge.bg-success') || row.querySelector('.badge.bg-secondary');
            if (statusBadge && statusBadge.classList.contains('bg-success')) draftCount++;
            else if (statusBadge) exportedCount++;
        }
    });

    if (selectedCount) {
        selectedCount.textContent = totalCount + ' selected (' + draftCount + ' draft, ' + exportedCount + ' exported)';
    }

    if (exportBtn) exportBtn.disabled = totalCount === 0;
    if (resetBtn) resetBtn.disabled = exportedCount === 0;
}

// Group checkbox → check/uncheck all ads in group
groupChecks.forEach(function(gcb) {
    gcb.addEventListener('change', function() {
        var groupId = this.value;
        var groupAds = document.querySelectorAll('.ad-check[data-group-id="' + groupId + '"]');
        groupAds.forEach(function(acb) { acb.checked = gcb.checked; });
        updateCount();
    });
});

// Individual ad checkbox → update group checkbox state
adChecks.forEach(function(acb) {
    acb.addEventListener('change', function() {
        var groupId = this.getAttribute('data-group-id');
        var groupCheck = document.querySelector('.group-check[value="' + groupId + '"]');
        var groupAds = document.querySelectorAll('.ad-check[data-group-id="' + groupId + '"]');
        if (groupCheck) {
            var allChecked = true;
            groupAds.forEach(function(a) { if (!a.checked) allChecked = false; });
            groupCheck.checked = allChecked;
        }
        updateCount();
    });
});

// Check All
if (checkAll) {
    checkAll.addEventListener('change', function() {
        groupChecks.forEach(function(cb) { cb.checked = checkAll.checked; });
        adChecks.forEach(function(cb) { cb.checked = checkAll.checked; });
        updateCount();
    });
}

// Toggle group rows
document.querySelectorAll('.toggle-ads').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var target = document.querySelector(this.getAttribute('data-target'));
        if (target) {
            var isHidden = target.style.display === 'none' || target.style.display === '';
            target.style.display = isHidden ? 'table-row' : 'none';
            this.textContent = isHidden ? '▼' : '▶';
            this.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
        }
    });
});

// Select All / Deselect All buttons
if (selectAllBtn) {
    selectAllBtn.addEventListener('click', function() {
        groupChecks.forEach(function(cb) { cb.checked = true; });
        adChecks.forEach(function(cb) { cb.checked = true; });
        if (checkAll) checkAll.checked = true;
        updateCount();
    });
}

if (deselectAllBtn) {
    deselectAllBtn.addEventListener('click', function() {
        groupChecks.forEach(function(cb) { cb.checked = false; });
        adChecks.forEach(function(cb) { cb.checked = false; });
        if (checkAll) checkAll.checked = false;
        updateCount();
    });
}

// Reset button
if (resetBtn) {
    resetBtn.addEventListener('click', function(e) {
        var checkedGroups = document.querySelectorAll('.group-check:checked');
        if (checkedGroups.length === 0) {
            e.preventDefault();
            return;
        }
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
