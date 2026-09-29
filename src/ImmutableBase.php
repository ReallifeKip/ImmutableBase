<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase;

use BackedEnum;
use JsonSerializable;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\DebugLogDirectoryInvalidException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidArrayOfTargetException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidArrayOfUsageException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidCompareTargetException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidSerializeTargetException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidSpecException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidVisibilityException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidWithPathException;
use ReallifeKip\ImmutableBase\Exceptions\ImmutableBaseException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidJsonException;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\StrictViolationException;
use ReallifeKip\ImmutableBase\Internal\Json;
use ReallifeKip\ImmutableBase\Internal\KeyCaser;
use ReallifeKip\ImmutableBase\Internal\Metadata;
use ReallifeKip\ImmutableBase\Internal\Resolver;
use ReallifeKip\ImmutableBase\Internal\Scanner;
use ReallifeKip\ImmutableBase\Objects\SingleValueObject;
use ReallifeKip\ImmutableBase\Objects\ValueObject;
use ReallifeKip\ImmutableBase\Types;
use UnitEnum;

/**
 * Core engine for immutable data objects with strict type validation.
 *
 * Provides reflection-based property scanning, type-specific resolver
 * compilation, JSON/array serialization, deep immutable mutation via
 * with(), and structural equality comparison. Serves as the shared
 * foundation for DataTransferObject, ValueObject, and SingleValueObject.
 *
 * Not intended for direct extension — extend DataTransferObject,
 * ValueObject, or SingleValueObject instead.
 *
 * @phpstan-import-type Hydrator from Types
 * @phpstan-import-type NamedTypeFromUnion from Types
 * @phpstan-import-type NamedType from Types
 * @phpstan-import-type UnionType from Types
 * @phpstan-import-type Type from Types
 * @phpstan-import-type Property from Types
 * @phpstan-import-type Caches from Types
 * @phpstan-import-type State from Types
 */
abstract readonly class ImmutableBase
{
    use BasicTrait;
    /**
     * Wraps construction and mutation operations in an error boundary that
     * contributes one frame to the exception's error path via prependPath().
     * Nested constructions each add their frame on the way out, so the
     * outermost catch leaves the full chain (e.g. "OrderDTO > $customer > $email").
     *
     * No state is kept outside the exception itself, so an exception that
     * never reaches the outermost frame cannot affect later messages.
     *
     * @param callable(string, ?string): mixed $callback Receives the FQCN of the calling class and a
     *                                                   by-reference error path variable for prependPath()
     */
    final protected static function executeSafely(callable $callback): mixed
    {
        $static    = static::class;
        $errorPath = null;
        try {
            return $callback($static, $errorPath);
        } catch (ImmutableBaseException $e) {
            throw $e->prependPath($static, $errorPath);
        }
    }
    /**
     * Returns a by-reference handle to the engine's internal state array.
     * Holds all runtime caches and configuration flags.
     *
     * The engine itself no longer uses this handle: it goes through the
     * narrow accessors of Internal\Metadata. The handle is kept only so
     * existing callers (test suites resetting caches) keep working.
     *
     * @internal
     * @deprecated Kept for compatibility; slated for removal in the next major version.
     * DANGER: DO NOT USE THIS METHOD!
     * INTERNAL USE ONLY. MANIPULATING THIS STATE MANUALLY WILL CAUSE FATAL
     * UNINTENDED CONSEQUENCES, DATA CORRUPTION, OR UNSTABLE ENGINE BEHAVIOR!
     *
     * @return State
     */
    public static function &state(): array
    {
        return Metadata::state();
    }

    /**
     * Hydrates the object from an associative array.
     * On first instantiation of a given class, triggers property scanning and
     * resolver compilation via buildPropertyInheritanceChain(). Subsequent
     * instantiations reuse the cached metadata from state()['properties'].
     *
     * Input keys are remapped before resolution when class-level or property-level
     * #[InputKeyTo] attributes are present (see Internal\KeyCaser::remapInput()).
     *
     * Enforces strict mode rejection of redundant keys when enabled globally
     * or via class-level #[Strict] attribute (unless overridden by #[Lax]).
     *
     * @param array<string, mixed> $data Associative input keyed by property name (or any mapped case). Missing nullable keys default to null.
     */
    protected function __construct(array $data = [])
    {
        self::executeSafely(function ($static, &$errorPath) use ($data) {
            if (!Metadata::has($static)) {
                $this::buildPropertyInheritanceChain($this);
            }
            if (($logPath = Metadata::debugPath()) !== null) {
                self::logging($data, $static, $logPath);
            }
            $class = Metadata::get($static);
            if ($class['inputKeyCase'] !== null || $class['propertyInputKeyCases'] !== null) {
                $data = KeyCaser::remapInput($data, $class);
            }
            if (
                !$class['isLax'] &&
                (Metadata::isStrict() || $class['isStrict']) && $redundant = array_keys(array_diff_key($data, $class['types']))
            ) {
                throw new StrictViolationException($class['name'], $redundant);
            }
            $compiled = array_filter(array_map(fn($t) => $t['defaults'] ?? null, $class['types']), fn($v) => $v !== null);
            $data     = array_merge($compiled, $data);
            foreach ($class['classTreeReversed'] as $classname) {
                $ref = Metadata::get($classname);
                if ($ref === null || !($ref['hasPrepareInput'] ?? false)) {
                    continue;
                }
                $data = array_merge($data, array_intersect_key($classname::prepareInput($data), $data));
            }
            $class['hydrator']($this, Resolver::properties($data, $class['types'], $errorPath));
        });
    }
    /**
     * Compiles metadata for $object's class and its ancestors on first use.
     * See Internal\Scanner::compile().
     *
     * @param self $object The instance being constructed; used to seed ReflectionClass and determine DTO/VO/SVO type.
     * @throws InvalidSpecException
     * @throws InvalidVisibilityException
     * @throws InvalidArrayOfTargetException
     * @throws InvalidArrayOfUsageException
     * @return Caches
     */
    final protected static function buildPropertyInheritanceChain(self $object): array
    {
        return Scanner::compile($object);
    }

    /**
     * Resolves one raw value against one property's compiled type metadata.
     * See Internal\Resolver::value() for the resolution order.
     *
     * @param Type $type Compiled property type metadata.
     * @param mixed $value The raw input value to resolve against the declared type.
     * @param bool $tryJson When true, decode a JSON string for a plain `array` target (with() only).
     * @return mixed
     */
    final protected static function resolveValue(array $type, mixed $value, bool $tryJson = false): mixed
    {
        return Resolver::value($type, $value, $tryJson);
    }

    /**
     * Logs redundant keys (present in input but absent in class definition)
     * to a debug file. Only active when debug mode is enabled via debug().
     * Includes timestamp, class name, redundant keys, full input, and stack trace.
     *
     * @param array $data The full input array passed to the constructor.
     * @param class-string $class The FQCN of the class being constructed.
     * @param string $path The configured debug log directory.
     * @throws DebugLogDirectoryInvalidException
     * @return void
     */
    private static function logging(array $data, string $class, string $path): void
    {
        if (!is_dir($path)) {
            throw new DebugLogDirectoryInvalidException($path);
        }
        $redundant = array_diff_key($data, Metadata::get($class)['types']);
        if ($redundant) {
            file_put_contents("$path/ImmutableBaseDebugLog.log", json_encode([
                'time'      => date('Y-m-d H:i:s'), 'object'   => $class,
                'redundant' => array_keys($redundant), 'input' => $data,
                'trace'     => (new \Exception())->getTraceAsString(),
            ]) . "\n", FILE_APPEND | LOCK_EX);
        }
    }
    /**
     * Converts a value to its array-serializable form for toArray()/toJson().
     * Dispatch order matters: SVO and BackedEnum both have ->value, but SVO
     * must be checked first. UnitEnum serializes to ->name since it has no
     * backed value. An ImmutableBase instance delegates to its own toArray().
     *
     * Scan-time validation keeps foreign objects out of typed properties, but
     * `mixed` and plain `array` properties can still hold one: a
     * JsonSerializable is serialized through jsonSerialize(), anything else
     * throws InvalidSerializeTargetException.
     *
     * @param mixed $value The property value to convert: scalar passthrough, SVO→value, enum→value/name, IB→toArray().
     * @throws InvalidSerializeTargetException
     * @return mixed The array-serializable representation.
     */
    private static function toArrayOrValue(mixed $value, KeyCase | bool $keyCase = false)
    {
        return match (true) {
            !\is_object($value)                 => $value,
            $value instanceof SingleValueObject => $value->value,
            $value instanceof BackedEnum        => $value->value,
            $value instanceof UnitEnum          => $value->name,
            $value instanceof self              => $value->toArray($keyCase),
            $value instanceof JsonSerializable  => $value->jsonSerialize(),
            default                             => throw new InvalidSerializeTargetException(get_debug_type($value)),
        };
    }
    /**
     * Recursively compares two arrays for deep equality: same keys (including
     * keys holding null) and pairwise-equal values per valueEquals().
     *
     * @param array $a Left-hand array to compare.
     * @param array $b Right-hand array to compare.
     * @return bool
     */
    private static function arrayEquals(array $a, array $b): bool
    {
        if (\count($a) !== \count($b)) {
            return false;
        }
        foreach ($a as $k => $v) {
            if (!\array_key_exists($k, $b) || !self::valueEquals($v, $b[$k])) {
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
            \is_array($a)          => \is_array($b) && self::arrayEquals($a, $b),
            $a instanceof self     => $b instanceof self && $a::class === $b::class && $a->equals($b),
            $a instanceof UnitEnum => $a === $b,
            \is_object($a)         => throw new InvalidCompareTargetException(get_debug_type($a)),
            default                => $a === $b,
        };
    }

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
    private static function parseDeepPath(string $path, string $separator, array $values): array
    {
        [$root, $rest] = explode(
            $separator,
            /** Note: Using str_ireplace because str_replace cannot reach 100% branch coverage. */
            str_ireplace(['[', ']'], [$separator, ''], $path),
            2
        );
        if (!(\is_array($values[$root] ?? null) || ($values[$root] ?? null) instanceof self)) {
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
    private static function resolveDeepUpdates(array &$values, array $deepUpdates, array $types, string $separator,  ? string &$errorPath) : void
    {
        foreach ($deepUpdates as $root => $sub) {
            $errorPath     = $root;
            $current       = $values[$root];
            $values[$root] = match (true) {
                $current instanceof self => $current->with($sub, $separator),
                default                  => self::applyArrayDeepUpdate($current, $sub, $separator),
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
                    $current[$index] instanceof self => $current[$index]->with($deeperValues, $separator),
                    \is_array($current[$index])      => self::applyArrayDeepUpdate($current[$index], $deeperValues, $separator),
                    default                          => $current[$index], // scalar, can't traverse
                };
            }
        }

        return $current;
    }
    /**
     * Loads pre-generated property metadata from a cache file, bypassing
     * reflection-based scanning. The cache must return an associative array
     * keyed by fully-qualified class name. Uses require_once to prevent
     * double-loading; for test scenarios, set state()['cachedMeta'] directly.
     *
     * @return void
     */
    final public static function loadCache(): void
    {
        Metadata::loadCache();
    }

    /**
     * Enables or disables debug logging. When enabled, redundant keys in
     * fromArray/fromJson input are logged to {path}/ImmutableBaseDebugLog.log.
     * Pass null to disable.
     *
     * @param string|null $path Directory path for log output, or null to disable debug mode.
     * @return void
     */
    final public static function debug(string | null $path): void
    {
        Metadata::setDebugPath($path);
    }

    /**
     * Enables or disables global strict mode. When enabled, all non-#[Lax]
     * classes reject input arrays containing keys not defined as properties.
     *
     * @param bool $on True to enable global strict mode, false to disable.
     * @return void
     */
    final public static function strict(bool $on): void
    {
        Metadata::setStrict($on);
    }

    /**
     * Named constructor from an associative array.
     *
     * @param array $array Associative array keyed by property name.
     * @return static
     */
    final public static function fromArray(array $array): static
    {
        return new static($array);
    }

    /**
     * Named constructor from a JSON string. Rejects non-object JSON
     * (e.g. "[1,2,3]") — the decoded result must be an associative array.
     *
     * @param string $data JSON-encoded object string.
     * @return static
     */
    final public static function fromJson(string $data): static
    {
        $trimmed = trim($data);
        if (($trimmed[0] ?? '') === '[' && $trimmed !== '[]') {
            throw new InvalidJsonException();
        }

        return new static(Json::decode($data, false));
    }

    /**
     * Serializes the object to an associative array. Respects #[SkipOnNull]
     * (omits null-valued properties) and #[KeepOnNull] (overrides SkipOnNull
     * to retain null). ArrayOf properties are recursively serialized via
     * toArrayOrValue(). toJson() delegates entirely to this method to
     * guarantee serialization consistency.
     *
     * @param KeyCase|bool $keyCase
     *     Controls the key format of the serialized output.
     *     - false (default): Use property names as-is.
     *     - true: Use each property's #[OutputKeyTo] case (falling back to the
     *       class-level #[OutputKeyTo]); nested objects apply their own.
     *     - KeyCase::*: Force all keys (including nested) to the specified case.
     * @return array
     */
    final public function toArray(KeyCase | bool $keyCase = false): array
    {
        $types = Metadata::get(static::class)['types'];
        foreach (get_object_vars($this) as $name => $value) {
            $type = $types[$name];
            if ($type['skipOnNull'] && $value === null && !$type['keepOnNull']) {
                continue;
            }
            $outputName = match (true) {
                $keyCase instanceof KeyCase => KeyCaser::convert($name, $keyCase),
                $keyCase                    => $type['outputKey'],
                default                     => $name,
            };
            $result[$outputName] = \is_array($value)
            ? array_map(fn($v) => self::toArrayOrValue($v, $keyCase), $value)
            : self::toArrayOrValue($value, $keyCase);
        }

        return $result ?? [];
    }

    /**
     * Serializes the object to a JSON string. Delegates to toArray() to
     * ensure SkipOnNull/KeepOnNull behavior is consistent across both
     * serialization formats.
     *
     * @param KeyCase|bool $keyCase
     *     Controls the key format of the serialized output.
     *     - false (default): Use property names as-is.
     *     - true: Use each property's #[OutputKeyTo] case (falling back to the
     *       class-level #[OutputKeyTo]); nested objects apply their own.
     *     - KeyCase::*: Force all keys (including nested) to the specified case.
     * @return string
     */
    final public function toJson(KeyCase | bool $keyCase = false): string
    {
        return json_encode($this->toArray($keyCase));
    }

    /**
     * Performs a deep structural equality check between two ImmutableBase instances.
     * Requires exact class match (no polymorphic comparison). For SVOs,
     * compares the wrapped value directly. For compound objects, recursively
     * compares each property with valueEquals():
     *   - Array → recursive, shape-sensitive comparison
     *   - ImmutableBase object → same class and recursive equals()
     *   - Enum → identity (covers both UnitEnum and BackedEnum)
     *   - Scalar / null → strict identity (===)
     *   - Foreign object (via `mixed` / plain `array`) → InvalidCompareTargetException
     *
     * @param static $value The instance to compare against; must be the exact same class.
     * @throws InvalidCompareTargetException If the target is not of the same class.
     * @return bool Returns true if all properties are identical, false otherwise.
     */
    final public function equals(self $value): bool
    {
        if ($value::class !== static::class) {
            throw new InvalidCompareTargetException(static::class, $value::class);
        }
        if ($this instanceof SingleValueObject) {
            /** @var SingleValueObject $value */
            return $this->value === $value->value;
        }
        return self::arrayEquals(get_object_vars($this), get_object_vars($value));
    }

    /**
     * Creates a new instance with selectively updated properties. Supports
     * three input formats: associative array, object (cast to array), or
     * JSON string. For SVOs, replaces the entire wrapped value.
     *
     * Input keys are remapped before resolution when class-level or property-level
     * #[InputKeyTo] attributes are present (see Internal\KeyCaser::remapInput()).
     *
     * Dot-notation and bracket-notation paths (e.g. "customer.address.city"
     * or "items[0].sku") are resolved into nested with() calls. The separator
     * can be customized (e.g. "/" for JSON Pointer-like paths). Deep path
     * targets must resolve to an ImmutableBase instance or array; scalar targets throw
     * InvalidWithPathException.
     *
     * Keys that name no property are ignored, except under strict mode
     * (global or #[Strict], unless #[Lax]), where they throw
     * StrictViolationException exactly as they do during construction.
     *
     * @param string|array|object $data Update payload: associative array, object (cast to array), or JSON string.
     * @param string $separator Path delimiter for deep notation; empty string disables deep path parsing.
     * @return static
     */
    final public function with(string | array | object $data, string $separator = '.'): static
    {
        $static = static::class;
        if ($this instanceof SingleValueObject) {
            return $data instanceof $static ? $data : $static::from($data);
        }

        return self::executeSafely(function ($static, &$errorPath) use ($data, $separator) {
            $values         = get_object_vars($this);
            $props          = Metadata::get($static);
            $types          = $props['types'];
            $normalizedData = match (\is_string($data)) {
                true    => Json::decode($data, false),
                default => (array) $data
            };
            if ($props['inputKeyCase'] !== null || $props['propertyInputKeyCases'] !== null) {
                $normalizedData = KeyCaser::remapInput($normalizedData, $props);
            }
            foreach ($normalizedData as $path => $value) {
                $errorPath = $path;
                if ($separator !== '' && strpbrk($path, "$separator\[")) {
                    [$root, $rest]             = self::parseDeepPath($path, $separator, $values);
                    $deepUpdates[$root][$rest] = $value;
                    $errorPath                 = $root;
                } elseif (\array_key_exists($path, $values) && isset($types[$path])) {
                    $values[$path] = Resolver::value($types[$path], $value, true);
                } else {
                    $undeclared[] = $path;
                }
            }
            if (isset($undeclared) && !$props['isLax'] && (Metadata::isStrict() || $props['isStrict'])) {
                $errorPath = null; // a class-level violation, as in the constructor
                throw new StrictViolationException($props['name'], $undeclared);
            }
            if (isset($deepUpdates)) {
                self::resolveDeepUpdates($values, $deepUpdates, $types, $separator, $errorPath);
            }
            $instance = Metadata::reflection($static)->newInstanceWithoutConstructor();
            $props['hydrator']($instance, $values);
            if (!$props['isDTO']) {
                /** @var class-string<ValueObject> $static */
                $static::enforceValidationRules(
                    $instance,
                    $props['validateFromSelf'] ? $props['classTree'] : $props['classTreeReversed'],
                    Metadata::all()
                );
            }

            return $instance;
        });
    }
    /**
     * Preprocessing step for input normalization before property resolution.
     * Called after defaults are merged and key remapping (#[InputKeyTo]) is applied,
     * before type resolution and hydration. Declare in subclasses to normalize values,
     * derive fields, or inject context. Only keys already present in $data are written back.
     *
     * @param array<string, mixed> $data Merged input data (includes defaults)
     * @return array<string, mixed>
     */
    protected static function prepareInput(array $data): array
    {
        return []; // @codeCoverageIgnore
    }

    /**
     * Declares default values for properties that should be populated
     * when absent from input data. Return an associative array keyed
     * by property name — only keys matching declared property names
     * are recognized; unmatched keys are silently ignored.
     *
     * Resolution priority during construction:
     *   1. Explicit input value (fromArray / fromJson)
     *   2. defaultValues()[$propertyName]
     *   3. #[Defaults] attribute value
     *   4. null (if nullable) or RequiredValueException
     *
     * Values may be of any type valid for the target property, including
     * ImmutableBase instances and other objects. However, non-serializable
     * values (objects, Closures, resources) will be excluded from the
     * cache file generated by ib-cacher and resolved at runtime instead.
     *
     * This method should be purely declarative — avoid side effects,
     * external I/O, or input-dependent logic. It may be invoked during
     * both cache generation and runtime construction.
     *
     * @return array<property-string, mixed>
     */
    public static function defaultValues(): array
    {
        return [];
    }
}

Metadata::loadCache();
