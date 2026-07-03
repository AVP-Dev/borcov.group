<?php

declare(strict_types=1);

use yii\db\Migration;

class m260703_000010_cleanup_excess_ads extends Migration
{
    public function safeUp(): void
    {
        $db = $this->getDb();
        $command = $db->createCommand("
            WITH ranked AS (
                SELECT id, ad_group_id,
                       ROW_NUMBER() OVER (PARTITION BY ad_group_id ORDER BY id) AS rn
                FROM {{%ads}}
            )
            DELETE FROM {{%ads}}
            WHERE id IN (
                SELECT id FROM ranked WHERE rn > 3
            )
        ");
        $deleted = $command->execute();
        echo "    > cleaned up {$deleted} excess ads (kept 3 per group)\n";
    }

    public function safeDown(): void
    {
    }
}
