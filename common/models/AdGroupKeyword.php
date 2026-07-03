<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveRecord;

class AdGroupKeyword extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%ad_group_keywords}}';
    }

    public function rules(): array
    {
        return [
            [['ad_group_id', 'keyword_id'], 'required'],
            [['ad_group_id', 'keyword_id'], 'integer'],
        ];
    }
}
