<?php

declare(strict_types=1);

use yii\db\Migration;

class m260703_000007_rename_queue_finished_at_to_done_at extends Migration
{
    public function safeUp(): void
    {
        $this->renameColumn('{{%queue}}', 'finished_at', 'done_at');
    }

    public function safeDown(): void
    {
        $this->renameColumn('{{%queue}}', 'done_at', 'finished_at');
    }
}
