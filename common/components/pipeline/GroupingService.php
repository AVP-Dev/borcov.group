<?php

declare(strict_types=1);

namespace common\components\pipeline;

use common\models\AdGroup;
use common\models\AdGroupKeyword;
use common\models\Keyword;
use Yii;

class GroupingService
{
    /** @var array<string, string> category → target URL path */
    private const array CATEGORY_URL_MAP = [
        Keyword::CATEGORY_WEBSITE_BUILDER => '/website-builder',
        Keyword::CATEGORY_EMAIL => '/email',
        Keyword::CATEGORY_DOMAINS => '/domains',
        Keyword::CATEGORY_ACCOUNTING => '/accounting',
        Keyword::CATEGORY_INVOICING => '/invoicing',
        Keyword::CATEGORY_RESELLER => '/reseller',
        Keyword::CATEGORY_GENERAL_BRAND => '/',
        Keyword::CATEGORY_UNCLASSIFIED => '/',
    ];

    private const int ADS_PER_GROUP = 3;

    public function __construct(
        private readonly ?AdGeneratorInterface $generator = null,
    ) {}

    /**
     * @return array{int, int} [groupsCreated, adsGenerated]
     */
    public function groupAll(): array
    {
        $keywords = Keyword::find()
            ->where(['status' => Keyword::STATUS_READY])
            ->andWhere(['IS NOT', 'category', null])
            ->andWhere(['IS NOT', 'audience_segment', null])
            ->andWhere(['IS NOT', 'language', null])
            ->all();

        if ($keywords === []) {
            return [0, 0];
        }

        $groups = $this->buildGroups($keywords);
        $groupsCreated = 0;
        $adsGenerated = 0;

        foreach ($groups as $key => $keywordIds) {
            [$category, $segment, $language] = explode('|', $key, 3);

            $existing = AdGroup::find()
                ->where(['category' => $category, 'audience_segment' => $segment, 'language' => $language])
                ->one();

            if ($existing !== null) {
                $group = $existing;
            } else {
                $group = new AdGroup();
                $group->category = $category;
                $group->audience_segment = $segment;
                $group->language = $language;
                $group->theme_label = $this->buildThemeLabel($category, $segment, $language);
                if (!$group->save()) {
                    Yii::error('Failed to create AdGroup: ' . json_encode($group->errors), 'grouping');
                    continue;
                }
                $groupsCreated++;
            }

            $group->target_url = $this->resolveTargetUrl($category);
            $group->save();

            foreach ($keywordIds as $kwId) {
                $link = AdGroupKeyword::findOne(['ad_group_id' => $group->id, 'keyword_id' => $kwId]);
                if ($link === null) {
                    $link = new AdGroupKeyword();
                    $link->ad_group_id = $group->id;
                    $link->keyword_id = $kwId;
                    $link->save();
                }
            }

            if ($this->generator !== null) {
                $currentAdCount = $group->getAds()->count();
                if ($currentAdCount < self::ADS_PER_GROUP) {
                    // Сначала удалить все существующие объявления, если есть
                    // чтобы избежать накопления при повторных запусках
                    if ($currentAdCount > 0) {
                        \common\models\Ad::deleteAll(['ad_group_id' => $group->id]);
                    }
                    $firstKw = Keyword::findOne($keywordIds[0]);
                    if ($firstKw !== null) {
                        $adsGenerated += $this->generateAds($group, $firstKw);
                    }
                }
            }
        }

        return [$groupsCreated, $adsGenerated];
    }

    public function regenerateForGroup(int $adGroupId): int
    {
        $group = AdGroup::find()->with('keywords')->where(['id' => $adGroupId])->one();
        if ($group === null || $this->generator === null) {
            return 0;
        }

        \common\models\Ad::deleteAll(['ad_group_id' => $adGroupId]);

        $firstKw = $group->keywords[0] ?? null;
        if ($firstKw === null) {
            return 0;
        }

        return $this->generateAds($group, $firstKw);
    }

    private function generateAds(AdGroup $group, Keyword $keyword): int
    {
        $ads = $this->generator->generate($group, $keyword);
        $saved = 0;
        foreach ($ads as $adData) {
            $ad = new \common\models\Ad();
            $ad->ad_group_id = $group->id;
            $ad->headline_1 = $adData->headline1;
            $ad->headline_2 = $adData->headline2;
            $ad->description_1 = $adData->description1;
            $ad->final_url = $adData->finalUrl;
            $ad->path_1 = $adData->path1;
            $ad->path_2 = $adData->path2;
            $ad->generator = $adData->source;
            if ($ad->save()) {
                $saved++;
            } else {
                Yii::error(
                    'Failed to save Ad for group #' . $group->id . ': ' . json_encode($ad->errors),
                    'grouping',
                );
            }
        }
        return $saved;
    }

    /** @param Keyword[] $keywords */
    private function buildGroups(array $keywords): array
    {
        $groups = [];
        foreach ($keywords as $kw) {
            $category = $kw->category ?? Keyword::CATEGORY_UNCLASSIFIED;
            $segment = $kw->audience_segment ?? Keyword::AUDIENCE_B2C;
            $language = $kw->language ?? 'en';
            $key = "{$category}|{$segment}|{$language}";
            $groups[$key][] = $kw->id;
        }
        ksort($groups);
        return $groups;
    }

    private function resolveTargetUrl(string $category): string
    {
        $baseUrl = rtrim(Yii::$app->params['siteUrl'] ?? 'https://site.pro', '/');
        return $baseUrl . (self::CATEGORY_URL_MAP[$category] ?? '/');
    }

    private function buildThemeLabel(string $category, string $segment, string $language): string
    {
        $catLabels = [
            Keyword::CATEGORY_WEBSITE_BUILDER => 'Website Builder',
            Keyword::CATEGORY_EMAIL => 'Email',
            Keyword::CATEGORY_DOMAINS => 'Domains',
            Keyword::CATEGORY_ACCOUNTING => 'Accounting',
            Keyword::CATEGORY_INVOICING => 'Invoicing',
            Keyword::CATEGORY_RESELLER => 'Reseller',
            Keyword::CATEGORY_GENERAL_BRAND => 'General Brand',
            Keyword::CATEGORY_UNCLASSIFIED => 'Other',
        ];

        $label = $catLabels[$category] ?? $category;
        $segmentLabel = $segment === Keyword::AUDIENCE_B2B ? 'B2B' : 'B2C';

        return "{$label} ({$segmentLabel}, {$language})";
    }
}
