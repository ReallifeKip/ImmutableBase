<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\Defaults;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class DefaultPriorityDTO extends DataTransferObject
{
    #[Defaults('attribute-default')]
    public ?string $fromAttribute;

    public ?string $fromFunction;

    #[Defaults('attribute-should-be-overridden')]
    public ?string $both;

    public string $required;

    public static function defaultValues(): array
    {
        return [
            'fromFunction' => 'function-default',
            'both'         => 'function-overrides-attribute',
        ];
    }
}
