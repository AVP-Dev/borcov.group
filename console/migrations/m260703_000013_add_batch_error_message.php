<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260703_000013_add_batch_error_message extends Migration
{
    public function up(): void
    {
        $this->addColumn('{{%import_batches}}', 'error_message', $this->text());
    }

    public function down(): void
    {
        $this->dropColumn('{{%import_batches}}', 'error_message');
    }
}
