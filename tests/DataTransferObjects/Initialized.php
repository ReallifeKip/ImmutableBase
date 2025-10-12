<?php

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\DataTransferObject;

#[DataTransferObject]
class Initialized extends ImmutableBase
{
    public readonly string $foo;
    public function __construct(array $data)
    {
        $this->foo = 'foo';
        parent::__construct($data);
    }
}
