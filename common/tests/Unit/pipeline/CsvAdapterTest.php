<?php

declare(strict_types=1);

namespace common\tests\Unit\pipeline;

use Codeception\Test\Unit;
use common\components\pipeline\CsvAdapter;
use common\tests\Support\UnitTester;

final class CsvAdapterTest extends Unit
{
    protected UnitTester $tester;

    public function testParseGadsCsv(): void
    {
        $adapter = new CsvAdapter([
            'columnMap' => ['keyword' => 'Keyword', 'volume' => 'Volume'],
        ]);

        $rows = iterator_to_array($adapter->parse(codecept_data_dir() . 'gads.csv'));

        verify($rows)->notEmpty();
        verify($rows[0]['keyword'])->equals('website builder');
        verify($rows[0]['volume'])->equals('1200');
        verify($rows[1]['keyword'])->equals('email marketing');
    }

    public function testParseReturnsAllRows(): void
    {
        $adapter = new CsvAdapter([
            'columnMap' => ['keyword' => 'Keyword', 'volume' => 'Volume'],
        ]);

        $rows = iterator_to_array($adapter->parse(codecept_data_dir() . 'gads.csv'));

        verify($rows)->arrayCount(9);
    }

    public function testParseHandlesQuotedFields(): void
    {
        $adapter = new CsvAdapter([
            'columnMap' => ['keyword' => 'Keyword', 'volume' => 'Volume'],
        ]);

        $rows = iterator_to_array($adapter->parse(codecept_data_dir() . 'gads.csv'));

        $premiumRow = array_filter($rows, fn($r) => $r['keyword'] === 'website builder, premium');
        verify($premiumRow)->notEmpty();
    }

    public function testParseAhrefsOrganic(): void
    {
        $adapter = new CsvAdapter([
            'columnMap' => ['keyword' => 'Keyword', 'volume' => 'Volume'],
        ]);

        $rows = iterator_to_array($adapter->parse(codecept_data_dir() . 'ahrefs_organic.csv'));

        verify($rows)->arrayCount(5);
        verify($rows[0]['keyword'])->equals('landing page builder');
        verify($rows[0]['volume'])->equals('1800');
    }

    public function testParseAhrefsPaid(): void
    {
        $adapter = new CsvAdapter([
            'columnMap' => ['keyword' => 'Keyword', 'volume' => 'Volume'],
        ]);

        $rows = iterator_to_array($adapter->parse(codecept_data_dir() . 'ahrefs_paid.csv'));

        verify($rows)->arrayCount(5);
        verify($rows[0]['keyword'])->equals('контекстная реклама');
        verify($rows[0]['volume'])->equals('600');
    }

    public function testParseHandlesMissingVolume(): void
    {
        $adapter = new CsvAdapter([
            'columnMap' => ['keyword' => 'Keyword', 'volume' => 'Volume'],
        ]);

        $rows = iterator_to_array($adapter->parse(codecept_data_dir() . 'gads.csv'));

        $lastRow = $rows[8];
        verify($lastRow['keyword'])->equals('website builder');
        verify($lastRow['volume'])->null();
    }

    public function testParseNonExistentFile(): void
    {
        $adapter = new CsvAdapter();

        $this->expectException(\RuntimeException::class);
        iterator_to_array($adapter->parse('/tmp/nonexistent_' . uniqid() . '.csv'));
    }

    public function testParseSearchConsoleCsv(): void
    {
        $adapter = new CsvAdapter([
            'columnMap' => ['keyword' => 'Search query', 'volume' => 'Impressions'],
        ]);

        $rows = iterator_to_array($adapter->parse(codecept_data_dir() . 'search_console.csv'));

        verify($rows)->arrayCount(5);
        verify($rows[0]['keyword'])->equals('how to make a website');
        verify($rows[0]['volume'])->equals('12500');
        verify($rows[1]['keyword'])->equals('best hosting for small business');
    }

    public function testParseSearchConsoleCsvCaseInsensitive(): void
    {
        $adapter = new CsvAdapter([
            'columnMap' => ['keyword' => 'SEARCH QUERY', 'volume' => 'impressions'],
        ]);

        $rows = iterator_to_array($adapter->parse(codecept_data_dir() . 'search_console.csv'));

        verify($rows)->arrayCount(5);
        verify($rows[0]['keyword'])->equals('how to make a website');
    }
}
