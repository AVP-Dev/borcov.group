<?php

declare(strict_types=1);

namespace backend\controllers;

use common\components\pipeline\AdData;
use common\components\pipeline\GroupingService;
use common\components\pipeline\LlmAdGenerator;
use common\components\pipeline\TemplateAdGenerator;
use common\models\Ad;
use common\models\AdGroup;
use common\models\Keyword;
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
        $filterCategory = Yii::$app->request->get('category', '');
        $filterLanguage = Yii::$app->request->get('language', '');

        $query = AdGroup::find()->with('keywords')->orderBy(['theme_label' => SORT_ASC]);

        if ($filterCategory !== '') {
            $query->andWhere(['category' => $filterCategory]);
        }
        if ($filterLanguage !== '') {
            $query->andWhere(['language' => $filterLanguage]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
        ]);

        // Available options for filter dropdowns
        $categories = AdGroup::find()->select('category')->distinct()->column();
        $languages = AdGroup::find()->select('language')->distinct()->column();

        $categoryLabels = [
            Keyword::CATEGORY_WEBSITE_BUILDER => Yii::t('app', 'class.category.website_builder'),
            Keyword::CATEGORY_EMAIL => Yii::t('app', 'class.category.email'),
            Keyword::CATEGORY_DOMAINS => Yii::t('app', 'class.category.domains'),
            Keyword::CATEGORY_ACCOUNTING => Yii::t('app', 'class.category.accounting'),
            Keyword::CATEGORY_INVOICING => Yii::t('app', 'class.category.invoicing'),
            Keyword::CATEGORY_RESELLER => Yii::t('app', 'class.category.reseller'),
            Keyword::CATEGORY_GENERAL_BRAND => Yii::t('app', 'class.category.general_brand'),
        ];
        $catOptions = [];
        foreach ($categories as $c) {
            $catOptions[$c] = $categoryLabels[$c] ?? $c;
        }
        $langOptions = array_combine($languages, array_map('strtoupper', $languages));

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'deepseekAvailable' => $this->isDeepSeekAvailable(),
            'filterCategory' => $filterCategory,
            'filterLanguage' => $filterLanguage,
            'categoryOptions' => $catOptions,
            'languageOptions' => $langOptions,
        ]);
    }

    public function actionGenerate(): \yii\web\Response
    {
        $generatorType = Yii::$app->request->post('generator', 'template');
        if ($generatorType === 'llm' && $this->isDeepSeekAvailable()) {
            $gen = new LlmAdGenerator();
            $gen->timeBudget = 25.0;
            $generator = $gen;
        } else {
            $generator = new TemplateAdGenerator();
        }

        $service = new GroupingService($generator);
        [$groupsCreated, $adsGenerated] = $service->groupAll();

        $label = $generatorType === 'llm' && $this->isDeepSeekAvailable()
            ? Yii::t('app', 'ad_groups.generator_llm')
            : Yii::t('app', 'ad_groups.generator_template');
        Yii::$app->session->setFlash(
            'success',
            Yii::t('app', 'ad_groups.generated', ['groups' => $groupsCreated, 'ads' => $adsGenerated]),
        );
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
            'deepseekAvailable' => $this->isDeepSeekAvailable(),
        ]);
    }

    public function actionRegenerate(int $id): \yii\web\Response
    {
        $group = AdGroup::findOne($id);
        if ($group === null) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', 'ad_groups.not_found'));
        }

        $generatorType = Yii::$app->request->post('generator', 'template');
        if ($generatorType === 'llm' && $this->isDeepSeekAvailable()) {
            $gen = new LlmAdGenerator();
            $gen->timeBudget = 25.0;
            $generator = $gen;
        } else {
            $generator = new TemplateAdGenerator();
        }

        $service = new GroupingService($generator);
        $count = $service->regenerateForGroup($id);

        $label = $generatorType === 'llm' ? Yii::t('app', 'ad_groups.generator_llm') : Yii::t('app', 'ad_groups.generator_template');
        Yii::$app->session->setFlash('success', Yii::t('app', 'ad_groups.regenerated', ['count' => $count, 'generator' => $label]));

        return $this->redirect(['view', 'id' => $id]);
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

    private function isDeepSeekAvailable(): bool
    {
        return !empty(Yii::$app->params['deepseekApiKey'] ?? '');
    }
}
