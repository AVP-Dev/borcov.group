<?php

declare(strict_types=1);

namespace common\tests\Unit\models;

use Codeception\Test\Unit;
use common\components\pipeline\AdGeneratorInterface;
use common\models\Ad;
use common\tests\Support\UnitTester;

final class AdTest extends Unit
{
    protected UnitTester $tester;

    public function testTruncateWordSafeReturnsShortTextUnchanged(): void
    {
        $result = Ad::truncateWordSafe('short text', 90);
        verify($result)->equals('short text');
    }

    public function testTruncateWordSafeCutsAtExactWordBoundary(): void
    {
        $long = 'Launch your professional website quickly with Site.pro. No coding skills required. Start building today.';
        verify(mb_strlen($long))->greaterThan(90);

        $result = Ad::truncateWordSafe($long, 90);
        verify(mb_strlen($result))->lessThanOrEqual(90);
        verify($result)->stringContainsString('Launch your professional');
        verify($result)->stringEndsWith('Start');
    }

    public function testTruncateWordSafeDoesNotCutMidWord(): void
    {
        $long = str_repeat('word ', 30); // 150 chars
        $result = Ad::truncateWordSafe($long, 90);
        verify(mb_strlen($result))->lessThanOrEqual(90);
        verify($result)->stringEndsWith('word');
        verify(mb_substr($result, -1))->notEquals(' ');
    }

    public function testTruncateWordSafeReturnsEmptyStringForEmptyInput(): void
    {
        verify(Ad::truncateWordSafe('', 90))->equals('');
    }

    public function testTruncateWordSafeUsesMaxLengthHeadline(): void
    {
        $long = 'a ' . str_repeat('longerword', 10);
        $result = Ad::truncateWordSafe($long, AdGeneratorInterface::MAX_HEADLINE_LENGTH);
        verify(mb_strlen($result))->lessThanOrEqual(AdGeneratorInterface::MAX_HEADLINE_LENGTH);
    }
}
