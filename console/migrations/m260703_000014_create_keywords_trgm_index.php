<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Create GIN index on keywords.normalized_text for pg_trgm similarity searches.
 * Accelerates DeduplicationService (fuzzy match) and GapAnalysisService.
 */
final class m260703_000014_create_keywords_trgm_index extends Migration
{
    public function up(): bool
    {
        $this->execute('CREATE INDEX IF NOT EXISTS keywords_trgm_idx ON {{%keywords}} USING GIN (normalized_text gin_trgm_ops)');
        return true;
    }

    public function down(): bool
    {
        $this->execute('DROP INDEX IF EXISTS keywords_trgm_idx');
        return true;
    }
}
