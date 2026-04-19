<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\OutputKeyTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/**
 * Parent DTO with OutputKeyTo(Snake). Its nested child (NestedOutputChildDTO)
 * carries OutputKeyTo(Macro), so toArray(true) applies each layer's own OutputKeyTo,
 * while toArray(KeyCase::*) forces the same case across all layers.
 */
#[OutputKeyTo(KeyCase::Snake)]
readonly class NestedOutputParentDTO extends DataTransferObject
{
    public string $parentName;
    public NestedOutputChildDTO $childItem;
}
