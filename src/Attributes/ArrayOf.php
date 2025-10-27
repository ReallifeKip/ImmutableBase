<?php

namespace ReallifeKip\ImmutableBase\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class ArrayOf
{
    public bool $error = false;
    public function __construct(string $class = '')
    {
        $this->error = empty(trim($class));
    }
}
