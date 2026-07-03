<?php

declare(strict_types=1);

namespace backend\controllers;

use common\components\pipeline\ExportService;
use common\models\Ad;
use common\models\ExportBatch;
use common\models\Keyword;
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
        $filterCategory = Yii::$app->request->get('category', '');
        $filterLanguage = Yii::$app->request->get('language', '');

        $historyProvider = new ActiveDataProvider([
            'query' => ExportBatch::find()->orderBy(['id' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        $groupStats = ExportService::getGroupedStats();

        $filteredStats = [];
        foreach ($groupStats as $groupId => $stats) {
            $group = $stats['group'];
            if ($filterCategory !== '' && $group->category !== $filterCategory) {
                continue;
            }
            if ($filterLanguage !== '' && $group->language !== $filterLanguage) {
                continue;
            }
            $filteredStats[$groupId] = $stats;
        }

        $groupAds = [];
        foreach ($groupStats as $groupId => $stats) {
            $groupAds[$groupId] = Ad::find()
                ->where(['ad_group_id' => $groupId])
                ->orderBy(['status' => SORT_ASC, 'id' => SORT_ASC])
                ->all();
        }

        $categories = [];
        $languages = [];
        foreach ($groupStats as $stats) {
            $g = $stats['group'];
            $categories[$g->category] = $g->category;
            $languages[$g->language] = $g->language;
        }
        $categoryLabels = [
            Keyword::CATEGORY_WEBSITE_BUILDER => Yii::t('app', 'class.category.website_builder'),
            Keyword::CATEGORY_EMAIL => Yii::t('app', 'class.category.email'),
            Keyword::CATEGORY_DOMAINS => Yii::t('app', 'class.category.domains'),
            Keyword::CATEGORY_ACCOUNTING => Yii::t('app', 'class.category.accounting'),
            Keyword::CATEGORY_INVOICING => Yii::t('app', 'class.category.invoicing'),
            Keyword::CATEGORY_RESELLER => Yii::t('app', 'class.category.reseller'),
            Keyword::CATEGORY_GENERAL_BRAND => Yii::t('app', 'class.category.general_brand'),
        ];
        $categoryOptions = array_map(fn($c) => $categoryLabels[$c] ?? $c, $categories);
        $languageOptions = array_map('strtoupper', $languages);

        return $this->render('index', [
            'historyProvider' => $historyProvider,
            'groupStats' => $groupStats,
            'groupAds' => $groupAds,
            'filterCategory' => $filterCategory,
            'filterLanguage' => $filterLanguage,
            'categoryOptions' => $categoryOptions,
            'languageOptions' => $languageOptions,
        ]);
    }

    public function actionCreate(): Response
    {
        $adIds = Yii::$app->request->post('ad_ids');
        $groupIds = Yii::$app->request->post('group_ids');
        $exportAll = Yii::$app->request->post('export_all') === '1';

        $service = new ExportService();

        try {
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
                return $this->redirect(['index']);
            }

            return Yii::$app->response->sendFile(
                $filePath,
                basename($filePath),
                ['mimeType' => 'text/csv'],
            );
        } catch (\Throwable $e) {
            Yii::error("Export failed: " . $e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', Yii::t('app', 'export.export_error', ['error' => $e->getMessage()]));
            return $this->redirect(['index']);
        }
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
        try {
            $batch = ExportBatch::findOne($id);
            if ($batch === null || $batch->file_path === '' || !file_exists($batch->file_path)) {
                throw new NotFoundHttpException(Yii::t('app', 'export.not_found'));
            }

            return Yii::$app->response->sendFile(
                $batch->file_path,
                basename($batch->file_path),
                ['mimeType' => 'text/csv'],
            );
        } catch (\Throwable $e) {
            Yii::error("Export download failed: " . $e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', Yii::t('app', 'export.download_error', ['error' => $e->getMessage()]));
            return $this->redirect(['index']);
        }
    }
}
