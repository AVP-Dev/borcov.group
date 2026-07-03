<?php

declare(strict_types=1);

namespace common\components\pipeline;

final class AdData
{
    public function __construct(
        public readonly string $headline1,
        public readonly string $headline2,
        public readonly ?string $headline3,
        public readonly string $description1,
        public readonly ?string $description2,
        public readonly string $finalUrl,
        public readonly ?string $path1,
        public readonly ?string $path2,
    ) {}
}
