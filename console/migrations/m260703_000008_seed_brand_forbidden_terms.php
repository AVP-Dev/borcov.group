<?php

declare(strict_types=1);

use yii\db\Migration;

class m260703_000008_seed_brand_forbidden_terms extends Migration
{
    public function safeUp(): void
    {
        $brandCount = (new \yii\db\Query())
            ->from('{{%brand_terms}}')
            ->count();

        if ($brandCount === 0) {
            $this->batchInsert('{{%brand_terms}}', ['term', 'is_own_brand'], [
                ['site pro', true],
                ['sitepro', true],
                ['site.pro', true],
                ['wix', false],
                ['wordpress', false],
                ['squarespace', false],
                ['weebly', false],
                ['jimdo', false],
                ['webflow', false],
                ['duda', false],
                ['strikingly', false],
                ['tilda', false],
                ['ucraft', false],
                ['godaddy', false],
            ]);
        }

        $forbiddenCount = (new \yii\db\Query())
            ->from('{{%forbidden_terms}}')
            ->count();

        if ($forbiddenCount === 0) {
            $this->batchInsert('{{%forbidden_terms}}', ['term', 'match_type', 'reason'], [
                ['crack', 'contains', 'Software piracy'],
                ['nulled', 'contains', 'Software piracy'],
                ['xxx', 'contains', 'Adult content'],
                ['porn', 'contains', 'Adult content'],
                ['sex', 'exact', 'Adult content'],
                ['nude', 'contains', 'Adult content'],
                ['escort', 'contains', 'Adult content'],
                ['casino', 'contains', 'Gambling'],
                ['gambling', 'contains', 'Gambling'],
                ['bet', 'exact', 'Gambling'],
                ['таблетки', 'contains', 'Pharmacy spam'],
                ['купить', 'exact', 'Transactional intent irrelevant'],
            ]);
        }
    }

    public function safeDown(): void
    {
        $this->delete('{{%brand_terms}}');
        $this->delete('{{%forbidden_terms}}');
    }
}
