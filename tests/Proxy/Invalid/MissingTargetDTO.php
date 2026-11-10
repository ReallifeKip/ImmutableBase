<?php

declare (strict_types = 1);

namespace Tests\Proxy\Invalid;

use ReallifeKip\ImmutableBase\ImmutableProxy;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/** Base ImmutableProxy without #[Proxy]: the target class is unknown. */
readonly class MissingTargetDTO extends DataTransferObject
{
    public ImmutableProxy $thing;
}
