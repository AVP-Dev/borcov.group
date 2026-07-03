<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveRecord;

class ImportBatch extends ActiveRecord
{
    public ?string $error_message = null;

    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    public static function tableName(): string
    {
        return '{{%import_batches}}';
    }

    public function rules(): array
    {
        return [
            [['source_id', 'filename', 'file_hash', 'imported_at'], 'required'],
            [['source_id', 'imported_at', 'rows_total', 'rows_accepted', 'rows_rejected'], 'integer'],
            ['filename', 'string', 'max' => 255],
            ['file_hash', 'string', 'max' => 64],
            ['error_message', 'string'],
            ['status', 'default', 'value' => self::STATUS_PROCESSING],
            ['status', 'in', 'range' => [self::STATUS_PROCESSING, self::STATUS_DONE, self::STATUS_FAILED]],
        ];
    }

    public function getSource()
    {
        return $this->hasOne(Source::class, ['id' => 'source_id']);
    }

    public function getKeywords()
    {
        return $this->hasMany(Keyword::class, ['batch_id' => 'id']);
    }
}
