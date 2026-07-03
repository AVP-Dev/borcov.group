<?php

declare(strict_types=1);

use yii\db\Migration;

class m260703_000009_add_ad_generator_column extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%ads}}', 'generator', $this->string(20)->notNull()->defaultValue('template'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%ads}}', 'generator');
    }
}
