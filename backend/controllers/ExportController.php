<?php

declare(strict_types=1);

namespace backend\controllers;

use common\components\pipeline\ExportService;
use common\models\ExportBatch;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ExportController extends Controller
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
            'query' => ExportBatch::find()->orderBy(['id' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        $draftAdsCount = \common\models\Ad::find()->where(['status' => \common\models\Ad::STATUS_DRAFT])->count();

        $adsProvider = new ActiveDataProvider([
            'query' => \common\models\Ad::find()
                ->joinWith(['adGroup'])
                ->where(['ad.status' => \common\models\Ad::STATUS_DRAFT])
                ->orderBy(['ad.id' => SORT_ASC]),
            'pagination' => ['pageSize' => 50],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'adsProvider' => $adsProvider,
            'draftAdsCount' => $draftAdsCount,
        ]);
    }

    public function actionCreate(): Response
    {
        $service = new ExportService();
        [$filePath, $adsCount, $keywordsCount] = $service->export();

        if ($adsCount === 0) {
            Yii::$app->session->setFlash('warning', Yii::t('app', 'export.nothing_to_export'));
        } else {
            Yii::$app->session->setFlash(
                'success',
                Yii::t('app', 'export.success', ['ads' => $adsCount, 'keywords' => $keywordsCount]),
            );
        }

        return $this->redirect(['index']);
    }

    public function actionDownload(int $id): Response
    {
        $batch = ExportBatch::findOne($id);
        if ($batch === null || $batch->file_path === '' || !file_exists($batch->file_path)) {
            throw new NotFoundHttpException(Yii::t('app', 'export.not_found'));
        }

        return Yii::$app->response->sendFile(
            $batch->file_path,
            basename($batch->file_path),
            ['mimeType' => 'text/csv'],
        );
    }
}
