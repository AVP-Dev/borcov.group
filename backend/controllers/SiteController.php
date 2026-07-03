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
                        'actions' => ['login', 'error', 'status', 'set-language', 'debug-user'],
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

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex(): string
    {
        return $this->render('index');
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

    public function actionDebugUser(): \yii\web\Response
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $user = \common\models\User::findByUsername('admin');
        if ($user === null) {
            return $this->asJson(['exists' => false, 'error' => 'User not found']);
        }
        return $this->asJson([
            'exists' => true,
            'has_password_hash' => !empty($user->password_hash),
            'hash_prefix' => substr($user->password_hash, 0, 10) . '...',
            'status' => $user->status,
        ]);
    }

    /**
     * Login action.
     *
     * @return string|Response
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
