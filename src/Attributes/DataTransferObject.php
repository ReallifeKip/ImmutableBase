<?php

declare(strict_types=1);

namespace ReallifeKip\ImmutableBase\Attributes;

/**
 * Data Transfer Object
 *
 * All properties must be public and readonly
 * @deprecated will be depecreate in 4.0.0
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
abstract class DataTransferObject
{
}
