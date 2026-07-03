<?php

declare(strict_types=1);

namespace backend\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use common\models\Keyword;
use common\models\Source;

class KeywordController extends Controller
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
        $query = Keyword::find()->with('source')->orderBy(['id' => SORT_DESC]);
        $params = Yii::$app->request->get();

        if (!empty($params['Keyword']['source_id'])) {
            $query->andWhere(['source_id' => (int)$params['Keyword']['source_id']]);
        }

        if (!empty($params['Keyword']['status'])) {
            $query->andWhere(['status' => $params['Keyword']['status']]);
        }

        if (!empty($params['Keyword']['category'])) {
            $query->andWhere(['category' => $params['Keyword']['category']]);
        }

        if (!empty($params['Keyword']['intent'])) {
            $query->andWhere(['intent' => $params['Keyword']['intent']]);
        }

        if (!empty($params['Keyword']['language'])) {
            $query->andWhere(['language' => $params['Keyword']['language']]);
        }

        if (!empty($params['Keyword']['search'])) {
            $query->andWhere(['ilike', 'normalized_text', $params['Keyword']['search']]);
        }

        $pageSize = (int) Yii::$app->request->get('per-page', 50);
        $pageSize = max(10, min(200, $pageSize));

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
                'pageSizeParam' => 'per-page',
            ],
        ]);

        $sources = Source::find()->all();
        $statuses = [Keyword::STATUS_RAW, Keyword::STATUS_CLEANED, Keyword::STATUS_REJECTED, Keyword::STATUS_READY];
        $categories = [
            Keyword::CATEGORY_WEBSITE_BUILDER,
            Keyword::CATEGORY_EMAIL,
            Keyword::CATEGORY_DOMAINS,
            Keyword::CATEGORY_ACCOUNTING,
            Keyword::CATEGORY_INVOICING,
            Keyword::CATEGORY_RESELLER,
            Keyword::CATEGORY_GENERAL_BRAND,
            Keyword::CATEGORY_UNCLASSIFIED,
        ];
        $intents = [
            Keyword::INTENT_COMMERCIAL,
            Keyword::INTENT_INFORMATIONAL,
            Keyword::INTENT_NAVIGATIONAL,
            Keyword::INTENT_UNKNOWN,
        ];

        return $this->render('index', compact('dataProvider', 'sources', 'statuses', 'categories', 'intents', 'params'));
    }

    public function actionOverride(int $id): \yii\web\Response
    {
        $keyword = Keyword::findOne($id);
        if ($keyword === null) {
            Yii::$app->session->setFlash('error', 'Keyword not found.');
            return $this->redirect(['/keyword/index']);
        }

        $status = Yii::$app->request->post('status');
        $allowed = [Keyword::STATUS_RAW, Keyword::STATUS_CLEANED, Keyword::STATUS_REJECTED, Keyword::STATUS_READY];
        if (in_array($status, $allowed, true)) {
            $keyword->status = $status;
            $keyword->save();
            Yii::$app->session->setFlash('success', Yii::t('app', 'keywords.override_done'));
        }

        return $this->redirect(['/keyword/index']);
    }
}
