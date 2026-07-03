<?php

declare(strict_types=1);

use yii\base\Event;
use common\components\pipeline\ImportService;
use common\components\pipeline\CleaningService;
use common\components\pipeline\ClassificationService;
use common\components\pipeline\ExportService;

Yii::setAlias('@common', dirname(__DIR__));
Yii::setAlias('@frontend', dirname(dirname(__DIR__)) . '/frontend');
Yii::setAlias('@backend', dirname(dirname(__DIR__)) . '/backend');
Yii::setAlias('@console', dirname(dirname(__DIR__)) . '/console');

/**
 * Wire pipeline event listeners.
 * Logger shows that the event mechanism works and provides observability.
 */
Event::on(ImportService::class, ImportService::EVENT_AFTER_IMPORT, function (Event $event) {
    /** @var common\models\ImportBatch|null $batch */
    $batch = $event->sender;
    if ($batch) {
        Yii::info("Pipeline: import completed for batch #{$batch->id} ({$batch->filename}), accepted: {$batch->rows_accepted}", __METHOD__);
    }
});

Event::on(CleaningService::class, CleaningService::EVENT_AFTER_CLEANING, function (Event $event) {
    /** @var common\models\ImportBatch|null $batch */
    $batch = $event->sender;
    if ($batch) {
        Yii::info("Pipeline: cleaning completed for batch #{$batch->id}, rejected: {$batch->rows_rejected}", __METHOD__);
    }
});

Event::on(ClassificationService::class, ClassificationService::EVENT_AFTER_CLASSIFICATION, function (Event $event) {
    /** @var common\models\ImportBatch|null $batch */
    $batch = $event->sender;
    if ($batch) {
        Yii::info("Pipeline: classification completed for batch #{$batch->id}", __METHOD__);
    }
});

Event::on(ExportService::class, ExportService::EVENT_AFTER_EXPORT, function (Event $event) {
    /** @var common\models\ExportBatch|null $exportBatch */
    $exportBatch = $event->sender;
    if ($exportBatch) {
        Yii::info("Pipeline: export completed — {$exportBatch->ads_count} ads, {$exportBatch->keywords_count} keywords", __METHOD__);
    }
});
