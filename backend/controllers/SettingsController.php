<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\BrandTerm;
use common\models\ForbiddenTerm;
use common\models\Setting;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class SettingsController extends Controller
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
        $brandProvider = new ActiveDataProvider([
            'query' => BrandTerm::find()->orderBy(['is_own_brand' => SORT_DESC, 'term' => SORT_ASC]),
            'pagination' => ['pageSize' => 50],
        ]);

        $forbiddenProvider = new ActiveDataProvider([
            'query' => ForbiddenTerm::find()->orderBy(['term' => SORT_ASC]),
            'pagination' => ['pageSize' => 50],
        ]);

        $volumeMin = Setting::get('pipeline.volume.min', '10');
        $volumeSources = Setting::get('pipeline.volume.min_source_count', '3');
        $dedupThreshold = Setting::get('pipeline.dedup.similarity_threshold', '0.6');

        return $this->render('index', [
            'brandProvider' => $brandProvider,
            'forbiddenProvider' => $forbiddenProvider,
            'volumeMin' => $volumeMin,
            'volumeSources' => $volumeSources,
            'dedupThreshold' => $dedupThreshold,
        ]);
    }

    public function actionSaveGeneral(): Response
    {
        $volumeMin = Yii::$app->request->post('volume_min', '10');
        $volumeSources = Yii::$app->request->post('volume_sources', '3');
        $dedupThreshold = Yii::$app->request->post('dedup_threshold', '0.6');

        Setting::set('pipeline.volume.min', $volumeMin);
        Setting::set('pipeline.volume.min_source_count', $volumeSources);
        Setting::set('pipeline.dedup.similarity_threshold', $dedupThreshold);

        Yii::$app->session->setFlash('success', Yii::t('app', 'settings.saved'));
        return $this->redirect(['index']);
    }

    public function actionAddBrand(): Response
    {
        $term = Yii::$app->request->post('term', '');
        $isOwn = Yii::$app->request->post('is_own_brand', '0');

        if ($term === '') {
            Yii::$app->session->setFlash('warning', Yii::t('app', 'settings.term_required'));
            return $this->redirect(['index']);
        }

        $model = new BrandTerm();
        $model->term = $term;
        $model->is_own_brand = $isOwn === '1';
        $model->save();

        Yii::$app->session->setFlash('success', Yii::t('app', 'settings.brand_added'));
        return $this->redirect(['index']);
    }

    public function actionDeleteBrand(int $id): Response
    {
        $model = BrandTerm::findOne($id);
        if ($model) {
            $model->delete();
        }
        return $this->redirect(['index']);
    }

    public function actionAddForbidden(): Response
    {
        $term = Yii::$app->request->post('term', '');
        $matchType = Yii::$app->request->post('match_type', 'contains');

        if ($term === '') {
            Yii::$app->session->setFlash('warning', Yii::t('app', 'settings.term_required'));
            return $this->redirect(['index']);
        }

        $model = new ForbiddenTerm();
        $model->term = $term;
        $model->match_type = $matchType;
        $model->save();

        Yii::$app->session->setFlash('success', Yii::t('app', 'settings.forbidden_added'));
        return $this->redirect(['index']);
    }

    public function actionDeleteForbidden(int $id): Response
    {
        $model = ForbiddenTerm::findOne($id);
        if ($model) {
            $model->delete();
        }
        return $this->redirect(['index']);
    }
}
