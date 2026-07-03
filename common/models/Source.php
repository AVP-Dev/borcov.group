<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveRecord;

class Source extends ActiveRecord
{
    public const TYPE_GADS = 'gads';
    public const TYPE_SEARCH_CONSOLE = 'search_console';
    public const TYPE_AHREFS_ORGANIC = 'ahrefs_organic';
    public const TYPE_AHREFS_PAID = 'ahrefs_paid';

    public static function tableName(): string
    {
        return '{{%sources}}';
    }

    public function rules(): array
    {
        return [
            [['name', 'type', 'created_at'], 'required'],
            ['type', 'in', 'range' => [
                self::TYPE_GADS,
                self::TYPE_SEARCH_CONSOLE,
                self::TYPE_AHREFS_ORGANIC,
                self::TYPE_AHREFS_PAID,
            ]],
            ['name', 'string', 'max' => 255],
            ['created_at', 'integer'],
        ];
    }

    public function getImportBatches()
    {
        return $this->hasMany(ImportBatch::class, ['source_id' => 'id']);
    }

    public function getKeywords()
    {
        return $this->hasMany(Keyword::class, ['source_id' => 'id']);
    }
}
