<?php
namespace Tests\Attacks\Objects\DeepNesting;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use Tests\Attacks\Objects\DeepNesting\AddressDTO;
use Tests\Attacks\Objects\DeepNesting\EmailSVO;

readonly class PersonDTO extends DataTransferObject
{
    public string $name;
    public EmailSVO $email;
    public AddressDTO $address;
}
