<?php

declare (strict_types = 1);

namespace Tests\Proxy;

/** Stand-in for a mutable third-party object nested inside another. */
class Endpoint
{
    public function __construct(public string $url)
    {}
}
