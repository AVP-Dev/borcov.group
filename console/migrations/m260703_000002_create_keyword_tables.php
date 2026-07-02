<?php

declare(strict_types=1);

use yii\db\Migration;

class m260703_000002_create_keyword_tables extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%sources}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'type' => "VARCHAR(20) NOT NULL CHECK (type IN ('gads','search_console','ahrefs_organic','ahrefs_paid'))",
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->createTable('{{%import_batches}}', [
            'id' => $this->primaryKey(),
            'source_id' => $this->integer()->notNull(),
            'filename' => $this->string()->notNull(),
            'file_hash' => $this->string(64)->notNull(),
            'imported_at' => $this->integer()->notNull(),
            'rows_total' => $this->integer()->notNull()->defaultValue(0),
            'rows_accepted' => $this->integer()->notNull()->defaultValue(0),
            'rows_rejected' => $this->integer()->notNull()->defaultValue(0),
            'status' => "VARCHAR(20) NOT NULL DEFAULT 'processing' CHECK (status IN ('processing','done','failed'))",
        ]);

        $this->addForeignKey(
            'fk_import_batches_source',
            '{{%import_batches}}', 'source_id',
            '{{%sources}}', 'id',
            'CASCADE',
        );

        $this->createIndex('idx_import_batches_file_hash', '{{%import_batches}}', 'file_hash');

        $this->createTable('{{%keywords}}', [
            'id' => $this->bigPrimaryKey(),
            'batch_id' => $this->integer()->notNull(),
            'source_id' => $this->integer()->notNull(),
            'raw_text' => $this->text()->notNull(),
            'normalized_text' => $this->text()->notNull(),
            'volume' => $this->integer(),
            'language' => $this->string(5),
            'category' => "VARCHAR(30) NOT NULL DEFAULT 'unclassified' CHECK (category IN ('website_builder','email','domains','accounting','invoicing','reseller','general_brand','unclassified'))",
            'audience_segment' => "VARCHAR(10) CHECK (audience_segment IN ('b2c','b2b'))",
            'intent' => "VARCHAR(15) NOT NULL DEFAULT 'unknown' CHECK (intent IN ('commercial','informational','navigational','unknown'))",
            'is_brand' => $this->boolean()->notNull()->defaultValue(false),
            'is_duplicate_of_id' => $this->bigInteger(),
            'is_forbidden' => $this->boolean()->notNull()->defaultValue(false),
            'is_already_used' => $this->boolean()->notNull()->defaultValue(false),
            'quality_score' => $this->float(),
            'status' => "VARCHAR(20) NOT NULL DEFAULT 'raw' CHECK (status IN ('raw','cleaned','rejected','ready'))",
            'rejection_reason' => $this->text(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_keywords_batch',
            '{{%keywords}}', 'batch_id',
            '{{%import_batches}}', 'id',
            'CASCADE',
        );

        $this->addForeignKey(
            'fk_keywords_source',
            '{{%keywords}}', 'source_id',
            '{{%sources}}', 'id',
            'CASCADE',
        );

        $this->addForeignKey(
            'fk_keywords_duplicate',
            '{{%keywords}}', 'is_duplicate_of_id',
            '{{%keywords}}', 'id',
            'SET NULL',
        );

        $this->createIndex('idx_keywords_normalized_text', '{{%keywords}}', 'normalized_text');
        $this->createIndex('idx_keywords_status', '{{%keywords}}', 'status');
        $this->createIndex('idx_keywords_category', '{{%keywords}}', 'category');
        $this->createIndex('idx_keywords_source_id', '{{%keywords}}', 'source_id');

        $this->createTable('{{%forbidden_terms}}', [
            'id' => $this->primaryKey(),
            'term' => $this->string()->notNull(),
            'match_type' => "VARCHAR(10) NOT NULL DEFAULT 'exact' CHECK (match_type IN ('exact','contains','regex'))",
            'reason' => $this->text(),
        ]);

        $this->createTable('{{%brand_terms}}', [
            'id' => $this->primaryKey(),
            'term' => $this->string()->notNull(),
            'is_own_brand' => $this->boolean()->notNull()->defaultValue(false),
        ]);

        $this->createTable('{{%ad_groups}}', [
            'id' => $this->primaryKey(),
            'category' => "VARCHAR(30) NOT NULL CHECK (category IN ('website_builder','email','domains','accounting','invoicing','reseller','general_brand','unclassified'))",
            'audience_segment' => "VARCHAR(10) CHECK (audience_segment IN ('b2c','b2b'))",
            'language' => $this->string(5)->notNull(),
            'target_url' => $this->string(),
            'theme_label' => $this->string(),
        ]);

        $this->createTable('{{%ad_group_keywords}}', [
            'ad_group_id' => $this->integer()->notNull(),
            'keyword_id' => $this->bigInteger()->notNull(),
            'PRIMARY KEY (ad_group_id, keyword_id)',
        ]);

        $this->addForeignKey(
            'fk_agk_ad_group',
            '{{%ad_group_keywords}}', 'ad_group_id',
            '{{%ad_groups}}', 'id',
            'CASCADE',
        );

        $this->addForeignKey(
            'fk_agk_keyword',
            '{{%ad_group_keywords}}', 'keyword_id',
            '{{%keywords}}', 'id',
            'CASCADE',
        );

        $this->createTable('{{%ads}}', [
            'id' => $this->primaryKey(),
            'ad_group_id' => $this->integer()->notNull(),
            'headline_1' => $this->string(30)->notNull(),
            'headline_2' => $this->string(30)->notNull(),
            'headline_3' => $this->string(30),
            'headline_4' => $this->string(30),
            'headline_5' => $this->string(30),
            'headline_6' => $this->string(30),
            'headline_7' => $this->string(30),
            'headline_8' => $this->string(30),
            'headline_9' => $this->string(30),
            'headline_10' => $this->string(30),
            'headline_11' => $this->string(30),
            'headline_12' => $this->string(30),
            'headline_13' => $this->string(30),
            'headline_14' => $this->string(30),
            'headline_15' => $this->string(30),
            'description_1' => $this->string(90)->notNull(),
            'description_2' => $this->string(90),
            'description_3' => $this->string(90),
            'description_4' => $this->string(90),
            'final_url' => $this->string()->notNull(),
            'path_1' => $this->string(15),
            'path_2' => $this->string(15),
            'status' => "VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','ready','exported'))",
        ]);

        $this->addForeignKey(
            'fk_ads_ad_group',
            '{{%ads}}', 'ad_group_id',
            '{{%ad_groups}}', 'id',
            'CASCADE',
        );

        $this->createTable('{{%export_batches}}', [
            'id' => $this->primaryKey(),
            'created_at' => $this->integer()->notNull(),
            'file_path' => $this->string(),
            'ads_count' => $this->integer()->notNull()->defaultValue(0),
            'keywords_count' => $this->integer()->notNull()->defaultValue(0),
        ]);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%export_batches}}');
        $this->dropTable('{{%ads}}');
        $this->dropTable('{{%ad_group_keywords}}');
        $this->dropTable('{{%ad_groups}}');
        $this->dropTable('{{%brand_terms}}');
        $this->dropTable('{{%forbidden_terms}}');
        $this->dropTable('{{%keywords}}');
        $this->dropTable('{{%import_batches}}');
        $this->dropTable('{{%sources}}');
    }
}
