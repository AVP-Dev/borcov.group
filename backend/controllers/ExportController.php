<?php

declare(strict_types=1);

namespace backend\controllers;

use common\components\pipeline\ExportService;
use common\models\Ad;
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
        $historyProvider = new ActiveDataProvider([
            'query' => ExportBatch::find()->orderBy(['id' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        $groupStats = ExportService::getGroupedStats();

        // Load ads for each group (for expandable rows)
        $groupAds = [];
        foreach ($groupStats as $groupId => $stats) {
            $groupAds[$groupId] = Ad::find()
                ->where(['ad_group_id' => $groupId])
                ->orderBy(['status' => SORT_ASC, 'id' => SORT_ASC])
                ->all();
        }

        return $this->render('index', [
            'historyProvider' => $historyProvider,
            'groupStats' => $groupStats,
            'groupAds' => $groupAds,
        ]);
    }

    public function actionCreate(): Response
    {
        $adIds = Yii::$app->request->post('ad_ids');
        $groupIds = Yii::$app->request->post('group_ids');
        $exportAll = Yii::$app->request->post('export_all') === '1';

        $service = new ExportService();

        if ($exportAll) {
            [$filePath, $adsCount, $keywordsCount] = $service->export();
        } elseif (is_array($adIds) && $adIds !== []) {
            $adIds = array_map('intval', $adIds);
            [$filePath, $adsCount, $keywordsCount] = $service->exportSelected($adIds);
        } elseif (is_array($groupIds) && $groupIds !== []) {
            $groupIds = array_map('intval', $groupIds);
            [$filePath, $adsCount, $keywordsCount] = $service->exportGroups($groupIds);
        } else {
            Yii::$app->session->setFlash('warning', Yii::t('app', 'export.no_selection'));
            return $this->redirect(['index']);
        }

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

    public function actionReset(): Response
    {
        $groupIds = Yii::$app->request->post('group_ids');

        if (!is_array($groupIds) || $groupIds === []) {
            Yii::$app->session->setFlash('warning', Yii::t('app', 'export.no_selection'));
            return $this->redirect(['index']);
        }

        $groupIds = array_map('intval', $groupIds);
        $count = ExportService::resetGroupsToDraft($groupIds);

        Yii::$app->session->setFlash(
            'success',
            Yii::t('app', 'export.reset_success', ['count' => $count]),
        );

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
