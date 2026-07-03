<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveRecord;

class ExportBatch extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%export_batches}}';
    }

    public function rules(): array
    {
        return [
            [['created_at'], 'required'],
            [['created_at', 'ads_count', 'keywords_count'], 'integer'],
            ['file_path', 'string', 'max' => 255],
            ['ads_count', 'default', 'value' => 0],
            ['keywords_count', 'default', 'value' => 0],
        ];
    }
}
