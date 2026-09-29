<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Internal;

use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidCompareTargetException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\Objects\SingleValueObject;
use UnitEnum;

/**
 * Deep structural equality for equals().
 *
 * @internal Not part of the public API; may change without notice.
 */
final class Comparator
{
    /**
     * Whether $a and $b are structurally equal. They must be instances of the
     * exact same class (no polymorphic comparison); SVOs compare their
     * wrapped value, other objects every property via valueEquals().
     *
     * @throws InvalidCompareTargetException If $b is not of $a's class, or a
     *     property holds a foreign object that cannot be compared.
     */
    public static function equals(ImmutableBase $a, ImmutableBase $b): bool
    {
        if ($b::class !== $a::class) {
            throw new InvalidCompareTargetException($a::class, $b::class);
        }
        if ($a instanceof SingleValueObject) {
            /** @var SingleValueObject $b */
            return $a->value === $b->value;
        }

        return self::arrayEquals(get_object_vars($a), get_object_vars($b));
    }

    /**
     * Recursively compares two arrays for deep equality: same keys (including
     * keys holding null) and pairwise-equal values per valueEquals().
     *
     * @param array $a Left-hand array to compare.
     * @param array $b Right-hand array to compare.
     * @return bool
     */
    public static function arrayEquals(array $a, array $b): bool
    {
        if (\count($a) !== \count($b)) {
            return false;
        }
        foreach ($a as $k => $v) {
            // Identical values (equal scalars, the same instance) need no deep comparison
            if (!\array_key_exists($k, $b) || ($v !== $b[$k] && !self::valueEquals($v, $b[$k]))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compares two property or element values for deep equality:
     *   - Array → both arrays and recursively equal (a shape mismatch is inequality)
     *   - ImmutableBase object → same class and equals()
     *   - Enum → identity (cases are singletons)
     *   - Scalar / null → strict identity (===)
     *
     * Non-ImmutableBase objects (only reachable through `mixed` or plain
     * `array` properties) throw InvalidCompareTargetException, since
     * ImmutableBase cannot guarantee semantic equality for foreign objects.
     *
     * @throws InvalidCompareTargetException
     */
    private static function valueEquals(mixed $a, mixed $b): bool
    {
        return match (true) {
            \is_array($a)               => \is_array($b) && self::arrayEquals($a, $b),
            $a instanceof ImmutableBase => $b instanceof ImmutableBase && $a::class === $b::class && $a->equals($b),
            $a instanceof UnitEnum      => $a === $b,
            \is_object($a)              => throw new InvalidCompareTargetException(get_debug_type($a)),
            default                     => $a === $b,
        };
    }
}
