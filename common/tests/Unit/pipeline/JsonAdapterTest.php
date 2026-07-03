<?php

declare(strict_types=1);

namespace common\tests\Unit\pipeline;

use Codeception\Test\Unit;
use common\components\pipeline\JsonAdapter;
use common\tests\Support\UnitTester;

final class JsonAdapterTest extends Unit
{
    protected UnitTester $tester;

    public function testParseSearchConsoleJson(): void
    {
        $adapter = new JsonAdapter([
            'fieldMap' => ['keyword' => 'query', 'volume' => 'impressions'],
        ]);

        $rows = iterator_to_array($adapter->parse(codecept_data_dir() . 'search_console.json'));

        verify($rows)->notEmpty();
        verify($rows[0]['keyword'])->equals('how to make a website');
        verify($rows[0]['volume'])->equals('12500');
    }

    public function testParseReturnsAllRows(): void
    {
        $adapter = new JsonAdapter([
            'fieldMap' => ['keyword' => 'query', 'volume' => 'impressions'],
        ]);

        $rows = iterator_to_array($adapter->parse(codecept_data_dir() . 'search_console.json'));

        verify($rows)->arrayCount(5);
    }

    public function testParseWithCustomFieldMap(): void
    {
        $adapter = new JsonAdapter([
            'fieldMap' => ['keyword' => 'query', 'clicks' => 'clicks'],
        ]);

        $rows = iterator_to_array($adapter->parse(codecept_data_dir() . 'search_console.json'));

        verify($rows[0]['keyword'])->equals('how to make a website');
        verify($rows[0]['clicks'])->equals(450);
    }

    public function testParseNonExistentFile(): void
    {
        $adapter = new JsonAdapter();

        $this->expectException(\RuntimeException::class);
        iterator_to_array($adapter->parse('/tmp/nonexistent_' . uniqid() . '.json'));
    }

    public function testParseInvalidJson(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'json_test_');
        file_put_contents($tmpFile, '{invalid json}');

        $adapter = new JsonAdapter();

        $this->expectException(\RuntimeException::class);
        try {
            iterator_to_array($adapter->parse($tmpFile));
        } finally {
            unlink($tmpFile);
        }
    }

    public function testParseHandlesRussianQueries(): void
    {
        $adapter = new JsonAdapter([
            'fieldMap' => ['keyword' => 'query', 'volume' => 'impressions'],
        ]);

        $rows = iterator_to_array($adapter->parse(codecept_data_dir() . 'search_console.json'));

        $ruRows = array_filter($rows, fn($r) => $r['keyword'] === 'конструктор интернет магазина');
        verify($ruRows)->notEmpty();
    }
}
