<?php

declare(strict_types=1);

namespace backend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use common\models\Keyword;
use common\components\pipeline\GapAnalysisService;

class GapAnalysisController extends Controller
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
        $service = new GapAnalysisService();
        $candidates = $service->analyze();

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

        return $this->render('index', compact('candidates', 'categories', 'intents'));
    }
}
