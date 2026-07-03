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

    /**
     * @var bool if true, auto-detect keyword column when no known name matches
     */
    public bool $autoDetectKeyword = true;

    /**
     * @var int maximum sample rows to check for keyword auto-detection
     */
    public int $autoDetectSampleRows = 5;

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

        // Build column index: for each target (keyword, volume), find the matching column index
        $columnIndex = [];
        foreach ($this->columnMap as $target => $source) {
            $names = (array)$source;
            $index = null;
            foreach ($names as $name) {
                $lcName = mb_strtolower($name);
                $idx = array_search($lcName, $lcHeader, true);
                if ($idx !== false) {
                    $index = $idx;
                    break;
                }
            }
            $columnIndex[$target] = $index;
        }

        // Auto-detect keyword column if not found and enabled
        if ($this->autoDetectKeyword && $header !== null && $columnIndex['keyword'] === null) {
            $columnIndex['keyword'] = $this->autoDetectKeywordColumn($handle, $header);
        }

        $lineNum = $this->hasHeader ? 1 : 0;
        // Re-read: we may have consumed sample rows during auto-detection
        // so rewind and skip header again
        rewind($handle);
        if ($this->hasHeader) {
            fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape);
        }

        while (($row = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
            $lineNum++;
            $row = array_map('trim', $row);

            if ($header) {
                $assoc = [];
                foreach ($this->columnMap as $target => $source) {
                    $idx = $columnIndex[$target] ?? null;
                    $raw = null;
                    if ($idx !== null && isset($row[$idx]) && $row[$idx] !== '') {
                        $raw = $row[$idx];
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

    /**
     * Try to find the keyword column by examining data in each column.
     * Looks for a column where most values are non-numeric text (looks like keywords).
     */
    private function autoDetectKeywordColumn($handle, array $header): ?int
    {
        $samples = [];
        $readCount = 0;
        while (($row = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false && $readCount < $this->autoDetectSampleRows) {
            $samples[] = array_map('trim', $row);
            $readCount++;
        }

        if ($samples === []) {
            return null;
        }

        $colCount = count($header);
        $scores = array_fill(0, $colCount, 0);

        foreach ($samples as $row) {
            for ($i = 0; $i < $colCount; $i++) {
                $val = $row[$i] ?? '';
                if ($val === '') {
                    continue;
                }
                // +1 for containing letters
                if (preg_match('/\p{L}/u', $val)) {
                    $scores[$i]++;
                }
                // +1 for containing space (multi-word keyword)
                if (str_contains($val, ' ')) {
                    $scores[$i]++;
                }
                // -1 for purely numeric
                if (preg_match('/^\d+$/', $val)) {
                    $scores[$i]--;
                }
                // -1 for looking like a URL
                if (str_starts_with($val, 'http') || str_starts_with($val, '/')) {
                    $scores[$i]--;
                }
            }
        }

        // Pick column with highest score, minimum score of 1
        arsort($scores);
        $bestScore = reset($scores);
        $bestCol = key($scores);

        if ($bestScore >= 1) {
            return (int)$bestCol;
        }

        // Fallback: first column that has any non-numeric values
        foreach ($samples as $row) {
            for ($i = 0; $i < $colCount; $i++) {
                $val = $row[$i] ?? '';
                if ($val !== '' && preg_match('/\p{L}/u', $val)) {
                    return $i;
                }
            }
        }

        return null;
    }
}
