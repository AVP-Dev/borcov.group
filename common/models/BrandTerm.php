<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveRecord;

class BrandTerm extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%brand_terms}}';
    }

    public function rules(): array
    {
        return [
            [['term'], 'required'],
            ['term', 'string', 'max' => 255],
            ['is_own_brand', 'boolean'],
            ['is_own_brand', 'default', 'value' => false],
        ];
    }
}
