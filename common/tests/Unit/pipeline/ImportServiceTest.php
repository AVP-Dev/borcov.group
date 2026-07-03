<?php

declare(strict_types=1);

namespace common\tests\Unit\pipeline;

use Codeception\Test\Unit;
use common\components\pipeline\ImportService;
use common\jobs\ImportJob;
use common\models\ImportBatch;
use common\models\Source;
use common\tests\Support\UnitTester;
use Yii;
use yii\queue\db\Queue;

final class ImportServiceTest extends Unit
{
    protected UnitTester $tester;

    public function testImportCreatesBatchAndPushesJob(): void
    {
        $source = $this->ensureSourceExists();

        $service = new ImportService();
        $batch = $service->import(codecept_data_dir() . 'gads.csv', Source::TYPE_GADS);

        $this->assertInstanceOf(ImportBatch::class, $batch);
        verify($batch->id)->notEmpty();
        verify($batch->source_id)->equals($source->id);
        verify($batch->filename)->equals('gads.csv');
        verify($batch->file_hash)->notEmpty();
        verify($batch->status)->equals(ImportBatch::STATUS_PROCESSING);
        verify($batch->rows_total)->equals(0);
        verify($batch->rows_accepted)->equals(0);
        verify($batch->rows_rejected)->equals(0);

        verify($this->tester->grabRecord(ImportBatch::class, ['id' => $batch->id]))->notNull();
    }

    public function testImportThrowsOnUnknownSource(): void
    {
        $service = new ImportService();

        $this->expectException(\InvalidArgumentException::class);
        $service->import(codecept_data_dir() . 'gads.csv', 'nonexistent_source');
    }

    public function testImportThrowsOnMissingFile(): void
    {
        $this->ensureSourceExists();
        $service = new ImportService();

        $this->expectException(\RuntimeException::class);
        $service->import('/tmp/nonexistent_' . uniqid() . '.csv', Source::TYPE_GADS);
    }

    public function testImportIsIdempotentByHash(): void
    {
        $this->ensureSourceExists();
        $service = new ImportService();

        $batch1 = $service->import(codecept_data_dir() . 'gads.csv', Source::TYPE_GADS);
        $batch2 = $service->import(codecept_data_dir() . 'gads.csv', Source::TYPE_GADS);

        verify($batch1->file_hash)->equals($batch2->file_hash);
        verify($batch1->id)->equals($batch2->id);
    }

    public function testImportWithDifferentSources(): void
    {
        $this->ensureSourceExists(Source::TYPE_AHREFS_ORGANIC, 'Ahrefs Organic (test)');
        $this->ensureSourceExists(Source::TYPE_AHREFS_PAID, 'Ahrefs Paid (test)');

        $service = new ImportService();

        $batch1 = $service->import(codecept_data_dir() . 'ahrefs_organic.csv', Source::TYPE_AHREFS_ORGANIC);
        verify($batch1->source->type)->equals(Source::TYPE_AHREFS_ORGANIC);

        $batch2 = $service->import(codecept_data_dir() . 'ahrefs_paid.csv', Source::TYPE_AHREFS_PAID);
        verify($batch2->source->type)->equals(Source::TYPE_AHREFS_PAID);
    }

    public function testImportWithSearchConsoleJson(): void
    {
        $this->ensureSourceExists(Source::TYPE_SEARCH_CONSOLE, 'Search Console (test)');

        $service = new ImportService();
        $batch = $service->import(codecept_data_dir() . 'search_console.json', Source::TYPE_SEARCH_CONSOLE);

        verify($batch->source->type)->equals(Source::TYPE_SEARCH_CONSOLE);
        verify($batch->filename)->equals('search_console.json');
    }

    private function ensureSourceExists(string $type = Source::TYPE_GADS, ?string $name = null): Source
    {
        $source = Source::findOne(['type' => $type]);
        if ($source === null) {
            $source = new Source();
            $source->name = $name ?? match($type) {
                Source::TYPE_GADS => 'Google Ads (test)',
                Source::TYPE_SEARCH_CONSOLE => 'Search Console (test)',
                Source::TYPE_AHREFS_ORGANIC => 'Ahrefs Organic (test)',
                Source::TYPE_AHREFS_PAID => 'Ahrefs Paid (test)',
                default => 'Unknown (test)',
            };
            $source->type = $type;
            $source->created_at = time();
            verify($source->save())->true();
        }
        return $source;
    }
}
