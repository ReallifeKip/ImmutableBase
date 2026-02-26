<?php
namespace Tests\Attacks\Objects\ForbiddenTypes;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class ObjectPropertyDTO extends DataTransferObject
{
    public object $payload;
}
