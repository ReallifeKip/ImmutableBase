<?php

declare (strict_types = 1);

namespace Tests\Proxy;

/** Not serializable: holds a Closure. */
class ClosureHolder
{
    public function __construct(public \Closure $fn)
    {}
}
