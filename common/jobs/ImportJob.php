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
            $batch = ImportBatch::findOne($this->batchId);
            if ($batch !== null) {
                $this->failBatch($batch, $e->getMessage());
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

        foreach ($adapter->parse($this->filePath) as $row) {
            $total++;

            $rawText = trim($row['keyword'] ?? '');
            if ($rawText === '') {
                $rejected++;
                continue;
            }

            $normalizedText = $this->normalizeText($rawText);
            $volume = isset($row['volume']) ? (int)$row['volume'] : null;

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
        $batch->status = ImportBatch::STATUS_DONE;
        $batch->save();

        Yii::$app->queue->push(new CleanJob([
            'batchId' => (int)$batch->id,
        ]));
    }

    private function createAdapter(string $sourceType): SourceAdapterInterface
    {
        return match ($sourceType) {
            'gads' => new CsvAdapter([
                'delimiter' => ',',
                'columnMap' => ['keyword' => 'Keyword', 'volume' => 'Volume'],
            ]),
            'ahrefs_organic' => new CsvAdapter([
                'delimiter' => ',',
                'columnMap' => ['keyword' => 'Keyword', 'volume' => 'Volume'],
            ]),
            'ahrefs_paid' => new CsvAdapter([
                'delimiter' => ',',
                'columnMap' => ['keyword' => 'Keyword', 'volume' => 'Volume'],
            ]),
            'search_console' => new JsonAdapter([
                'fieldMap' => ['keyword' => 'keys.0', 'volume' => 'impressions'],
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
        $batch->save();
    }

}
