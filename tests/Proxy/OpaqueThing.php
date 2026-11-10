<?php

declare (strict_types = 1);

namespace Tests\Proxy;

/** A mutable object with no JsonSerializable: holdable, but has no output form. */
class OpaqueThing
{
    public function __construct(public int $n)
    {}
}
