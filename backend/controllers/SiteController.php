<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\LoginForm;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\Response;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error', 'status', 'set-language'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions(): array
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
        ];
    }

    public function actionIndex(): string
    {
        $importBatchesCount = \common\models\ImportBatch::find()->count();
        $keywordsTotal = \common\models\Keyword::find()->count();
        $keywordsReady = \common\models\Keyword::find()->where(['status' => \common\models\Keyword::STATUS_READY])->count();
        $keywordsRaw = \common\models\Keyword::find()->where(['status' => \common\models\Keyword::STATUS_RAW])->count();
        $keywordsCleaned = \common\models\Keyword::find()->where(['status' => \common\models\Keyword::STATUS_CLEANED])->count();
        $keywordsRejected = \common\models\Keyword::find()->where(['status' => \common\models\Keyword::STATUS_REJECTED])->count();
        $adGroupsCount = \common\models\AdGroup::find()->count();
        $adsCount = \common\models\Ad::find()->count();
        $adsDraft = \common\models\Ad::find()->where(['status' => \common\models\Ad::STATUS_DRAFT])->count();
        $adsExported = \common\models\Ad::find()->where(['status' => \common\models\Ad::STATUS_EXPORTED])->count();

        return $this->render('index', compact(
            'importBatchesCount', 'keywordsTotal', 'keywordsReady',
            'keywordsRaw', 'keywordsCleaned', 'keywordsRejected',
            'adGroupsCount', 'adsCount', 'adsDraft', 'adsExported',
        ));
    }

    /**
     * Status/health-check action (Phase -1 deployment verification).
     * Public, no authentication required.
     *
     * @return Response
     */
    public function actionStatus(): Response
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $this->asJson([
            'status' => 'OK',
            'app'    => 'Marketing Keyword Automation Platform',
            'phase'  => '-1 (skeleton)',
            'time'   => date('c'),
        ]);
    }

    /**
     * Set language action.
     */
    public function actionSetLanguage(string $lang): Response
    {
        $allowed = ['en', 'ru'];
        if (in_array($lang, $allowed, true)) {
            Yii::$app->session->set('language', $lang);
        }
        return $this->redirect(Yii::$app->request->referrer ?: ['/site/index']);
    }

    public function actionLogin(): string|Response
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $this->layout = 'blank';

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout(): Response
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }
}
