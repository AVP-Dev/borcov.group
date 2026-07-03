<?php

declare(strict_types=1);

/**
 * @var yii\web\View $this
 * @var int $importBatchesCount
 * @var int $keywordsTotal
 * @var int $keywordsReady
 * @var int $keywordsRaw
 * @var int $keywordsCleaned
 * @var int $keywordsRejected
 * @var int $adGroupsCount
 * @var int $adsCount
 * @var int $adsDraft
 * @var int $adsExported
 */

use yii\helpers\Html;

$this->title = Yii::t('app', 'dashboard.title');
$username = Yii::$app->user->identity?->username;
?>
<div class="site-index">
    <div class="dashboard-banner text-white rounded-4 p-4 p-lg-5 mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="fw-bold mb-2"><?= Yii::t('app', 'dashboard.welcome', ['username' => Html::encode($username)]) ?></h1>
                <p class="opacity-75 mb-0"><?= Yii::t('app', 'dashboard.description') ?></p>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-1 fw-bold text-primary"><?= $importBatchesCount ?></div>
                    <div class="text-muted small"><?= Yii::t('app', 'dashboard.imports') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-1 fw-bold text-success"><?= $keywordsReady ?></div>
                    <div class="text-muted small"><?= Yii::t('app', 'dashboard.ready') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-1 fw-bold text-warning"><?= $keywordsTotal ?></div>
                    <div class="text-muted small"><?= Yii::t('app', 'dashboard.total_keywords') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-1 fw-bold text-danger"><?= $keywordsRejected ?></div>
                    <div class="text-muted small"><?= Yii::t('app', 'dashboard.rejected') ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-1 fw-bold text-info"><?= $adGroupsCount ?></div>
                    <div class="text-muted small"><?= Yii::t('app', 'dashboard.ad_groups') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-1 fw-bold text-secondary"><?= $adsCount ?></div>
                    <div class="text-muted small"><?= Yii::t('app', 'dashboard.total_ads') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-1 fw-bold text-primary"><?= $adsDraft ?></div>
                    <div class="text-muted small"><?= Yii::t('app', 'dashboard.ads_draft') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-1 fw-bold text-success"><?= $adsExported ?></div>
                    <div class="text-muted small"><?= Yii::t('app', 'dashboard.ads_exported') ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent fw-semibold"><?= Yii::t('app', 'dashboard.quick_actions') ?></div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?= Html::a(Yii::t('app', 'import.title'), ['/import/index'], ['class' => 'btn btn-primary']) ?>
                        <?= Html::a(Yii::t('app', 'keywords.title'), ['/keyword/index'], ['class' => 'btn btn-outline-secondary']) ?>
                        <?= Html::a(Yii::t('app', 'nav.ad_groups'), ['/ad-groups/index'], ['class' => 'btn btn-outline-info']) ?>
                        <?= Html::a(Yii::t('app', 'nav.export'), ['/export/index'], ['class' => 'btn btn-outline-success']) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent fw-semibold"><?= Yii::t('app', 'dashboard.pipeline_status') ?></div>
                <div class="card-body">
                    <div class="mb-2 d-flex justify-content-between">
                        <span><?= Yii::t('app', 'status.raw') ?></span>
                        <span class="badge bg-secondary"><?= $keywordsRaw ?></span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span><?= Yii::t('app', 'status.cleaned') ?></span>
                        <span class="badge bg-info"><?= $keywordsCleaned ?></span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span><?= Yii::t('app', 'status.ready') ?></span>
                        <span class="badge bg-success"><?= $keywordsReady ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><?= Yii::t('app', 'status.rejected') ?></span>
                        <span class="badge bg-danger"><?= $keywordsRejected ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
