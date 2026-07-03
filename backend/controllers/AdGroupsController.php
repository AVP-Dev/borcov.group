<?php

declare(strict_types=1);

namespace backend\controllers;

use common\components\pipeline\GroupingService;
use common\components\pipeline\TemplateAdGenerator;
use common\models\Ad;
use common\models\AdGroup;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;

class AdGroupsController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => AdGroup::find()->with('keywords')->orderBy(['theme_label' => SORT_ASC]),
            'pagination' => ['pageSize' => 50],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionGenerate(): \yii\web\Response
    {
        $service = new GroupingService(new TemplateAdGenerator());
        $created = $service->groupAll();

        Yii::$app->session->setFlash('success', Yii::t('app', 'ad_groups.generated', ['count' => $created]));
        return $this->redirect(['index']);
    }

    public function actionView(int $id): string
    {
        $group = AdGroup::find()->with('keywords', 'ads')->where(['id' => $id])->one();

        if ($group === null) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', 'ad_groups.not_found'));
        }

        $adsProvider = new ActiveDataProvider([
            'query' => Ad::find()->where(['ad_group_id' => $id]),
            'pagination' => ['pageSize' => 50],
        ]);

        return $this->render('view', [
            'group' => $group,
            'adsProvider' => $adsProvider,
        ]);
    }

    public function actionUpdateAd(int $id): \yii\web\Response
    {
        $ad = Ad::findOne($id);
        if ($ad === null) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', 'ad.not_found'));
        }

        if ($ad->load(Yii::$app->request->post()) && $ad->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'ad.updated'));
        } else {
            Yii::$app->session->setFlash('error', Yii::t('app', 'ad.update_error'));
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['view', 'id' => $ad->ad_group_id]);
    }
}
