<?php

declare(strict_types=1);

namespace common\components\pipeline;

interface SourceAdapterInterface
{
    public function parse(string $filePath): iterable;
}
