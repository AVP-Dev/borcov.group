<?php

declare(strict_types=1);

use yii\db\Migration;

class m260703_000005_add_import_unique_constraints extends Migration
{
    public function safeUp(): void
    {
        $this->createIndex('uq_import_batches_file_hash', '{{%import_batches}}', 'file_hash', true);
        $this->createIndex('uq_keywords_normalized_text_source', '{{%keywords}}', ['normalized_text', 'source_id'], true);
    }

    public function safeDown(): void
    {
        $this->dropIndex('uq_import_batches_file_hash', '{{%import_batches}}');
        $this->dropIndex('uq_keywords_normalized_text_source', '{{%keywords}}');
    }
}
