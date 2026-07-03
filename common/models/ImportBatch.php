<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveRecord;

class ImportBatch extends ActiveRecord
{
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    public static function tableName(): string
    {
        return '{{%import_batches}}';
    }

    public function rules(): array
    {
        $rules = [
            [['source_id', 'filename', 'file_hash', 'imported_at'], 'required'],
            [['source_id', 'imported_at', 'rows_total', 'rows_accepted', 'rows_rejected'], 'integer'],
            ['filename', 'string', 'max' => 255],
            ['file_hash', 'string', 'max' => 64],
            ['status', 'default', 'value' => self::STATUS_PROCESSING],
            ['status', 'in', 'range' => [self::STATUS_PROCESSING, self::STATUS_DONE, self::STATUS_FAILED]],
        ];

        // Only add error_message rule if the column exists in the DB
        // (handles test environments where migration may not be applied)
        try {
            if ($this->hasAttribute('error_message')) {
                $rules[] = ['error_message', 'string'];
            }
        } catch (\Throwable $e) {
            // Schema may not be available yet during early app stages
        }

        return $rules;
    }

    public function getSource()
    {
        return $this->hasOne(Source::class, ['id' => 'source_id']);
    }

    public function getKeywords()
    {
        return $this->hasMany(Keyword::class, ['batch_id' => 'id']);
    }

    /**
     * Get error message, or null if column doesn't exist or is empty.
     * Uses direct _attributes access to avoid any recursion with __get().
     */
    public function getErrorText(): ?string
    {
        try {
            if (!$this->hasAttribute('error_message')) {
                return null;
            }
            $attrs = $this->getAttributes();
            $value = $attrs['error_message'] ?? null;
            return $value !== null && $value !== '' ? (string)$value : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Intercept error_message access to return getErrorText().
     * This avoids the fallback to parent::__get() which would throw
     * UnknownPropertyException when the column doesn't exist.
     * Do NOT call getErrorText() here — read _attributes directly to avoid recursion.
     */
    public function __get($name)
    {
        if ($name === 'error_message') {
            try {
                if (!$this->hasAttribute('error_message')) {
                    return null;
                }
                return $this->getAttribute('error_message');
            } catch (\Throwable $e) {
                return null;
            }
        }
        return parent::__get($name);
    }
}
