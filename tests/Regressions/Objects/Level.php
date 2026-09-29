<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects;

enum Level: string
{
    case Low  = 'low';
    case High = 'high';

    public const DEFAULT = self::Low;
    public const LIMIT   = 10;
}
