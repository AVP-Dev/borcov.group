<?php

declare(strict_types=1);

/**
 * @var \yii\web\View $this
 * @var \yii\data\ActiveDataProvider $dataProvider
 * @var bool $deepseekAvailable
 */

use common\models\AdGroup;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = Yii::t('app', 'ad_groups.title');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ad-groups-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
        <div>
            <?php if ($deepseekAvailable): ?>
                <span class="badge bg-info me-2"><?= Yii::t('app', 'ad_groups.generator_ai_available') ?></span>
            <?php endif; ?>
            <?php $form = ActiveForm::begin([
                'id' => 'generate-form',
                'action' => ['generate'],
                'method' => 'post',
                'options' => ['class' => 'd-inline'],
            ]) ?>
            <div class="btn-group me-2" role="group">
                <input type="radio" class="btn-check" name="generator" id="gen-all-template" value="template" checked>
                <label class="btn btn-outline-secondary btn-sm" for="gen-all-template"><?= Yii::t('app', 'ad_groups.generator_template') ?></label>
                <input type="radio" class="btn-check" name="generator" id="gen-all-llm" value="llm" <?= $deepseekAvailable ? '' : 'disabled' ?>>
                <label class="btn btn-outline-info btn-sm <?= $deepseekAvailable ? '' : 'text-muted' ?>" for="gen-all-llm">
                    <?= Yii::t('app', 'ad_groups.generator_llm') ?>
                </label>
            </div>
            <?= Html::submitButton(
                Yii::t('app', 'ad_groups.generate_btn'),
                ['class' => 'btn btn-primary btn-sm', 'id' => 'generate-all-btn'],
            ) ?>
            <?php ActiveForm::end() ?>
        </div>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'theme_label',
            'category',
            'audience_segment',
            'language',
            'target_url',
            [
                'label' => Yii::t('app', 'ad_groups.keywords_count'),
                'value' => fn(AdGroup $m) => $m->getKeywords()->count(),
            ],
            [
                'label' => Yii::t('app', 'ad_groups.ads_count'),
                'value' => fn(AdGroup $m) => $m->getAds()->count(),
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {regenerate}',
                'buttons' => [
                    'view' => fn($url, AdGroup $m) => Html::a(
                        Yii::t('app', 'ad_groups.preview'),
                        ['view', 'id' => $m->id],
                        ['class' => 'btn btn-sm btn-outline-primary'],
                    ),
                    'regenerate' => fn($url, AdGroup $m) => Html::beginForm(['regenerate', 'id' => $m->id], 'post', ['class' => 'd-inline'])
                        . Html::submitButton(
                            Yii::t('app', 'ad_groups.regenerate_btn'),
                            [
                                'class' => 'btn btn-sm btn-outline-warning regenerate-btn',
                                'formaction' => \yii\helpers\Url::to(['regenerate', 'id' => $m->id]),
                            ],
                        )
                        . Html::endForm(),
                ],
            ],
        ],
    ]) ?>
</div>

<?php
$this->registerJs(<<<JS
document.getElementById('generate-all-btn')?.addEventListener('click', function(e) {
    this.classList.add('disabled');
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> ' + this.textContent.trim();
});

document.querySelectorAll('.regenerate-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        if (!confirm('<?= Yii::t('app', 'ad_groups.regenerate_confirm') ?>')) {
            e.preventDefault();
        }
    });
});
JS);
?>
