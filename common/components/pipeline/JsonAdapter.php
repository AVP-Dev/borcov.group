<?php

declare(strict_types=1);

namespace common\components\pipeline;

use yii\base\BaseObject;

class JsonAdapter extends BaseObject implements SourceAdapterInterface
{
    public array $fieldMap = [
        'keyword' => 'query',
        'volume' => 'impressions',
    ];

    public function parse(string $filePath): iterable
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \RuntimeException("Cannot read file: $filePath");
        }
        $content = file_get_contents($filePath);

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON: ' . json_last_error_msg());
        }

        $rows = $this->extractRows($data);

        foreach ($rows as $row) {
            $mapped = [];
            foreach ($this->fieldMap as $target => $source) {
                $mapped[$target] = $this->getNestedValue($row, $source);
            }
            yield $mapped;
        }
    }

    private function getNestedValue(array $row, string $path): mixed
    {
        if (!str_contains($path, '.')) {
            return $row[$path] ?? null;
        }
        $parts = explode('.', $path);
        $current = $row;
        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return null;
            }
            $current = $current[$part];
        }
        return $current;
    }

    private function extractRows(array $data): array
    {
        if (isset($data['rows']) && is_array($data['rows'])) {
            return $data['rows'];
        }
        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }
        if (isset($data['items']) && is_array($data['items'])) {
            return $data['items'];
        }
        if (array_is_list($data)) {
            return $data;
        }
        return $data;
    }
}
