<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase;

use BackedEnum;
use Closure;
use JsonSerializable;
use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Attributes\Defaults;
use ReallifeKip\ImmutableBase\Attributes\InputKeyTo;
use ReallifeKip\ImmutableBase\Attributes\KeepOnNull;
use ReallifeKip\ImmutableBase\Attributes\Lax;
use ReallifeKip\ImmutableBase\Attributes\OutputKeyTo;
use ReallifeKip\ImmutableBase\Attributes\SkipOnNull;
use ReallifeKip\ImmutableBase\Attributes\Spec;
use ReallifeKip\ImmutableBase\Attributes\Strict;
use ReallifeKip\ImmutableBase\Attributes\ValidateFromSelf;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Enums\Native;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\DebugLogDirectoryInvalidException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidArrayOfTargetException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidArrayOfUsageException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidCompareTargetException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidKeyCaseException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidPropertyTypeException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidSerializeTargetException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidSpecException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidVisibilityException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidWithPathException;
use ReallifeKip\ImmutableBase\Exceptions\ImmutableBaseException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidJsonException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\RequiredValueException;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\StrictViolationException;
use ReallifeKip\ImmutableBase\Internal\Json;
use ReallifeKip\ImmutableBase\Internal\Metadata;
use ReallifeKip\ImmutableBase\Internal\Resolver;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use ReallifeKip\ImmutableBase\Objects\SingleValueObject;
use ReallifeKip\ImmutableBase\Objects\ValueObject;
use ReallifeKip\ImmutableBase\Types;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
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
     * #[InputKeyTo] attributes are present (see applyInputKeyRemap()).
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
                $data = self::applyInputKeyRemap($data, $class);
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
     * Walks the class hierarchy from the concrete class up to ImmutableBase,
     * scanning and compiling property metadata for each ancestor that hasn't
     * been processed yet. Supports three metadata sources:
     *
     *   1. Compiled metadata — already registered (skip via continue)
     *   2. Cached metadata — pre-generated by ib-cacher (restore validate method),
     *      used only while it still matches the class (see cacheMatchesClass())
     *   3. Reflection — full scan via scanProperties()
     *
     * After metadata is resolved, each property type is compiled by
     * Internal\Resolver::compile() and the class gets a hydrator closure for
     * readonly assignment.
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
        $self = self::class;
        for ($ref = Metadata::reflection($object::class); $ref && $ref->name !== $self; $ref = $ref->getParentClass()) {
            $classname = $ref->name;
            if (Metadata::has($classname)) {
                continue;
            }
            if (($cached = Metadata::cached($classname)) !== null && self::cacheMatchesClass($cached, $ref)) {
                $props                   = $cached;
                $props['validateMethod'] = !$props['hasValidate'] ?: $ref->getMethod('validate');
            } else {
                $props = self::scanProperties(
                    $ref,
                    match (true) {
                        $object instanceof DataTransferObject => [true, false, false],
                        $object instanceof SingleValueObject  => [false, true, true],
                        default                               => [false, true, false]
                    }
                );
            }
            $props['types']    = array_map(Resolver::compile(...), $props['types']);
            $props['hydrator'] = self::createHydrator($classname, array_keys($props['types']));
            Metadata::put($classname, $props);
        }

        return Metadata::all();
    }

    /**
     * Whether a pre-generated cache entry still describes $ref: the same
     * property names, each with the same declared type and nullability.
     * A class edited after ib-cacher ran (a property added, removed, renamed
     * or retyped) fails the check and is scanned by reflection instead, so a
     * stale cache degrades to the uncached path rather than to wrong
     * metadata. Changes to attributes or methods alone are not detected.
     *
     * @param Property $cached
     */
    private static function cacheMatchesClass(array $cached, ReflectionClass $ref): bool
    {
        $properties = $ref->getProperties();
        if (\count($properties) !== \count($cached['types'])) {
            return false;
        }
        foreach ($properties as $property) {
            $type  = $property->getType();
            $entry = $cached['types'][$property->name] ?? null;
            if (
                $type === null || $entry === null
                || $entry['allowsNull'] !== $type->allowsNull()
                || $entry['typename']['string'] !== ($type instanceof ReflectionNamedType ? $type->getName() : (string) $type)
            ) {
                return false;
            }
        }

        return true;
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
     * Reflects all public properties of a class and assembles the full
     * property metadata structure. Extracts class-level attributes (#[Strict],
     * #[Lax], #[SkipOnNull], #[ValidateFromSelf], #[Spec], #[InputKeyTo],
     * #[OutputKeyTo]) and builds the validation lineage via classTree for
     * enforceValidationRules(). Also collects per-property InputKeyTo overrides
     * into `propertyInputKeyCases` for use by applyInputKeyRemap().
     *
     * @param ReflectionClass $ref The class to scan.
     * @param array{bool, bool, bool} $flags Tuple of [isDTO, isVO, isSVO] indicating the object's base type.
     * @throws InvalidSpecException
     * @return Property
     */
    private static function scanProperties(ReflectionClass $ref, array $flags): array
    {
        [$isDTO, $isVO, $isSVO] = $flags;
        $classname              = $ref->name;
        if (!$isDTO && $ref->getAttributes(Spec::class)) {
            $spec = self::getAttributeArgument($ref, Spec::class);
            if (!\is_string($spec) || empty($spec = mb_trim($spec))) {
                throw new InvalidSpecException($classname);
            }
        }
        $hasValidate     = $isDTO ? false : $ref->hasMethod('validate');
        $hasPrepareInput = $ref->hasMethod('prepareInput') && $ref->getMethod('prepareInput')->getDeclaringClass()->name === $classname;
        $classTree       = [$classname => $classname] + class_parents($classname);
        $prop            = [
            'ref'               => $ref,
            'name'              => $classname,
            'isStrict'          => $ref->getAttributes(Strict::class) !== [],
            'isLax'             => $ref->getAttributes(Lax::class) !== [],
            'isDTO'             => $isDTO,
            'isVO'              => $isVO,
            'isSVO'             => $isSVO,
            'validateFromSelf'  => $ref->getAttributes(ValidateFromSelf::class) !== [],
            'skipOnNull'        => $ref->getAttributes(SkipOnNull::class) !== [],
            'hasValidate'       => $hasValidate,
            'hasPrepareInput'   => $hasPrepareInput,
            'validateMethod'    => $hasValidate ? $ref->getMethod('validate') : false,
            'spec'              => $spec ?? null,
            'classTree'         => $classTree,
            'classTreeReversed' => array_reverse($classTree),
            'inputKeyCase'      => self::getValidatedKeyCase(self::getAttributeArgument($ref, InputKeyTo::class), 'InputKeyTo', "$classname::class"),
            'types'             => [],
        ];
        $obj      = null;
        $defaults = [];
        if (!$ref->isAbstract()) {
            /** @var ImmutableBase $obj */
            $obj      = $ref->newInstanceWithoutConstructor();
            $defaults = $obj::defaultValues();
        }
        $classOutputKeyCase = self::getValidatedKeyCase(self::getAttributeArgument($ref, OutputKeyTo::class), 'OutputKeyTo', "$classname::class");
        foreach ($ref->getProperties() as $property) {
            $name                 = $property->name;
            $prop['types'][$name] = self::scanProperty($property, $prop['skipOnNull'], $classOutputKeyCase);
            $default              = match (true) {
                $obj === null                       => null,
                \array_key_exists($name, $defaults) => $defaults[$name],
                default                             => self::getAttributeArgument($property, Defaults::class)
            };
            if ($default !== null) {
                $prop['types'][$name]['defaults'] = $default;
            }
        }
        foreach ($prop['types'] as $name => $type) {
            if ($type['hasInputKeyOverride']) {
                $propInputKeyCases[$name] = $type['inputKeyCase'];
            }
        }
        $prop['propertyInputKeyCases'] = $propInputKeyCases ?? null;

        return $prop;
    }

    /**
     * Extracts type metadata from a single property. Enforces that all
     * properties must be public (readonly is implicit via the class declaration).
     * Delegates to scanNamedType() or scanUnionType() based on reflection type,
     * and resolves #[ArrayOf], #[SkipOnNull], #[KeepOnNull], #[InputKeyTo],
     * #[OutputKeyTo] attributes. Property-level #[InputKeyTo] stores the target
     * KeyCase in `inputKeyCase`; combined with `hasInputKeyOverride`, this is
     * picked up by scanProperties() for applyInputKeyRemap(). Property-level
     * #[OutputKeyTo] (falling back to the class-level case) pre-computes `outputKey`.
     *
     * @param ReflectionProperty $property         The property to extract metadata from.
     * @param bool               $classSkipOnNull  Whether the owning class has a class-level #[SkipOnNull] attribute.
     * @param KeyCase|null       $classOutputKeyCase Class-level OutputKeyTo case, used when the property has none.
     * @throws InvalidVisibilityException
     * @return Type
     */
    private static function scanProperty(ReflectionProperty $property, bool $classSkipOnNull, ?KeyCase $classOutputKeyCase = null): array
    {
        if (!$property->isPublic()) {
            throw new InvalidVisibilityException($property->name);
        }
        $type    = $property->getType();
        $name    = $property->name;
        $target  = $property->getDeclaringClass()->getName() . "::$$name";
        $inCase  = self::getValidatedKeyCase(self::getAttributeArgument($property, InputKeyTo::class), 'InputKeyTo', $target);
        $outCase = self::getValidatedKeyCase(self::getAttributeArgument($property, OutputKeyTo::class), 'OutputKeyTo', $target) ?? $classOutputKeyCase;

        return [
            'ref'                 => $type,
            'propertyRef'         => $property,
            'allowsNull'          => $type->allowsNull(),
            'arrayOf'             => self::resolveArrayOf($property, $type),
            'propertyName'        => $name,
            'inputKeyCase'        => $inCase,
            'hasInputKeyOverride' => $inCase !== null,
            'outputKey'           => $outCase !== null ? self::convertCase($name, $outCase) : $name,
            'skipOnNull'          => $classSkipOnNull || $property->getAttributes(SkipOnNull::class) !== [],
            'keepOnNull'          => $property->getAttributes(KeepOnNull::class) !== [],
            'isUnion'             => !($type instanceof ReflectionNamedType),
        ] + ($type instanceof ReflectionNamedType ? self::scanNamedType($type) : self::scanUnionType($type));
    }

    /**
     * Validates and resolves the #[ArrayOf] attribute on a property.
     * Each target class must be an ImmutableBase descendant or Native scalar.
     * The property type must be exactly `array` (not a union or any other type).
     *
     * @param ReflectionProperty $property The property to inspect for #[ArrayOf].
     * @param ReflectionNamedType|ReflectionUnionType $refType The property's declared type, used to enforce the `array` constraint.
     * @throws InvalidArrayOfTargetException
     * @throws InvalidArrayOfUsageException
     * @return list<non-empty-string>|null
     */
    private static function resolveArrayOf(ReflectionProperty $property, ReflectionNamedType | ReflectionUnionType $refType): ?array
    {
        $args = self::getAttributeArgument($property, ArrayOf::class, false);
        if ($args === null) {
            return null;
        }
        if ($args === []) {
            throw new InvalidArrayOfTargetException();
        }
        if ($refType instanceof ReflectionUnionType) {
            throw new InvalidArrayOfUsageException($property->name, (string) $refType);
        }
        if ($refType->getName() !== 'array') {
            throw new InvalidArrayOfUsageException($property->name, (string) $refType);
        }
        foreach ($args as $arg) {
            $resolved[] = match (true) {
                $arg instanceof Native         => $arg->value,
                enum_exists($arg)              => $arg,
                !is_a($arg, self::class, true) => throw new InvalidArrayOfTargetException(),
                default                        => $arg,
            };
        }

        return $resolved;
    }

    /**
     * Validates that a value resolved from #[InputKeyTo] or #[OutputKeyTo] is
     * either null (attribute absent) or a KeyCase enum instance.
     *
     * @param mixed  $value     The raw attribute argument.
     * @param string $attribute Short attribute name ('InputKeyTo' or 'OutputKeyTo').
     * @param string $target    Human-readable scan location for the exception message.
     * @throws InvalidKeyCaseException
     */
    private static function getValidatedKeyCase(mixed $value, string $attribute, string $target): ?KeyCase
    {
        if ($value !== null && !($value instanceof KeyCase)) {
            throw new InvalidKeyCaseException($value, $attribute, $target);
        }

        return $value;
    }

    /**
     * Scans a single named type, enforcing the forbidden type rule:
     * `object`, `iterable`, non-ImmutableBase classes, and non-enum classes are rejected at
     * definition time via InvalidPropertyTypeException. Standalone `null` passes through
     * here (as a builtin) and is rejected later in Internal\Resolver::compile().
     *
     * When called for a top-level property ($fromUnion=false), also resolves
     * whether the type is an SVO (recorded in the metadata). Union members
     * skip this; their matchers are chosen by type name in Internal\Resolver.
     *
     * @param ReflectionNamedType $refType The named type to scan and validate.
     * @throws InvalidPropertyTypeException
     * @return NamedType|NamedTypeFromUnion
     */
    private static function scanNamedType(ReflectionNamedType $refType, bool $fromUnion = false): array
    {
        $typename  = $refType->getName();
        $isBuiltin = $refType->isBuiltin();
        $isEnum    = !$isBuiltin && enum_exists($typename);
        if ($typename === 'object' || $typename === 'iterable' || (!$isBuiltin && !is_a($typename, self::class, true) && !$isEnum)) {
            throw new InvalidPropertyTypeException($typename);
        }
        $result = [
            'typename'  => [
                'string' => $typename,
                'array'  => [$typename],
            ],
            'isBuiltin' => $isBuiltin,
            'isEnum'    => $isEnum,
        ];
        if (!$fromUnion) {
            $compiled        = Metadata::get($typename);
            $result['isSVO'] = $compiled ? $compiled['isSVO'] : (!$isBuiltin && is_a($typename, SingleValueObject::class, true));
        }

        return $result;
    }

    /**
     * Scans a union type by delegating each member to scanNamedType().
     * PHP does not allow nested unions, so each member is guaranteed to be
     * a ReflectionNamedType. Members are scanned with $fromUnion=true to
     * skip isSVO resolution (matchers are chosen by type name in Internal\Resolver).
     *
     * @param ReflectionUnionType $refType The union type whose members will be individually scanned.
     * @return UnionType
     */
    private static function scanUnionType(ReflectionUnionType $refType): array
    {
        $unionTypes = $refType->getTypes();

        return [
            'typename' => [
                'string' => (string) $refType,
                'array'  => array_map(static fn(ReflectionNamedType $type): string => $type->getName(), $unionTypes),
            ],
            'types'    => array_map(static fn(ReflectionNamedType $type) => self::scanNamedType($type, true), $unionTypes),
        ];
    }

    /**
     * Creates a Closure bound to the target class scope, enabling direct
     * assignment to readonly properties. This bypasses the readonly restriction
     * because the closure operates within the declaring class's scope.
     *
     * @param class-string $classname The declaring class; the closure is bound to this scope.
     * @param list<string> $propertyNames Property names to assign during hydration.
     * @return Hydrator
     */
    private static function createHydrator(string $classname, array $propertyNames): Closure
    {
        return Closure::bind(
            static function (self $obj, array $resolved) use ($propertyNames): void {
                foreach ($propertyNames as $name) {
                    $obj->$name = $resolved[$name];
                }
            },
            null,
            $classname
        );
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
     * Applies class-level and property-level InputKeyTo remapping to an input array.
     *
     * Class-level: converts every string key to the declared KeyCase.
     * Property-level overrides: for each property that carries its own InputKeyTo,
     * scans the original (pre-class-remap) input and converts each key to the
     * property's declared case; on the first match, writes the value under the
     * property name directly.
     *
     * @param array<string|int, mixed> $data  Raw input array.
     * @param array{inputKeyCase: KeyCase|null, propertyInputKeyCases: array<string, KeyCase>|null} $class Compiled class metadata.
     * @return array<string|int, mixed>
     */
    private static function applyInputKeyRemap(array $data, array $class): array
    {
        $original = $data;
        if ($class['inputKeyCase'] !== null) {
            $data = self::remapInputKeys($original, $class['inputKeyCase']);
        }
        if ($class['propertyInputKeyCases'] !== null) {
            foreach ($class['propertyInputKeyCases'] as $propName => $propKeyCase) {
                foreach ($original as $inputKey => $inputValue) {
                    if (\is_string($inputKey) && self::convertCase($inputKey, $propKeyCase) === $propName) {
                        $data[$propName] = $inputValue;
                        break;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Converts all string keys of an input array to the specified naming convention.
     * Integer keys are passed through unchanged.
     *
     * @param array<string|int, mixed> $data The raw input array.
     * @param KeyCase $keyCase The target naming convention to apply to each key.
     * @return array<string|int, mixed>
     */
    private static function remapInputKeys(array $data, KeyCase $keyCase): array
    {
        foreach ($data as $k => $v) {
            $remapped[\is_string($k) ? self::convertCase($k, $keyCase) : $k] = $v;
        }

        return $remapped ?? [];
    }
    /**
     * Converts a property name to the specified naming convention.
     * Splits the name on camelCase/PascalCase boundaries, underscores,
     * hyphens, and whitespace, then rejoins in the target case.
     *
     * @param string $name The property name to convert.
     * @param KeyCase $keyCase The target naming convention.
     * @return string
     */
    private static function convertCase(string $name, KeyCase $keyCase): string
    {
        static $separator = [
            'PascalSnake' => '_',
            'Pascal'      => '',
            'Train'       => '-',
            'Snake'       => '_',
            'Kebab'       => '-',
        ];
        $words = array_map(
            'strtolower',
            preg_split('/(?<=[a-z\d])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])|[-_\s]+/', $name, -1, PREG_SPLIT_NO_EMPTY)
        );

        return match ($keyCase) {
            KeyCase::Snake, KeyCase::Kebab => join($separator[$keyCase->value], $words),
            KeyCase::Macro      => join('_', array_map('strtoupper', $words)),
            KeyCase::Camel      => $words[0] . join('', array_map('ucfirst', \array_slice($words, 1))),
            KeyCase::CamelKebab => "{$words[0]}-" . join('-', array_map('ucfirst', \array_slice($words, 1))),
            default => join($separator[$keyCase->value], array_map('ucfirst', $words))
        };
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
                $keyCase instanceof KeyCase => self::convertCase($name, $keyCase),
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
     * #[InputKeyTo] attributes are present (see applyInputKeyRemap()).
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
                $normalizedData = self::applyInputKeyRemap($normalizedData, $props);
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
