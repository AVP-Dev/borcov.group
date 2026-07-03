<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class Keyword extends ActiveRecord
{
    public const STATUS_RAW = 'raw';
    public const STATUS_CLEANED = 'cleaned';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_READY = 'ready';

    public const CATEGORY_WEBSITE_BUILDER = 'website_builder';
    public const CATEGORY_EMAIL = 'email';
    public const CATEGORY_DOMAINS = 'domains';
    public const CATEGORY_ACCOUNTING = 'accounting';
    public const CATEGORY_INVOICING = 'invoicing';
    public const CATEGORY_RESELLER = 'reseller';
    public const CATEGORY_GENERAL_BRAND = 'general_brand';
    public const CATEGORY_UNCLASSIFIED = 'unclassified';

    public const INTENT_COMMERCIAL = 'commercial';
    public const INTENT_INFORMATIONAL = 'informational';
    public const INTENT_NAVIGATIONAL = 'navigational';
    public const INTENT_UNKNOWN = 'unknown';

    public const AUDIENCE_B2C = 'b2c';
    public const AUDIENCE_B2B = 'b2b';

    public static function tableName(): string
    {
        return '{{%keywords}}';
    }

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules(): array
    {
        return [
            [['batch_id', 'source_id', 'raw_text', 'normalized_text'], 'required'],
            [['batch_id', 'source_id'], 'integer'],
            [['raw_text', 'normalized_text'], 'string'],
            ['volume', 'integer', 'min' => 0],
            ['language', 'string', 'max' => 5],
            ['status', 'default', 'value' => self::STATUS_RAW],
            ['status', 'in', 'range' => [self::STATUS_RAW, self::STATUS_CLEANED, self::STATUS_REJECTED, self::STATUS_READY]],
            ['category', 'default', 'value' => self::CATEGORY_UNCLASSIFIED],
            ['category', 'in', 'range' => [
                self::CATEGORY_WEBSITE_BUILDER,
                self::CATEGORY_EMAIL,
                self::CATEGORY_DOMAINS,
                self::CATEGORY_ACCOUNTING,
                self::CATEGORY_INVOICING,
                self::CATEGORY_RESELLER,
                self::CATEGORY_GENERAL_BRAND,
                self::CATEGORY_UNCLASSIFIED,
            ]],
            ['audience_segment', 'in', 'range' => [self::AUDIENCE_B2C, self::AUDIENCE_B2B]],
            ['intent', 'default', 'value' => self::INTENT_UNKNOWN],
            ['intent', 'in', 'range' => [
                self::INTENT_COMMERCIAL,
                self::INTENT_INFORMATIONAL,
                self::INTENT_NAVIGATIONAL,
                self::INTENT_UNKNOWN,
            ]],
            [['is_brand', 'is_forbidden', 'is_already_used'], 'boolean'],
            [['is_brand', 'is_forbidden', 'is_already_used'], 'default', 'value' => false],
            ['is_duplicate_of_id', 'integer'],
            ['quality_score', 'number', 'min' => 0, 'max' => 1],
            ['rejection_reason', 'string'],
        ];
    }

    public function getBatch()
    {
        return $this->hasOne(ImportBatch::class, ['id' => 'batch_id']);
    }

    public function getSource()
    {
        return $this->hasOne(Source::class, ['id' => 'source_id']);
    }
}
