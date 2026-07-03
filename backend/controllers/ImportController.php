<?php

declare(strict_types=1);

namespace backend\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\UploadedFile;
use common\models\ImportBatch;
use common\models\Source;
use common\components\pipeline\ImportService;

class ImportController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $sources = Source::find()->all();
        return $this->render('index', compact('sources'));
    }

    public function actionUpload(): \yii\web\Response
    {
        try {
            return $this->handleUpload();
        } catch (\Throwable $e) {
            Yii::error("Import upload failed: " . $e->getMessage() . "\n" . $e->getTraceAsString(), __METHOD__);
            Yii::$app->session->setFlash('error', Yii::t('app', 'import.error.internal'));
            return $this->redirect(['/import/index']);
        }
    }

    private function handleUpload(): \yii\web\Response
    {
        $request = Yii::$app->request;
        $sourceId = (int)$request->post('source_id');
        $source = Source::findOne($sourceId);

        if ($source === null) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'import.error.unknown_source', ['type' => $sourceId]));
            return $this->redirect(['/import/index']);
        }

        $file = UploadedFile::getInstanceByName('file');
        if ($file === null || $file->hasError) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'import.error.invalid_file'));
            return $this->redirect(['/import/index']);
        }

        $allowedExts = ['csv', 'json'];
        $ext = strtolower($file->extension);
        if (!in_array($ext, $allowedExts, true)) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'import.error.invalid_extension'));
            return $this->redirect(['/import/index']);
        }

        $maxSize = 20 * 1024 * 1024;
        if ($file->size > $maxSize) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'import.error.file_too_large', ['size' => '20MB']));
            return $this->redirect(['/import/index']);
        }

        $tmpPath = Yii::getAlias('@runtime') . '/uploads';
        if (!is_dir($tmpPath)) {
            mkdir($tmpPath, 0775, true);
        }

        $filePath = $tmpPath . '/' . uniqid('import_') . '.' . $ext;
        if (!$file->saveAs($filePath)) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'import.error.invalid_file'));
            return $this->redirect(['/import/index']);
        }

        $service = new ImportService();
        $batch = $service->import($filePath, $source->type);
        if ($batch->isNewRecord === false && $batch->status === ImportBatch::STATUS_DONE) {
            Yii::$app->session->setFlash('info', Yii::t('app', 'import.duplicate_hash'));
            return $this->redirect(['/import/batches']);
        }
        Yii::$app->session->setFlash('success', Yii::t('app', 'import.started'));
        return $this->redirect(['/import/batches']);
    }

    public function actionBatches(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => ImportBatch::find()->with('source')->orderBy(['id' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('batches', compact('dataProvider'));
    }

    public function actionDeleteBatch(int $id): \yii\web\Response
    {
        $batch = ImportBatch::findOne($id);
        if ($batch === null) {
            Yii::\$app->session->setFlash('error', Yii::t('app', 'import.batch_not_found'));
            return \$this->redirect(['batches']);
        }

        $db = Yii::\$app->db;
        $db->createCommand()->delete('{{%ad_group_keywords}}', ['keyword_id' => \common\models\Keyword::find()->select('id')->where(['batch_id' => $id])])->execute();
        \common\models\Keyword::deleteAll(['batch_id' => $id]);
        $batch->delete();

        Yii::\$app->session->setFlash('success', Yii::t('app', 'import.batch_deleted'));
        return \$this->redirect(['batches']);
    }
}
