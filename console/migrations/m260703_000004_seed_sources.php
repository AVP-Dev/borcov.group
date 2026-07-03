<?php

declare(strict_types=1);

use yii\db\Migration;

class m260703_000004_seed_sources extends Migration
{
    public function safeUp(): void
    {
        $count = (new \yii\db\Query())
            ->from('{{%sources}}')
            ->count();

        if ($count > 0) {
            return;
        }

        $now = time();
        $this->batchInsert('{{%sources}}', ['name', 'type', 'created_at'], [
            ['Google Ads', 'gads', $now],
            ['Search Console', 'search_console', $now],
            ['Ahrefs Organic', 'ahrefs_organic', $now],
            ['Ahrefs Paid', 'ahrefs_paid', $now],
        ]);
    }

    public function safeDown(): void
    {
        $this->delete('{{%sources}}');
    }
}
