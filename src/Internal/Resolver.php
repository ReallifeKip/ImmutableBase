<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Internal;

use BackedEnum;
use Closure;
use ReallifeKip\ImmutableBase\Attributes\Defaults;
use ReallifeKip\ImmutableBase\BasicTrait;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidPropertyTypeException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidEnumValueException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidValueException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\RequiredValueException;
use ReallifeKip\ImmutableBase\Exceptions\RuntimeException as InputRejectedException;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\InvalidArrayOfItemException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\Objects\SingleValueObject;
use ReallifeKip\ImmutableBase\Types;
use ReflectionEnum;
use UnitEnum;

/**
 * Turns raw input into property values. The single source of truth for
 * "does this value fit that type, and what does it become".
 *
 * Every target type — a builtin, an enum, an SVO or another ImmutableBase
 * class — compiles to exactly one matcher: a closure that returns the
 * resolved value, or NoMatch::Value when the input does not have the shape
 * of that type at all. Failures *inside* a match (a nested object's own
 * validation, an unknown enum case) still throw. The three consumers differ
 * only in what a NoMatch means:
 *
 *   - a named property  → InvalidValueException for the declared type
 *   - a union member    → try the next member
 *   - an #[ArrayOf] item → InvalidArrayOfItemException for the element
 *
 * Matchers are memoized per target, so union members and #[ArrayOf]
 * elements share the closures compiled for plain properties.
 *
 * @internal Not part of the public API; may change without notice.
 *
 * @phpstan-import-type Type from Types
 */
final class Resolver
{
    use BasicTrait;

    /** @var array<string, Closure(mixed): mixed> */
    private static array $matchers = [];

    /**
     * Attaches the runtime-only fields to one property's type metadata:
     * its compiled `resolver` and the `decodesJson` flag used by with().
     * Also coalesces fields that cache-sourced metadata omits.
     *
     * @param Type $type
     * @throws InvalidPropertyTypeException For a property declared as standalone `null`.
     * @return Type
     */
    public static function compile(array $type): array
    {
        $type['isBuiltin'] ??= false;
        $type['isSVO']     ??= false;
        // with() decodes JSON only for a plain `array` target; see value()
        $type['decodesJson'] = !$type['isUnion'] && $type['typename']['string'] === 'array' && $type['arrayOf'] === null;
        $type['resolver']    = $type['isUnion'] ? self::unionResolver($type) : self::namedResolver($type['typename']['string']);

        return $type;
    }

    /**
     * Resolves every property of a class from $data. A missing key takes
     * its default (compiled, or read from #[Defaults] when the metadata
     * came without one); a missing non-nullable value throws.
     *
     * @param array<string, mixed> $data      Input array, mutated in place when a default is injected.
     * @param array<string, Type>  $types     Compiled property type metadata.
     * @param string|null          $errorPath Updated to the property being resolved, for error context.
     * @return array<string, mixed> Resolved property values keyed by property name.
     */
    public static function properties(array &$data, array $types, ?string &$errorPath): array
    {
        foreach ($types as $type) {
            $name = $errorPath = $type['propertyName'];
            if (!\array_key_exists($name, $data)) {
                $data[$name] = $type['defaults'] ?? (isset($type['propertyRef']) ? self::getAttributeArgument($type['propertyRef'], Defaults::class) : null);
            }
            match (true) {
                !isset($data[$name]) && !$type['allowsNull'] => throw new RequiredValueException($name),
                default                                      => $resolved[$name] = self::value($type, $data[$name] ?? null)
            };
        }

        return $resolved ?? [];
    }

    /**
     * Resolves one value against one property. In priority order:
     *   1. $tryJson=true, a plain `array` target and a string that decodes to
     *      an array → the decoded array (with() only)
     *   2. Null → accepted if nullable, otherwise RequiredValueException
     *   3. #[ArrayOf] property → arrayOf()
     *   4. Everything else → the property's compiled resolver
     *
     * Every other target already handles JSON in its own matcher where it is
     * meaningful (nested objects, #[ArrayOf]) and must keep a JSON-looking
     * string as-is where a string is valid (`string`, `mixed`, SVOs), so
     * with() resolves those exactly as fromArray() does.
     *
     * @param Type $type
     */
    public static function value(array $type, mixed $value, bool $tryJson = false): mixed
    {
        return match (true) {
            $tryJson && ($type['decodesJson'] ?? false) && Json::looksLike($value) && \is_array($decoded = Json::decode($value))
                                               => $decoded,
            $value === null                    => $type['allowsNull'] ? null : throw new RequiredValueException($type['propertyName'] ?? $type['typename']['string']),
            ($arg = $type['arrayOf']) !== null => self::arrayOf($arg, $value),
            default                            => $type['resolver']($value)
        };
    }

    /**
     * Resolves an #[ArrayOf] value into a typed list: a JSON string is
     * decoded first; anything else that is not an array is rejected.
     *
     * With one declared element type, an element that does not match is
     * InvalidArrayOfItemException, while a failure inside a match (a nested
     * object's own validation) propagates as is. With several, each element
     * is tried against each type in declaration order, any rejection falling
     * through to the next; the first match wins. Attribute arguments are not
     * normalized by PHP, so that order is the declared one — unlike unions.
     *
     * `float` widens an int element (with an explicit cast: an array element
     * has no declared type to convert it) unless the same #[ArrayOf] also
     * lists `int`, so an exact match always wins. A foreign object given for
     * a DTO / VO element is read as an array of its public properties.
     *
     * @param list<non-empty-string> $args The declared element types.
     * @throws InvalidValueException
     * @throws InvalidArrayOfItemException
     * @return array<int|string, mixed>
     */
    public static function arrayOf(array $args, mixed $value): array
    {
        if (\is_string($value)) {
            $value = Json::decode($value, false);
        }
        if (!\is_array($value)) {
            throw new InvalidValueException('array', $value);
        }
        if ($value === []) {
            return $value;
        }
        $widen = !\in_array('int', $args, true);
        if (\count($args) === 1) {
            [$arg]    = $args;
            $matcher  = self::matcher($arg, $widen);
            $castable = self::castsObjects($arg);
            foreach ($value as $k => $v) {
                if ($castable && \is_object($v) && !$v instanceof $arg) {
                    $v = (array) $v;
                }
                $values[] = ($r = $matcher($v)) === NoMatch::Value ? throw new InvalidArrayOfItemException($k, $arg) : $r;
            }

            return $values;
        }
        foreach ($value as $k => $v) {
            foreach ($args as $arg) {
                $candidate = self::castsObjects($arg) && \is_object($v) && !$v instanceof $arg ? (array) $v : $v;
                try {
                    if (($r = self::matcher($arg, $widen)($candidate)) !== NoMatch::Value) {
                        $values[] = $r;
                        continue 2;
                    }
                } catch (InputRejectedException) {
                    continue;
                }
            }
            throw new InvalidArrayOfItemException($k, implode('|', $args));
        }

        return $values;
    }

    /**
     * The resolver for a named (non-union) property: its type's matcher in
     * throwing mode, so a mismatch is InvalidValueException for the declared
     * type and a hit costs a single closure call. `float` widens an int, as
     * a native float property does under strict_types.
     *
     * @throws InvalidPropertyTypeException For standalone `null`, a property that could only ever be null.
     */
    private static function namedResolver(string $typename): Closure
    {
        if ($typename === 'null') {
            throw new InvalidPropertyTypeException($typename);
        }

        return self::matcher($typename, true, true);
    }

    /**
     * The resolver for a union property: each member's matcher in the order
     * PHP reports them, first match wins.
     *
     * That order is *not* the declared one. PHP normalizes union members
     * before Reflection sees them: classes and enums first, keeping their
     * relative order, then builtins in the fixed sequence string, int,
     * float, bool. So `string|Foo` is attempted as `Foo, string`.
     *
     * Members match exactly. Only when every member failed does an int widen
     * to a `float` member — mirroring PHP, where an exact union match always
     * takes precedence over int-to-float widening (`int|float` keeps an int,
     * `float|string` widens it). PHP always places `int` before `float`, so
     * an exact int match has returned before this fallback runs.
     *
     * Any input rejection inside a member (a nested object's validation or
     * #[Strict] violation) falls through to the next member; definition
     * errors propagate.
     *
     * @param Type $type
     */
    private static function unionResolver(array $type): Closure
    {
        $matchers = array_map(static fn(array $member) => self::matcher($member['typename']['string'], false), $type['types']);
        $widens   = \in_array('float', $type['typename']['array'], true);
        $typename = $type['typename']['string'];

        return static function (mixed $value) use ($matchers, $widens, $typename): mixed {
            foreach ($matchers as $matcher) {
                try {
                    if (($r = $matcher($value)) !== NoMatch::Value) {
                        return $r;
                    }
                } catch (InputRejectedException) {
                    continue;
                }
            }

            return $widens && \is_int($value) ? (float) $value : throw new InvalidValueException($typename, $value);
        };
    }

    /**
     * The memoized matcher for a target type. See compileMatcher().
     *
     * @param string $target A builtin type name, or an enum / ImmutableBase FQCN.
     * @param bool $widen Whether a `float` target accepts an int.
     * @param bool $throws Whether a mismatch throws InvalidValueException instead of returning NoMatch::Value.
     * @return Closure(mixed): mixed The resolved value, or NoMatch::Value.
     */
    private static function matcher(string $target, bool $widen, bool $throws = false): Closure
    {
        return self::$matchers[$target . ($widen ? '' : '!') . ($throws ? '^' : '')] ??= self::compileMatcher($target, $widen, $throws);
    }

    /**
     * Builds the matcher for one target type:
     *   - builtins match their exact type (`mixed` anything, `null` nothing,
     *     `false` / `true` only that value); `float` also takes an int when
     *     $widen, cast to float
     *   - an enum takes a case, or a case name / backed value (see enumCase())
     *   - an SVO takes an instance, an array (fromArray), or any non-object
     *     scalar for from() to validate
     *   - another ImmutableBase class takes an instance, an array
     *     (fromArray) or a JSON object string (fromJson)
     *
     * A mismatch calls $miss: NoMatch::Value for union members and #[ArrayOf]
     * elements, InvalidValueException for a named property ($throws), so the
     * hit path never pays for a wrapper.
     */
    private static function compileMatcher(string $target, bool $widen, bool $throws): Closure
    {
        $miss = $throws
        ? static fn(mixed $v): never => throw new InvalidValueException($target, $v)
        : static fn(mixed $v): NoMatch => NoMatch::Value;

        return match (true) {
            $target === 'string' => static fn(mixed $v): mixed => \is_string($v) ? $v : $miss($v),
            $target === 'int'    => static fn(mixed $v): mixed => \is_int($v) ? $v : $miss($v),
            $target === 'bool'   => static fn(mixed $v): mixed => \is_bool($v) ? $v : $miss($v),
            $target === 'array'  => static fn(mixed $v): mixed => \is_array($v) ? $v : $miss($v),
            $target === 'false'  => static fn(mixed $v): mixed => $v === false ? $v : $miss($v),
            $target === 'true'   => static fn(mixed $v): mixed => $v === true ? $v : $miss($v),
            $target === 'mixed'  => static fn(mixed $v): mixed => $v,
            $target === 'float'  => $widen
            ? static fn(mixed $v): mixed => \is_float($v) ? $v : (\is_int($v) ? (float) $v : $miss($v))
            : static fn(mixed $v): mixed => \is_float($v) ? $v : $miss($v),
            enum_exists($target) => static fn(mixed $v): mixed => match (true) {
                $v instanceof $target         => $v,
                \is_string($v) || \is_int($v) => self::enumCase($target, $v),
                default                       => $miss($v),
            },
            is_a($target, SingleValueObject::class, true) => static fn(mixed $v): mixed => match (true) {
                $v instanceof $target => $v,
                \is_array($v)         => $target::fromArray($v),
                \is_object($v)        => $miss($v),
                default               => $target::from($v),
            },
            is_a($target, ImmutableBase::class, true) => static fn(mixed $v): mixed => match (true) {
                $v instanceof $target => $v,
                \is_array($v)         => $target::fromArray($v),
                Json::looksLike($v)   => $target::fromJson($v),
                default               => $miss($v),
            },
            default => $miss, // `null`, the only builtin left
        };
    }

    /**
     * Whether an #[ArrayOf] element of type $target reads a foreign object
     * as an array of its public properties: true for DTO / VO targets.
     */
    private static function castsObjects(string $target): bool
    {
        static $casts = [];

        return $casts[$target] ??= is_a($target, ImmutableBase::class, true) && !is_a($target, SingleValueObject::class, true);
    }

    /**
     * Resolves a string or integer value to an enum case. Tries two strategies:
     *   1. Constant lookup by name (works for both UnitEnum and BackedEnum).
     *      Only constants holding a case of this enum count — a case alias
     *      (`const DEFAULT = self::Low`) resolves, a plain `const LIMIT = 10`
     *      does not.
     *   2. BackedEnum::tryFrom() by backed value, when the value's type
     *      matches the backing type (calling it otherwise is a TypeError
     *      under strict_types).
     *
     * @param class-string $class
     * @throws InvalidEnumValueException
     */
    private static function enumCase(string $class, string | int $value): UnitEnum
    {
        static $backing = [];
        $backing[$class] ??= is_a($class, BackedEnum::class, true) ? (string) (new ReflectionEnum($class))->getBackingType() : '';

        return match (true) {
            \defined($case = "$class::$value") && ($case = \constant($case)) instanceof $class          => $case,
            $backing[$class] === get_debug_type($value) && ($case = $class::tryFrom($value)) !== null => $case,
            default                                                                                  => throw new InvalidEnumValueException($class, $value),
        };
    }
}
