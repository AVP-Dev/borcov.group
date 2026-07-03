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
        'label' => Yii::t('app', 'nav.login'),
        'url' => ['/site/login'],
        'visible' => Yii::$app->user->isGuest,
    ],
    [
        'label' => Yii::t('app', 'nav.logout', ['username' => Html::encode(Yii::$app->user->identity->username ?? '')]),
        'url' => ['/site/logout'],
        'linkOptions' => [
            'data-method' => 'post',
            'class' => 'logout',
        ],
        'visible' => !Yii::$app->user->isGuest,
    ],
    [
        'label' => Yii::$app->language === 'ru' ? Yii::t('app', 'lang.en') : Yii::t('app', 'lang.ru'),
        'url' => ['/site/set-language', 'lang' => Yii::$app->language === 'ru' ? 'en' : 'ru'],
        'linkOptions' => ['class' => 'nav-link'],
    ],
];
?>
<header id="header">
    <?php NavBar::begin(
        [
            'brandLabel' => Yii::$app->name,
            'brandUrl' => Yii::$app->homeUrl,
            'options' => ['class' => 'navbar-expand-md navbar-dark bg-dark fixed-top']
        ],
    ) ?>
    <?= Nav::widget(
        [
            'options' => ['class' => 'navbar-nav me-auto'],
            'encodeLabels' => false,
            'items' => $items,
        ],
    ) ?>
    <?= Html::button(
        '&#127769;',
        [
            'id' => 'theme-toggle',
            'class' => 'btn btn-link nav-link fs-5',
            'aria-label' => Yii::t('app', 'nav.toggle_dark'),
        ],
    ) ?>
    <?php NavBar::end() ?>
</header>
