<?php

declare(strict_types=1);

namespace common\components\pipeline;

use common\models\AdGroup;
use common\models\Keyword;

interface AdGeneratorInterface
{
    public const int MAX_HEADLINE_LENGTH = 30;
    public const int MAX_DESCRIPTION_LENGTH = 90;
    public const int MAX_PATH_LENGTH = 15;

    /** @return AdData[] */
    public function generate(AdGroup $group, Keyword $keyword): array;
}
