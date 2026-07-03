<?php

declare(strict_types=1);

namespace common\components\pipeline;

use yii\base\Component;

class NormalizationService extends Component
{
    public function normalize(string $text): string
    {
        $text = trim($text);
        $text = mb_strtolower($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = $this->unifySpecialChars($text);
        return $text;
    }

    public function normalizeBatch(array $keywords): array
    {
        $result = [];
        foreach ($keywords as $keyword) {
            $result[] = $this->normalize($keyword);
        }
        return $result;
    }

    private function unifySpecialChars(string $text): string
    {
        $replacements = [
            "\u{2018}" => "'", "\u{2019}" => "'",
            "\u{201C}" => '"', "\u{201D}" => '"',
            "\u{2013}" => '-', "\u{2014}" => '-',
            "\u{00AB}" => '"', "\u{00BB}" => '"',
            "\u{2039}" => "'", "\u{203A}" => "'",
        ];
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
}
