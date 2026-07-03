<?php

declare(strict_types=1);

namespace common\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use common\models\ImportBatch;
use common\models\Keyword;
use common\components\pipeline\SourceAdapterInterface;
use common\components\pipeline\CsvAdapter;
use common\components\pipeline\JsonAdapter;
use common\jobs\CleanJob;

class ImportJob extends BaseObject implements JobInterface
{
    public int $batchId;
    public string $filePath;

    public function execute($queue): void
    {
        try {
            $this->doExecute($queue);
        } catch (\Throwable $e) {
            Yii::error("ImportJob #{$this->batchId} failed: " . $e->getMessage(), __METHOD__);
            $batch = ImportBatch::findOne($this->batchId);
            if ($batch !== null && $batch->status !== ImportBatch::STATUS_DONE) {
                $batch->rows_rejected = $batch->rows_total;
                $this->failBatch($batch, mb_substr($e->getMessage(), 0, 500));
            }
            throw $e;
        }
    }

    private function doExecute($queue): void
    {
        $batch = ImportBatch::findOne($this->batchId);
        if ($batch === null) {
            throw new \RuntimeException("ImportBatch #{$this->batchId} not found");
        }

        $source = $batch->source;
        if ($source === null) {
            $this->failBatch($batch, 'Source not found');
            return;
        }

        $adapter = $this->createAdapter($source->type);
        $db = Yii::$app->db;
        $total = 0;
        $accepted = 0;
        $rejected = 0;
        $firstRowKeys = [];

        foreach ($adapter->parse($this->filePath) as $row) {
            $total++;
            if ($total === 1) {
                $firstRowKeys = array_keys($row);
            }

            $rawText = trim($row['keyword'] ?? '');
            if ($rawText === '') {
                $rejected++;
                if ($rejected <= 3) {
                    Yii::warning("ImportJob #{$this->batchId}: row #{$total} has empty keyword. Available columns: " . json_encode(array_keys($row), JSON_UNESCAPED_UNICODE) . ". Values: " . json_encode($row, JSON_UNESCAPED_UNICODE), __METHOD__);
                }
                continue;
            }

            $normalizedText = $this->normalizeText($rawText);
            $volume = $this->parseVolume($row['volume'] ?? null);

            $now = time();
            try {
                $db->createCommand("
                    INSERT INTO {{%keywords}}
                        (batch_id, source_id, raw_text, normalized_text, volume, status, created_at, updated_at)
                    VALUES (:batchId, :sourceId, :rawText, :normalizedText, :volume, :status, :createdAt, :updatedAt)
                    ON CONFLICT (normalized_text, source_id) DO UPDATE SET
                        batch_id      = EXCLUDED.batch_id,
                        raw_text      = EXCLUDED.raw_text,
                        volume        = COALESCE(EXCLUDED.volume, keywords.volume),
                        updated_at    = EXCLUDED.updated_at
                ", [
                    ':batchId' => $batch->id,
                    ':sourceId' => $source->id,
                    ':rawText' => $rawText,
                    ':normalizedText' => $normalizedText,
                    ':volume' => $volume,
                    ':status' => Keyword::STATUS_RAW,
                    ':createdAt' => $now,
                    ':updatedAt' => $now,
                ])->execute();
                $accepted++;
            } catch (\yii\db\Exception $e) {
                $rejected++;
            }
        }

        $batch->rows_total = $total;
        $batch->rows_accepted = $accepted;
        $batch->rows_rejected = $rejected;

        if ($total > 0 && $accepted === 0) {
            $this->failBatch($batch, 'All ' . $total . ' rows rejected — no keyword column. Available: ' . implode(', ', $firstRowKeys));
            $this->cleanupTempFile();
            return;
        }

        $batch->status = ImportBatch::STATUS_DONE;
        $batch->save();

        $this->cleanupTempFile();

        Yii::$app->queue->push(new CleanJob([
            'batchId' => (int)$batch->id,
        ]));
    }

    private function cleanupTempFile(): void
    {
        if ($this->filePath !== '' && file_exists($this->filePath)) {
            @unlink($this->filePath);
        }
    }

    private function createAdapter(string $sourceType): SourceAdapterInterface
    {
        $ext = strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION));

        if ($ext === 'json') {
            return match ($sourceType) {
                'search_console' => new JsonAdapter([
                    'fieldMap' => [
                        'keyword' => ['keys.0', 'keyword', 'query', 'term', 'name'],
                        'volume' => ['impressions', 'volume', 'clicks', 'count', 'search_volume'],
                    ],
                ]),
                default => new JsonAdapter([
                    'fieldMap' => [
                        'keyword' => ['keyword', 'query', 'keys.0', 'name', 'term', 'key'],
                        'volume' => ['volume', 'impressions', 'clicks', 'count', 'search_volume', 'traffic'],
                    ],
                ]),
            };
        }

        return match ($sourceType) {
            'gads' => new CsvAdapter([
                'delimiter' => ',',
                'columnMap' => [
                    'keyword' => ['Keyword', 'keyword', 'Search term', 'Keyword text', 'Key phrase'],
                    'volume' => ['Volume', 'volume', 'Avg. monthly searches', 'Impressions', 'impressions'],
                ],
            ]),
            'ahrefs_organic' => new CsvAdapter([
                'delimiter' => ',',
                'columnMap' => [
                    'keyword' => ['Keyword', 'keyword'],
                    'volume' => ['Volume', 'volume'],
                ],
            ]),
            'ahrefs_paid' => new CsvAdapter([
                'delimiter' => ',',
                'columnMap' => [
                    'keyword' => ['Keyword', 'keyword'],
                    'volume' => ['Volume', 'volume'],
                ],
            ]),
            'search_console' => new CsvAdapter([
                'delimiter' => ',',
                'columnMap' => [
                    'keyword' => ['Search query', 'Поисковый запрос', 'Top queries', 'Query', 'запрос', 'keyword', 'Keyword'],
                    'volume' => ['Impressions', 'Показы', 'Volume', 'volume', 'impressions'],
                ],
            ]),
            default => throw new \InvalidArgumentException("Unknown source type: $sourceType"),
        };
    }

    private function normalizeText(string $text): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $text)));
    }

    private function failBatch(ImportBatch $batch, string $reason): void
    {
        $batch->status = ImportBatch::STATUS_FAILED;
        $batch->error_message = $reason;
        try {
            $batch->save();
        } catch (\Throwable $e) {
            Yii::warning("Could not save error_message (column may not exist): " . $e->getMessage(), __METHOD__);
        }
        Yii::error("ImportJob #{$this->batchId} failed: $reason", __METHOD__);
    }

    /**
     * Parse volume string into nullable integer.
     * Handles: "1,200", "12 500", "1.2K", "1.5M", "N/A", "-", ""
     */
    private function parseVolume(?string $raw): ?int
    {
        if ($raw === null || $raw === '' || $raw === '-' || strtoupper($raw) === 'N/A') {
            return null;
        }

        $val = str_replace([',', ' ', '_'], '', $raw);

        if (preg_match('/^(\d+(\.\d+)?)\s*([kKmM])?$/', $val, $m)) {
            $num = (float)$m[1];
            if (isset($m[3])) {
                $mult = strtolower($m[3]) === 'k' ? 1000 : 1000000;
                $num *= $mult;
            }
            return (int)round($num);
        }

        // Fallback: try direct integer cast
        $int = (int)$raw;
        return $int === 0 && $raw !== '0' ? null : $int;
    }
}
