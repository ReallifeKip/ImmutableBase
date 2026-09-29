<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Internal;

use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidWithPathException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\Types;

/**
 * Deep-path updates for with(): dot / bracket notation such as
 * "customer.address.city" or "items[0].sku".
 *
 * @internal Not part of the public API; may change without notice.
 *
 * @phpstan-import-type Type from Types
 */
final class PathUpdater
{
    /**
     * Parses a dot/bracket-notation path into root key and remainder.
     * Validates that the root key points to a traversable target (array or
     * ImmutableBase instance). Throws InvalidWithPathException if the root resolves
     * to a scalar — indicating a structural path error by the caller.
     *
     * Note: Uses str_ireplace because str_replace cannot achieve 100%
     * branch coverage under Xdebug's opcode-level tracking.
     *
     * @param string $path The raw dot/bracket-notation path (e.g. "items[0].sku").
     * @param string $separator The path delimiter character (e.g. ".", "/").
     * @param array $values Current property values of the object, used to validate the root key.
     * @return array{string, string} Tuple of [root property name, remaining sub-path].
     */
    public static function split(string $path, string $separator, array $values): array
    {
        [$root, $rest] = explode(
            $separator,
            /** Note: Using str_ireplace because str_replace cannot reach 100% branch coverage. */
            str_ireplace(['[', ']'], [$separator, ''], $path),
            2
        );
        if (!(\is_array($values[$root] ?? null) || ($values[$root] ?? null) instanceof ImmutableBase)) {
            throw new InvalidWithPathException($root);
        }

        return [$root, $rest];
    }

    /**
     * Resolves accumulated deep-path updates (dot/bracket notation) into $values.
     * For each root key, delegates to with() for ImmutableBase instances or
     * applyArrayDeepUpdate() for plain arrays, then re-resolves ArrayOf properties.
     *
     * @param array<string, mixed>                       $values      Current property values, mutated in place.
     * @param array<string, array<string, mixed>>        $deepUpdates Root → sub-path map collected during the flat loop.
     * @param array<string, Type>                        $types       Compiled type metadata for the class.
     * @param string                                     $separator   The path delimiter used to split keys.
     * @param string|null                                $errorPath   Reference updated to the current root for error context.
     */
    public static function apply(array &$values, array $deepUpdates, array $types, string $separator, ?string &$errorPath): void
    {
        foreach ($deepUpdates as $root => $sub) {
            $errorPath     = $root;
            $current       = $values[$root];
            $values[$root] = match (true) {
                $current instanceof ImmutableBase => $current->with($sub, $separator),
                default                           => self::applyArrayDeepUpdate($current, $sub, $separator),
            };
            if ($types[$root]['arrayOf'] !== null) {
                $values[$root] = Resolver::value($types[$root], $values[$root]);
            }
        }
    }

    /**
     * Applies deep updates to a plain array (non-ImmutableBase) value. Groups sub-paths
     * by their next segment: paths containing the separator are accumulated
     * for recursive with() on nested ImmutableBase instances; flat keys are assigned directly.
     *
     * @param array $current The existing array value to update.
     * @param array<string|int, mixed> $subPaths Remaining path segments mapped to their target values.
     * @param string $separator The path delimiter for further nested resolution.
     * @return array The updated array with deep modifications applied.
     */
    private static function applyArrayDeepUpdate(array $current, array $subPaths, string $separator): array
    {
        foreach ($subPaths as $path => $value) {
            if (\is_string($path)) {
                $target = explode($separator, $path, 2);
                if (\count($target) === 2) {
                    $grouped[$target[0]][$target[1]] = $value;
                } else {
                    $grouped[$target[0]] = $value;
                }
            } else {
                $current[$path] = $value;
            }
        }
        foreach ($grouped ?? [] as $index => $deeperValues) {
            if (isset($current[$index])) {
                $current[$index] = match (true) {
                    $current[$index] instanceof ImmutableBase => $current[$index]->with($deeperValues, $separator),
                    \is_array($current[$index])               => self::applyArrayDeepUpdate($current[$index], $deeperValues, $separator),
                    default                                   => $current[$index], // scalar, can't traverse
                };
            }
        }

        return $current;
    }
}
