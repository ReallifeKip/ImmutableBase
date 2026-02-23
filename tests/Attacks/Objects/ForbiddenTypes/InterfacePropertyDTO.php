<?php
namespace Tests\Attacks\Objects\ForbiddenTypes;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use Stringable;

readonly class InterfacePropertyDTO extends DataTransferObject
{
    public Stringable $label;
}
