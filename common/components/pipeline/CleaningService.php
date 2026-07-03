<?php

declare(strict_types=1);

namespace common\components\pipeline;

use Yii;
use yii\base\Component;
use common\models\ForbiddenTerm;
use common\models\BrandTerm;
use common\models\Keyword;
use common\models\Source;

class CleaningService extends Component
{
    public const EVENT_AFTER_CLEANING = 'afterCleaning';

    public array $stopWords = [
        'free', 'cheap', 'best', 'top', 'buy', 'price', 'cost',
        'бесплатно', 'дешево', 'лучший', 'купить', 'цена',
    ];

    public array $ahrefsArtifacts = [
        '/', '?', 'keyword', 'search term',
    ];

    public float $brandFuzzyThreshold = 0.6;

    public function clean(Keyword $keyword): array
    {
        $result = [
            'passed' => true,
            'rejection_reason' => null,
            'is_brand' => false,
            'is_forbidden' => false,
        ];

        $junk = $this->checkJunk($keyword);
        if ($junk !== null) {
            return ['passed' => false, 'rejection_reason' => $junk, 'is_brand' => false, 'is_forbidden' => false];
        }

        $brand = $this->checkBrand($keyword);
        if ($brand !== null) {
            $result['is_brand'] = true;
            $result['passed'] = $brand['keep'];
            $result['rejection_reason'] = $brand['reason'];
        }

        $forbidden = $this->checkForbidden($keyword);
        if ($forbidden !== null) {
            $result['is_forbidden'] = true;
            $result['passed'] = false;
            $result['rejection_reason'] = $forbidden;
        }

        $alreadyUsed = $this->checkAlreadyUsed($keyword);
        if ($alreadyUsed !== null) {
            $result['passed'] = false;
            $result['rejection_reason'] = $alreadyUsed;
        }

        return $result;
    }

    private function checkJunk(Keyword $keyword): ?string
    {
        $text = $keyword->raw_text;

        if (mb_strlen($text) < 2) {
            return Yii::t('app', 'clean.reason.too_short');
        }

        if (preg_match('/^\d+$/', $text)) {
            return Yii::t('app', 'clean.reason.only_digits');
        }

        foreach ($this->stopWords as $stopWord) {
            if (mb_strtolower($text) === mb_strtolower($stopWord)) {
                return Yii::t('app', 'clean.reason.stop_word');
            }
        }

        foreach ($this->ahrefsArtifacts as $artifact) {
            if (mb_strtolower($text) === mb_strtolower($artifact)) {
                return Yii::t('app', 'clean.reason.artifact');
            }
        }

        return null;
    }

    private function checkBrand(Keyword $keyword): ?array
    {
        $text = mb_strtolower($keyword->raw_text);
        $terms = BrandTerm::find()->all();

        foreach ($terms as $term) {
            $termText = mb_strtolower($term->term);
            if (str_contains($text, $termText)) {
                if ($term->is_own_brand) {
                    return ['keep' => true, 'reason' => null];
                }
                return ['keep' => false, 'reason' => Yii::t('app', 'clean.reason.competitor_brand')];
            }
        }

        if (mb_strlen($text) >= 3) {
            return $this->checkBrandFuzzy($text);
        }

        return null;
    }

    private function checkBrandFuzzy(string $text): ?array
    {
        $result = Yii::$app->db->createCommand("
            SELECT term, is_own_brand
            FROM {{%brand_terms}}
            WHERE EXISTS (
                SELECT 1
                FROM regexp_split_to_table(LOWER(:text), E'\\\\s+') AS word
                WHERE word != ''
                AND similarity(word, LOWER(term)) >= :threshold
            )
            ORDER BY is_own_brand DESC
            LIMIT 1
        ", [
            ':text' => $text,
            ':threshold' => $this->brandFuzzyThreshold,
        ])->queryOne();

        if ($result === false) {
            return null;
        }

        return (bool)$result['is_own_brand']
            ? ['keep' => true, 'reason' => null]
            : ['keep' => false, 'reason' => Yii::t('app', 'clean.reason.competitor_brand')];
    }

    private function checkForbidden(Keyword $keyword): ?string
    {
        $text = $keyword->raw_text;
        $terms = ForbiddenTerm::find()->all();

        foreach ($terms as $term) {
            $termText = $term->term;
            $match = match ($term->match_type) {
                ForbiddenTerm::MATCH_EXACT => mb_strtolower($text) === mb_strtolower($termText),
                ForbiddenTerm::MATCH_CONTAINS => mb_stripos($text, $termText) !== false,
                ForbiddenTerm::MATCH_REGEX => (bool) preg_match('/' . $termText . '/iu', $text),
                default => false,
            };
            if ($match) {
                return $term->reason ?: Yii::t('app', 'clean.reason.forbidden');
            }
        }

        return null;
    }

    private function checkAlreadyUsed(Keyword $keyword): ?string
    {
        $existing = Keyword::find()
            ->alias('k')
            ->innerJoin(['s' => '{{%sources}}'], 'k.source_id = s.id')
            ->where(['k.normalized_text' => $keyword->normalized_text])
            ->andWhere(['in', 'k.status', [Keyword::STATUS_CLEANED, Keyword::STATUS_READY]])
            ->andWhere(['s.type' => Source::TYPE_GADS])
            ->exists();

        return $existing ? Yii::t('app', 'clean.reason.already_used') : null;
    }
}
