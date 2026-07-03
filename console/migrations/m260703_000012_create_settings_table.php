<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Creates settings table + seeds default values.
 */
final class m260703_000012_create_settings_table extends Migration
{
    public function up(): void
    {
        $this->createTable('{{%settings}}', [
            'key' => $this->string(64)->notNull(),
            'value' => $this->string(1024)->notNull()->defaultValue(''),
            'PRIMARY KEY ([[key]])',
        ]);

        $this->batchInsert('{{%settings}}', ['key', 'value'], [
            ['pipeline.volume.min', '10'],
            ['pipeline.volume.min_source_count', '3'],
            ['pipeline.dedup.similarity_threshold', '0.6'],
        ]);
    }

    public function down(): void
    {
        $this->dropTable('{{%settings}}');
    }
}
