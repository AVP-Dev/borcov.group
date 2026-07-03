<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveRecord;

class Ad extends ActiveRecord
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY = 'ready';
    public const STATUS_EXPORTED = 'exported';

    public static function tableName(): string
    {
        return '{{%ads}}';
    }

    public function rules(): array
    {
        return [
            [['ad_group_id', 'headline_1', 'description_1', 'final_url'], 'required'],
            ['ad_group_id', 'integer'],
            [['headline_1', 'headline_2', 'headline_3', 'headline_4', 'headline_5',
              'headline_6', 'headline_7', 'headline_8', 'headline_9', 'headline_10',
              'headline_11', 'headline_12', 'headline_13', 'headline_14', 'headline_15'], 'string', 'max' => 30],
            [['description_1', 'description_2', 'description_3', 'description_4'], 'string', 'max' => 90],
            ['final_url', 'string', 'max' => 255],
            [['path_1', 'path_2'], 'string', 'max' => 15],
            ['status', 'default', 'value' => self::STATUS_DRAFT],
            ['status', 'in', 'range' => [self::STATUS_DRAFT, self::STATUS_READY, self::STATUS_EXPORTED]],
            ['generator', 'string', 'max' => 20],
            ['generator', 'default', 'value' => 'template'],
        ];
    }

    public function getAdGroup()
    {
        return $this->hasOne(AdGroup::class, ['id' => 'ad_group_id']);
    }
}
