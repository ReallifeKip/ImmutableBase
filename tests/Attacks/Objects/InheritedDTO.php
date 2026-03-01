<?php
namespace Tests\Attacks\Objects;

use Tests\Attacks\Objects\DeepNesting\AddressDTO;

readonly class InheritedDTO extends AddressDTO
{
    public string $country;
}
