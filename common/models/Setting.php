<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveRecord;

/**
 * Key-value settings storage.
 *
 * @property string $key
 * @property string $value
 */
class Setting extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%settings}}';
    }

    public function rules(): array
    {
        return [
            [['key', 'value'], 'required'],
            ['key', 'string', 'max' => 64],
            ['value', 'string', 'max' => 1024],
            ['key', 'unique'],
        ];
    }

    public static function get(string $key, string $default = ''): string
    {
        $model = self::findOne($key);
        return $model ? $model->value : $default;
    }

    public static function set(string $key, string $value): void
    {
        $model = self::findOne($key) ?? new self();
        $model->key = $key;
        $model->value = $value;
        $model->save();
    }
}
