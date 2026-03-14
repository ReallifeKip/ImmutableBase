<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Objects;

use ReallifeKip\ImmutableBase\ImmutableBase;

/**
 * Base class for data transfer objects. DTOs carry structured data without
 * domain validation — no validate() method is invoked during construction.
 *
 * All properties must be public and readonly (enforced by the `readonly class`
 * declaration and scan-time visibility checks).
 *
 * @example
 * readonly class OrderDTO extends DataTransferObject {
 *     public string $orderId;
 *     public ?string $note;
 * }
 */
abstract readonly class DataTransferObject extends ImmutableBase
{
    final protected function __construct(array $data = [])
    {
        return parent::__construct($data);
    }
}
