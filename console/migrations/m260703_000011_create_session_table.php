<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Creates session table for DbSession storage.
 * Sessions survive container restarts when stored in PostgreSQL.
 */
final class m260703_000011_create_session_table extends Migration
{
    public function up(): void
    {
        $this->createTable('{{%session}}', [
            'id' => $this->string()->notNull(),
            'expire' => $this->integer(),
            'data' => $this->binary(),
            'PRIMARY KEY ([[id]])',
        ]);
    }

    public function down(): void
    {
        $this->dropTable('{{%session}}');
    }
}
