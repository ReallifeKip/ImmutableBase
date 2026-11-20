<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects\Writer;

use ReallifeKip\ImmutableBase\Attributes\Defaults;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/** Default values the Markdown writer must render as written, and the required column they affect. */
readonly class DocDefaults extends DataTransferObject
{
    #[Defaults(true)]
    public bool $enabled;
    #[Defaults(false)]
    public bool $disabled;
    public string $plain;
    public mixed $stamp;
    public mixed $rule;

    public static function defaultValues(): array
    {
        return [
            'stamp' => new \DateTimeImmutable('2020-01-01'),
            'rule'  => static fn() => 'computed',
        ];
    }
}
