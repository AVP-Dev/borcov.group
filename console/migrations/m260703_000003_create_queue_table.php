<?php

declare(strict_types=1);

use yii\db\Migration;

class m260703_000003_create_queue_table extends Migration
{
    public function safeUp(): void
    {
        $this->execute("
            CREATE TABLE {{%queue}} (
                id BIGSERIAL PRIMARY KEY,
                channel VARCHAR(255) NOT NULL DEFAULT 'queue',
                job BYTEA NOT NULL,
                created_at BIGINT NOT NULL,
                started_at BIGINT,
                finished_at BIGINT,
                ttr INTEGER NOT NULL,
                attempt INTEGER NOT NULL DEFAULT 0,
                reserved_at BIGINT
            )
        ");

        $this->createIndex('idx_queue_channel', '{{%queue}}', 'channel');
        $this->createIndex('idx_queue_reserved_at', '{{%queue}}', 'reserved_at');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%queue}}');
    }
}
