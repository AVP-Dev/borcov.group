<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveRecord;

class Ad extends ActiveRecord
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY = 'ready';
    public const STATUS_EXPORTED = 'exported';

    private const int HEADLINE_MAX = 30;
    private const int DESCRIPTION_MAX = 90;

    /** @var string[] Fields that should be word-safe truncated before save */
    private const array WORD_SAFE_FIELDS = [
        'headline_1', 'headline_2', 'headline_3', 'headline_4', 'headline_5',
        'headline_6', 'headline_7', 'headline_8', 'headline_9', 'headline_10',
        'headline_11', 'headline_12', 'headline_13', 'headline_14', 'headline_15',
        'description_1', 'description_2', 'description_3', 'description_4',
    ];

    public static function tableName(): string
    {
        return '{{%ads}}';
    }

    public function rules(): array
    {
        return [
            [['ad_group_id', 'headline_1', 'headline_2', 'description_1', 'final_url'], 'required'],
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

    public function beforeValidate(): bool
    {
        foreach (self::WORD_SAFE_FIELDS as $field) {
            $value = $this->getAttribute($field);
            if ($value !== null && $value !== '') {
                $max = str_starts_with($field, 'headline') ? self::HEADLINE_MAX : self::DESCRIPTION_MAX;
                if (mb_strlen($value) > $max) {
                    $this->setAttribute($field, self::truncateWordSafe($value, $max));
                }
            }
        }
        return parent::beforeValidate();
    }

    public function getAdGroup()
    {
        return $this->hasOne(AdGroup::class, ['id' => 'ad_group_id']);
    }

    public static function truncateWordSafe(string $text, int $maxLength): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }
        $truncated = mb_substr($text, 0, $maxLength);
        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace !== false && $lastSpace > 0) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }
        return $truncated;
    }
}
