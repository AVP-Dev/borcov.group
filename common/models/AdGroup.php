<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveRecord;

class AdGroup extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%ad_groups}}';
    }

    public function rules(): array
    {
        return [
            [['category', 'language'], 'required'],
            ['category', 'in', 'range' => [
                Keyword::CATEGORY_WEBSITE_BUILDER,
                Keyword::CATEGORY_EMAIL,
                Keyword::CATEGORY_DOMAINS,
                Keyword::CATEGORY_ACCOUNTING,
                Keyword::CATEGORY_INVOICING,
                Keyword::CATEGORY_RESELLER,
                Keyword::CATEGORY_GENERAL_BRAND,
                Keyword::CATEGORY_UNCLASSIFIED,
            ]],
            ['audience_segment', 'in', 'range' => [Keyword::AUDIENCE_B2C, Keyword::AUDIENCE_B2B]],
            ['language', 'string', 'max' => 5],
            [['target_url', 'theme_label'], 'string', 'max' => 255],
        ];
    }

    public function getAds()
    {
        return $this->hasMany(Ad::class, ['ad_group_id' => 'id']);
    }

    public function getKeywords()
    {
        return $this->hasMany(Keyword::class, ['id' => 'keyword_id'])
            ->viaTable('{{%ad_group_keywords}}', ['ad_group_id' => 'id']);
    }
}
