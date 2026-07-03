<?php

declare(strict_types=1);

use yii\db\Migration;

class m260703_000006_fix_queue_schema extends Migration
{
    public function safeUp(): void
    {
        $this->renameColumn('{{%queue}}', 'created_at', 'pushed_at');
        $this->addColumn('{{%queue}}', 'delay', $this->integer()->notNull()->defaultValue(0));
        $this->addColumn('{{%queue}}', 'priority', $this->integer()->notNull()->defaultValue(1024));
    }

    public function safeDown(): void
    {
        $this->renameColumn('{{%queue}}', 'pushed_at', 'created_at');
        $this->dropColumn('{{%queue}}', 'delay');
        $this->dropColumn('{{%queue}}', 'priority');
    }
}
