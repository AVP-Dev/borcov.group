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

            // Strip UTF-8 BOM from first column
            if (isset($header[0])) {
                $bom = \pack('H*', 'EFBBBF');
                $header[0] = preg_replace("/^$bom/", '', $header[0]);
            }
        }

        $lcHeader = $header ? array_map('mb_strtolower', $header) : [];

        $lineNum = $this->hasHeader ? 1 : 0;
        while (($row = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
            $lineNum++;
            $row = array_map('trim', $row);

            if ($header) {
                $assoc = [];
                foreach ($this->columnMap as $target => $source) {
                    $names = (array)$source;
                    $raw = null;
                    foreach ($names as $name) {
                        $lcName = mb_strtolower($name);
                        $index = array_search($lcName, $lcHeader, true);
                        if ($index !== false && isset($row[$index]) && $row[$index] !== '') {
                            $raw = $row[$index];
                            break;
                        }
                    }
                    $assoc[$target] = $raw;
                }
                yield $assoc;
            } else {
                yield $row;
            }
        }

        fclose($handle);
    }
}
