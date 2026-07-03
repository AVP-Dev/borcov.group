<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveRecord;

class ForbiddenTerm extends ActiveRecord
{
    public const MATCH_EXACT = 'exact';
    public const MATCH_CONTAINS = 'contains';
    public const MATCH_REGEX = 'regex';

    public static function tableName(): string
    {
        return '{{%forbidden_terms}}';
    }

    public function rules(): array
    {
        return [
            [['term'], 'required'],
            ['term', 'string', 'max' => 255],
            ['match_type', 'default', 'value' => self::MATCH_EXACT],
            ['match_type', 'in', 'range' => [self::MATCH_EXACT, self::MATCH_CONTAINS, self::MATCH_REGEX]],
            ['reason', 'string'],
        ];
    }
}
