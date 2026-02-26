<?php
namespace Tests\Attacks\Objects\DeepNesting;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class AddressDTO extends DataTransferObject
{
    public string $street;
    public string $city;
    public ?string $zip;
}
