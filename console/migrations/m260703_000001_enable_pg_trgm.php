<?php

declare(strict_types=1);

use yii\db\Migration;

class m260703_000001_enable_pg_trgm extends Migration
{
    public function safeUp(): void
    {
        $this->execute('CREATE EXTENSION IF NOT EXISTS pg_trgm');
    }

    public function safeDown(): void
    {
        $this->execute('DROP EXTENSION IF EXISTS pg_trgm');
    }
}
