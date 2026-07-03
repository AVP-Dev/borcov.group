<?php

declare(strict_types=1);

namespace common\components\pipeline;

use yii\base\BaseObject;

class CsvAdapter extends BaseObject implements SourceAdapterInterface
{
    public string $delimiter = ',';
    public string $enclosure = '"';
    public string $escape = '\\';
    public bool $hasHeader = true;
    public array $columnMap = [
        'keyword' => 'Keyword',
        'volume' => 'Volume',
    ];

    public function parse(string $filePath): iterable
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \RuntimeException("Cannot open file: $filePath");
        }
        $handle = fopen($filePath, 'rb');

        $header = null;
        if ($this->hasHeader) {
            $header = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape);
            if ($header === false) {
                fclose($handle);
                throw new \RuntimeException('Cannot read CSV header');
            }
            $header = array_map('trim', $header);
        }

        $lcHeader = $header ? array_map('mb_strtolower', $header) : [];

        $lineNum = $this->hasHeader ? 1 : 0;
        while (($row = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
            $lineNum++;
            $row = array_map('trim', $row);

            if ($header) {
                $assoc = [];
                foreach ($this->columnMap as $target => $source) {
                    $lcSource = mb_strtolower($source);
                    $index = array_search($lcSource, $lcHeader, true);
                    $raw = $index !== false && isset($row[$index]) ? $row[$index] : null;
                    $assoc[$target] = ($raw !== null && $raw !== '') ? $raw : null;
                }
                yield $assoc;
            } else {
                yield $row;
            }
        }

        fclose($handle);
    }
}
