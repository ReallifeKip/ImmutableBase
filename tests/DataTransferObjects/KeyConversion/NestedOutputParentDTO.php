<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\OutputCaseTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/**
 * Parent DTO with OutputCaseTo(Snake). Its nested child (NestedOutputChildDTO)
 * carries OutputCaseTo(Macro), so toArray(true) applies each layer's own OutputCaseTo,
 * while toArray(KeyCase::*) forces the same case across all layers.
 */
#[OutputCaseTo(KeyCase::Snake)]
readonly class NestedOutputParentDTO extends DataTransferObject
{
    public string $parentName;
    public NestedOutputChildDTO $childItem;
}
