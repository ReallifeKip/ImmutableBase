<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Internal;

/**
 * Sentinel a type matcher returns when a value does not have the shape of its
 * target type at all. Distinct from every legal value, including null, so
 * callers can tell "not this type" apart from a resolved value without
 * paying for an exception on the common union / #[ArrayOf] fallthrough path.
 *
 * @internal Not part of the public API; may change without notice.
 */
enum NoMatch
{
    case Value;
}
