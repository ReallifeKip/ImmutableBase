<?php
namespace Tests\Attacks\Objects\DeepNesting;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use Tests\Attacks\Objects\DeepNesting\PersonDTO;

readonly class CompanyDTO extends DataTransferObject
{
    public string $name;
    #[ArrayOf(PersonDTO::class)]
    public array $employees;
}
