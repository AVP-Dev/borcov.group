<?php

declare(strict_types=1);

namespace common\tests\Unit\pipeline;

use Codeception\Test\Unit;
use common\components\pipeline\GroupingService;
use common\components\pipeline\TemplateAdGenerator;
use common\models\AdGroup;
use common\models\AdGroupKeyword;
use common\models\ImportBatch;
use common\models\Keyword;
use common\models\Source;
use common\tests\Support\UnitTester;
use Yii;

final class GroupingServiceTest extends Unit
{
    protected UnitTester $tester;
    private int $ahrefsPaidId;

    protected function _setUp(): void
    {
        parent::_setUp();
        Yii::$app->params['siteUrl'] = 'https://site.pro';
        $this->seedSources();
        $this->seedBatch();
        $this->seedKeywords();
    }

    protected function _tearDown(): void
    {
        unset(Yii::$app->params['siteUrl']);
        parent::_tearDown();
    }

    private function seedSources(): void
    {
        $types = ['ahrefs_paid', 'gads', 'search_console'];
        foreach ($types as $type) {
            $existing = Source::findOne(['type' => $type]);
            if ($existing !== null) {
                continue;
            }
            $s = new Source();
            $s->name = ucfirst(str_replace('_', ' ', $type));
            $s->type = $type;
            $s->created_at = time();
            $s->save();
        }
        $this->ahrefsPaidId = Source::findOne(['type' => 'ahrefs_paid'])->id;
    }

    private function seedBatch(): void
    {
        if (ImportBatch::findOne(99) !== null) {
            return;
        }
        $b = new ImportBatch();
        $b->id = 99;
        $b->source_id = $this->ahrefsPaidId;
        $b->filename = 'grouping_test.csv';
        $b->file_hash = 'grouping_test';
        $b->imported_at = time();
        $b->status = ImportBatch::STATUS_DONE;
        $b->save();
    }

    private function seedKeywords(): void
    {
        if (Keyword::find()->count() > 0) {
            return;
        }
        $base = [
            ['website builder b2c en', Keyword::CATEGORY_WEBSITE_BUILDER, Keyword::AUDIENCE_B2C, 'en', 500],
            ['site builder b2c en', Keyword::CATEGORY_WEBSITE_BUILDER, Keyword::AUDIENCE_B2C, 'en', 400],
            ['email hosting b2c en', Keyword::CATEGORY_EMAIL, Keyword::AUDIENCE_B2C, 'en', 300],
            ['domain name b2c en', Keyword::CATEGORY_DOMAINS, Keyword::AUDIENCE_B2C, 'en', 200],
            ['reseller hosting b2b en', Keyword::CATEGORY_RESELLER, Keyword::AUDIENCE_B2B, 'en', 150],
            ['konstruktor saytov b2c ru', Keyword::CATEGORY_WEBSITE_BUILDER, Keyword::AUDIENCE_B2C, 'ru', 600],
            ['neklassif b2c en', Keyword::CATEGORY_UNCLASSIFIED, Keyword::AUDIENCE_B2C, 'en', 50],
        ];
        foreach ($base as [$text, $cat, $seg, $lang, $vol]) {
            $kw = new Keyword();
            $kw->batch_id = 99;
            $kw->source_id = $this->ahrefsPaidId;
            $kw->raw_text = $text;
            $kw->normalized_text = $text;
            $kw->volume = $vol;
            $kw->category = $cat;
            $kw->audience_segment = $seg;
            $kw->language = $lang;
            $kw->status = Keyword::STATUS_READY;
            $kw->save();
        }
    }

    public function testGroupsReadyKeywords(): void
    {
        $service = new GroupingService();
        $created = $service->groupAll();

        verify($created)->equals(6);

        $websiteB2CEn = AdGroup::find()
            ->where(['category' => Keyword::CATEGORY_WEBSITE_BUILDER, 'audience_segment' => Keyword::AUDIENCE_B2C, 'language' => 'en'])
            ->one();
        verify($websiteB2CEn)->notNull();
        verify($websiteB2CEn->getKeywords()->count())->equals(2);

        $resellerB2BEn = AdGroup::find()
            ->where(['category' => Keyword::CATEGORY_RESELLER, 'audience_segment' => Keyword::AUDIENCE_B2B, 'language' => 'en'])
            ->one();
        verify($resellerB2BEn)->notNull();
        verify($resellerB2BEn->getKeywords()->count())->equals(1);
    }

    public function testIsIdempotent(): void
    {
        $service = new GroupingService();
        $first = $service->groupAll();
        $second = $service->groupAll();

        verify($first)->equals(6);
        verify($second)->equals(0);

        verify(AdGroup::find()->count())->equals(6);
    }

    public function testNoReadyKeywords(): void
    {
        Keyword::updateAll(['status' => Keyword::STATUS_CLEANED], ['status' => Keyword::STATUS_READY]);
        $service = new GroupingService();
        verify($service->groupAll())->equals(0);
    }

    public function testGroupingWithGenerator(): void
    {
        $generator = new TemplateAdGenerator();
        $service = new GroupingService($generator);
        $service->groupAll();

        $webGroup = AdGroup::find()
            ->where(['category' => Keyword::CATEGORY_WEBSITE_BUILDER, 'language' => 'en'])
            ->one();
        verify($webGroup)->notNull();
        verify((int)$webGroup->getAds()->count() > 0);
    }

    public function testTargetUrlSetOnAllGroups(): void
    {
        $service = new GroupingService();
        $service->groupAll();

        $groups = AdGroup::find()->all();
        verify(count($groups))->equals(6);

        foreach ($groups as $group) {
            verify($group->target_url)->notNull();
            verify(str_starts_with($group->target_url, 'https://site.pro'));
        }

        $webGroup = AdGroup::find()
            ->where(['category' => Keyword::CATEGORY_WEBSITE_BUILDER, 'language' => 'en'])
            ->one();
        verify(str_ends_with($webGroup->target_url, '/website-builder'));

        $resellerGroup = AdGroup::find()
            ->where(['category' => Keyword::CATEGORY_RESELLER, 'language' => 'en'])
            ->one();
        verify(str_ends_with($resellerGroup->target_url, '/reseller'));
    }
}
