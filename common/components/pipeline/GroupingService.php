<?php

declare(strict_types=1);

namespace common\components\pipeline;

use common\models\AdGroup;
use common\models\AdGroupKeyword;
use common\models\Keyword;

class GroupingService
{
    public function __construct(
        private readonly ?AdGeneratorInterface $generator = null,
    ) {}

    public function groupAll(): int
    {
        $keywords = Keyword::find()
            ->where(['status' => Keyword::STATUS_READY])
            ->andWhere(['IS NOT', 'category', null])
            ->andWhere(['IS NOT', 'audience_segment', null])
            ->andWhere(['IS NOT', 'language', null])
            ->all();

        if ($keywords === []) {
            return 0;
        }

        $groups = $this->buildGroups($keywords);
        $created = 0;

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
                $group->save();
                $created++;
            }

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
                foreach ($keywordIds as $kwId) {
                    $kw = Keyword::findOne($kwId);
                    if ($kw === null) {
                        continue;
                    }
                    $existingAds = $group->getAds()->count();
                    if ($existingAds > 0) {
                        continue;
                    }
                    $ads = $this->generator->generate($group, $kw);
                    foreach ($ads as $adData) {
                        $ad = new \common\models\Ad();
                        $ad->ad_group_id = $group->id;
                        $ad->headline_1 = $adData->headline1;
                        $ad->headline_2 = $adData->headline2;
                        $ad->description_1 = $adData->description1;
                        $ad->final_url = $adData->finalUrl;
                        $ad->path_1 = $adData->path1;
                        $ad->path_2 = $adData->path2;
                        $ad->save();
                    }
                }
            }
        }

        return $created;
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
