<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\helpers\Html;
use yii\helpers\Url;

$items = [
    [
        'label' => Yii::t('app', 'nav.home'),
        'url' => ['/site/index'],
    ],
    [
        'label' => Yii::t('app', 'nav.import'),
        'url' => ['/import/index'],
        'visible' => !Yii::$app->user->isGuest,
    ],
    [
        'label' => Yii::t('app', 'nav.batches'),
        'url' => ['/import/batches'],
        'visible' => !Yii::$app->user->isGuest,
    ],
    [
        'label' => Yii::t('app', 'nav.keywords'),
        'url' => ['/keyword/index'],
        'visible' => !Yii::$app->user->isGuest,
    ],
    [
        'label' => Yii::t('app', 'nav.gap_analysis'),
        'url' => ['/gap-analysis/index'],
        'visible' => !Yii::$app->user->isGuest,
    ],
    [
        'label' => Yii::t('app', 'nav.ad_groups'),
        'url' => ['/ad-groups/index'],
        'visible' => !Yii::$app->user->isGuest,
    ],
    [
        'label' => Yii::t('app', 'nav.export'),
        'url' => ['/export/index'],
        'visible' => !Yii::$app->user->isGuest,
    ],
    [
        'label' => Yii::t('app', 'nav.settings'),
        'url' => ['/settings/index'],
        'visible' => !Yii::$app->user->isGuest,
    ],
    [
        'label' => Yii::t('app', 'nav.login'),
        'url' => ['/site/login'],
        'visible' => Yii::$app->user->isGuest,
    ],
];
?>
<header id="header">
    <?php NavBar::begin([
        'brandLabel' => Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'options' => ['class' => 'navbar-expand-md navbar-dark bg-dark fixed-top'],
    ]) ?>

    <?= Nav::widget([
        'options' => ['class' => 'navbar-nav me-auto'],
        'encodeLabels' => false,
        'items' => $items,
    ]) ?>

    <div class="d-flex align-items-center gap-2">
        <?= Html::a(
            Yii::$app->language === 'ru' ? 'EN' : 'RU',
            ['/site/set-language', 'lang' => Yii::$app->language === 'ru' ? 'en' : 'ru'],
            ['class' => 'nav-link text-white-50 px-2 text-decoration-none small fw-semibold'],
        ) ?>

        <?= Html::button(
            '&#127769;',
            [
                'id' => 'theme-toggle',
                'class' => 'btn btn-link nav-link fs-5',
                'aria-label' => Yii::t('app', 'nav.toggle_dark'),
            ],
        ) ?>

        <span class="text-white-50 mx-1">|</span>

        <?php if (!Yii::$app->user->isGuest): ?>
            <?= Html::a(
                Yii::t('app', 'nav.logout_btn'),
                ['/site/logout'],
                [
                    'class' => 'btn btn-outline-light btn-sm',
                    'data-method' => 'post',
                    'title' => Yii::t('app', 'nav.logout_title', ['username' => Html::encode(Yii::$app->user->identity->username ?? '')]),
                ],
            ) ?>
        <?php endif; ?>
    </div>

    <?php NavBar::end() ?>
</header>
