<?php

declare(strict_types=1);

namespace common\tests\Unit\pipeline;

use Codeception\Test\Unit;
use common\components\pipeline\ExportService;
use common\models\Ad;
use common\models\AdGroup;
use common\models\AdGroupKeyword;
use common\models\ExportBatch;
use common\models\ImportBatch;
use common\models\Keyword;
use common\models\Source;
use common\tests\Support\UnitTester;
use Yii;

final class ExportServiceTest extends Unit
{
    protected UnitTester $tester;

    protected function _setUp(): void
    {
        parent::_setUp();
        $this->seedData();
    }

    private function seedData(): void
    {
        $existing = Source::findOne(['type' => 'gads']);
        if ($existing === null) {
            $s = new Source();
            $s->name = 'Google Ads';
            $s->type = 'gads';
            $s->created_at = time();
            $s->save();
        }
        $sourceId = Source::findOne(['type' => 'gads'])->id;

        if (ImportBatch::findOne(900) === null) {
            $b = new \common\models\ImportBatch();
            $b->id = 900;
            $b->source_id = $sourceId;
            $b->filename = 'export_test.csv';
            $b->file_hash = 'export_test';
            $b->imported_at = time();
            $b->status = \common\models\ImportBatch::STATUS_DONE;
            $b->save();
        }

        if (AdGroup::find()->count() === 0) {
            $group = new AdGroup();
            $group->category = Keyword::CATEGORY_WEBSITE_BUILDER;
            $group->audience_segment = Keyword::AUDIENCE_B2C;
            $group->language = 'en';
            $group->theme_label = 'Test Group';
            $group->target_url = 'https://site.pro/website-builder';
            $group->save();

            $kw = new Keyword();
            $kw->batch_id = 900;
            $kw->source_id = $sourceId;
            $kw->raw_text = 'best website builder';
            $kw->normalized_text = 'best website builder';
            $kw->volume = 500;
            $kw->category = Keyword::CATEGORY_WEBSITE_BUILDER;
            $kw->audience_segment = Keyword::AUDIENCE_B2C;
            $kw->language = 'en';
            $kw->status = Keyword::STATUS_READY;
            $kw->save();

            $link = new AdGroupKeyword();
            $link->ad_group_id = $group->id;
            $link->keyword_id = $kw->id;
            $link->save();

            $ad = new Ad();
            $ad->ad_group_id = $group->id;
            $ad->headline_1 = 'Build Your Site';
            $ad->headline_2 = 'site.pro Builder';
            $ad->description_1 = 'Create a website with site.pro.';
            $ad->final_url = 'https://site.pro/website-builder';
            $ad->path_1 = 'website-builder';
            $ad->generator = 'template';
            $ad->status = Ad::STATUS_DRAFT;
            $ad->save();
        }
    }

    public function testExportGeneratesCsvWithCorrectHeaders(): void
    {
        $service = new ExportService();
        [$filePath, $adsCount, $keywordsCount] = $service->export();

        verify($adsCount)->greaterThan(0);
        verify($keywordsCount)->greaterThan(0);
        verify(file_exists($filePath))->true();

        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle);
        fclose($handle);

        verify($headers[0])->equals('Campaign');
        verify($headers[5])->equals('Match Type');
        verify($headers[6])->equals('Headline 1');
        verify($headers[21])->equals('Description 1');
        verify($headers[25])->equals('Final URL');
        verify($headers[26])->equals('Path 1');
        verify($headers[27])->equals('Path 2');

        @unlink($filePath);
    }

    public function testExportCreatesExportBatchRecord(): void
    {
        ExportBatch::deleteAll();
        $service = new ExportService();
        $service->export();

        $batch = ExportBatch::find()->orderBy(['id' => SORT_DESC])->one();
        verify($batch)->notNull();
        verify($batch->ads_count)->greaterThan(0);
        verify($batch->keywords_count)->greaterThan(0);
        verify($batch->file_path)->notEquals('');
    }

    public function testExportMarksAdsAsExported(): void
    {
        $draftAds = Ad::find()->where(['status' => Ad::STATUS_DRAFT])->count();
        verify($draftAds)->greaterThan(0);

        $service = new ExportService();
        $service->export();

        $remainingDrafts = Ad::find()->where(['status' => Ad::STATUS_DRAFT])->count();
        verify($remainingDrafts)->equals(0);
    }

    public function testExportReturnsEmptyOnNoDrafts(): void
    {
        Ad::updateAll(['status' => Ad::STATUS_EXPORTED]);

        $service = new ExportService();
        [$filePath, $adsCount, $keywordsCount] = $service->export();

        verify($adsCount)->equals(0);
        verify($keywordsCount)->equals(0);
        verify($filePath)->equals('');
    }
}
