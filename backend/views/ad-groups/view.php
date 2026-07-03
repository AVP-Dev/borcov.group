<?php

declare(strict_types=1);

/**
 * @var \yii\web\View $this
 * @var \common\models\AdGroup $group
 * @var \yii\data\ActiveDataProvider $adsProvider
 */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use common\models\Ad;

$this->title = Yii::t('app', 'ad_groups.view_title', ['label' => $group->theme_label]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'ad_groups.title'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $group->theme_label;
?>
<div class="ad-groups-view">
    <h1 class="h3 mb-3"><?= Html::encode($group->theme_label) ?></h1>

    <div class="row mb-4">
        <div class="col-md-3"><strong><?= Yii::t('app', 'ad_groups.category') ?>:</strong> <?= Html::encode($group->category) ?></div>
        <div class="col-md-2"><strong><?= Yii::t('app', 'ad_groups.audience') ?>:</strong> <?= Html::encode($group->audience_segment) ?></div>
        <div class="col-md-1"><strong><?= Yii::t('app', 'ad_groups.language') ?>:</strong> <?= Html::encode($group->language) ?></div>
        <div class="col-md-3"><strong><?= Yii::t('app', 'ad_groups.target_url') ?>:</strong> <?= Html::encode($group->target_url) ?></div>
        <div class="col-md-2"><strong><?= Yii::t('app', 'ad_groups.keywords_count') ?>:</strong> <?= $group->getKeywords()->count() ?></div>
    </div>

    <h4 class="mb-3"><?= Yii::t('app', 'ad_groups.keywords_title') ?></h4>
    <ul class="list-group mb-4">
        <?php foreach ($group->keywords as $kw): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <?= Html::encode($kw->normalized_text ?: $kw->raw_text) ?>
                <span class="badge bg-secondary rounded-pill"><?= Yii::t('app', 'ad_groups.volume') ?>: <?= $kw->volume ?? '—' ?></span>
            </li>
        <?php endforeach; ?>
    </ul>

    <h4 class="mb-3"><?= Yii::t('app', 'ad_groups.ads_title') ?></h4>

    <?= GridView::widget([
        'dataProvider' => $adsProvider,
        'columns' => [
            'id',
            [
                'attribute' => 'headline_1',
                'format' => 'raw',
                'value' => fn(Ad $ad) => Html::tag('code', Html::encode($ad->headline_1)),
            ],
            [
                'attribute' => 'headline_2',
                'format' => 'raw',
                'value' => fn(Ad $ad) => Html::tag('code', Html::encode($ad->headline_2)),
            ],
            [
                'attribute' => 'description_1',
                'format' => 'raw',
                'value' => fn(Ad $ad) => Html::tag('small', Html::encode(mb_substr($ad->description_1, 0, 60))),
            ],
            'final_url',
            'status',
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{update}',
                'buttons' => [
                    'update' => fn($url, Ad $ad) => Html::a(
                        Yii::t('app', 'ad_groups.edit_btn'),
                        '#',
                        [
                            'class' => 'btn btn-sm btn-outline-secondary ad-edit-btn',
                            'data-id' => $ad->id,
                            'data-headline1' => $ad->headline_1,
                            'data-headline2' => $ad->headline_2,
                            'data-description1' => $ad->description_1,
                            'data-final-url' => $ad->final_url,
                        ],
                    ),
                ],
            ],
        ],
    ]) ?>
</div>

<!-- Inline edit modal -->
<div class="modal fade" id="adEditModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <?php $form = ActiveForm::begin(['action' => ['update-ad', 'id' => 0], 'method' => 'post']) ?>
            <div class="modal-header">
                <h5 class="modal-title"><?= Yii::t('app', 'ad_groups.edit_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label"><?= Yii::t('app', 'ad_groups.headline1') ?></label>
                    <input type="text" name="Ad[headline_1]" id="edit-ad-h1" class="form-control" maxlength="30">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= Yii::t('app', 'ad_groups.headline2') ?></label>
                    <input type="text" name="Ad[headline_2]" id="edit-ad-h2" class="form-control" maxlength="30">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= Yii::t('app', 'ad_groups.description1') ?></label>
                    <textarea name="Ad[description_1]" id="edit-ad-d1" class="form-control" rows="2" maxlength="90"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= Yii::t('app', 'ad_groups.final_url') ?></label>
                    <input type="text" name="Ad[final_url]" id="edit-ad-url" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= Yii::t('app', 'ad_groups.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><?= Yii::t('app', 'ad_groups.save') ?></button>
            </div>
            <?php ActiveForm::end() ?>
        </div>
    </div>
</div>

<?php
$this->registerJs(<<<JS
document.querySelectorAll('.ad-edit-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const form = document.querySelector('#adEditModal form');
        form.action = form.action.replace(/id=\d+/, 'id=' + this.dataset.id);
        document.getElementById('edit-ad-h1').value = this.dataset.headline1;
        document.getElementById('edit-ad-h2').value = this.dataset.headline2;
        document.getElementById('edit-ad-d1').value = this.dataset.description1;
        document.getElementById('edit-ad-url').value = this.dataset.finalUrl;
        new bootstrap.Modal(document.getElementById('adEditModal')).show();
    });
});
JS);
?>
