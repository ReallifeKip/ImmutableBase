<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase;

use BackedEnum;
use Closure;
use Composer\Autoload\ClassLoader;
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
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidSpecException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidVisibilityException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidWithPathException;
use ReallifeKip\ImmutableBase\Exceptions\ImmutableBaseException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidEnumValueException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidJsonException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidValueException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\RequiredValueException;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\InvalidArrayOfItemException;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\StrictViolationException;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\ValidationChainException;
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
     * Wraps construction and mutation operations in a depth-tracked error boundary.
     * Maintains a static depth counter to enable hierarchical error path tracking
     * via prependPath() — nested constructions increment depth, and the outermost
     * catch assembles the full property path chain (e.g. "OrderDTO.customer.email").
     *
     * @param callable(string, ?string): mixed $callback Receives the FQCN of the calling class and a
     *                                                   by-reference error path variable for prependPath()
     */
    final protected static function executeSafely(callable $callback): mixed
    {
        $static    = static::class;
        $errorPath = null;
        ImmutableBaseException::$depth++;
        try {
            $result = $callback($static, $errorPath);
            ImmutableBaseException::$depth--;

            return $result;
        } catch (ImmutableBaseException $e) {
            ImmutableBaseException::$depth--;
            throw $e->prependPath($static, $errorPath);
        }
    }
    /**
     * Returns a by-reference handle to the engine's internal state array.
     * Holds all runtime caches and configuration flags.
     *
     * @internal
     * DANGER: DO NOT USE THIS METHOD!
     * INTERNAL USE ONLY. MANIPULATING THIS STATE MANUALLY WILL CAUSE FATAL
     * UNINTENDED CONSEQUENCES, DATA CORRUPTION, OR UNSTABLE ENGINE BEHAVIOR!
     *
     * @return State
     */
    public static function &state(): array
    {
        static $s = [
            'debug'      => false,
            'logPath'    => null,
            'cachePath'  => null,
            'strict'     => false,
            'refs'       => [],
            'properties' => [],
            'cachedMeta' => [],
        ];

        return $s;
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
            $s        = &self::state();
            $defaults = static::defaultValues();
            if (!isset($s['properties'][$static])) {
                $this::buildPropertyInheritanceChain($this);
            }
            if ($s['debug']) {
                self::logging($data, $this::class);
            }
            $class = $s['properties'][$static];
            if ($class['inputKeyCase'] !== null || $class['propertyInputKeyCases'] !== null) {
                $data = self::applyInputKeyRemap($data, $class);
            }
            if (
                !$class['isLax'] &&
                ($s['strict'] || $class['isStrict']) && $redundant = array_keys(array_diff_key($data, $class['types']))
            ) {
                throw new StrictViolationException($class['name'], $redundant);
            }
            $class['hydrator']($this, self::resolvePropertyData($data, $class['types'], $defaults, $errorPath));
        });
    }
    /**
     * Walks the class hierarchy from the concrete class up to ImmutableBase,
     * scanning and compiling property metadata for each ancestor that hasn't
     * been processed yet. Supports three metadata sources:
     *
     *   1. state()['properties'] — already compiled (skip via continue)
     *   2. state()['cachedMeta'] — pre-generated cache (restore validate method)
     *   3. Reflection — full scan via scanProperties()
     *
     * After metadata is resolved, each property type gets a compiled resolver
     * closure via buildResolver() and a hydrator closure for readonly assignment.
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
        $s      = &self::state();
        $static = static::class;
        $self   = self::class;
        if (!isset($s['properties'][$static]) && !isset($s['refs'][$static])) {
            $s['refs'][$static] = new ReflectionClass($object);
        }
        for ($ref = $s['refs'][$static] ?? null; $ref && $ref?->name !== $self; $ref = $ref->getParentClass()) {
            $classname = $ref->name;
            if (isset($s['properties'][$classname])) {
                continue;
            }
            if (isset($s['cachedMeta'][$classname])) {
                $props                   = $s['cachedMeta'][$classname];
                $refClass                = $s['refs'][$classname] ??= new ReflectionClass($classname);
                $props['validateMethod'] = !$props['hasValidate'] ?: $refClass->getMethod('validate');
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
            foreach ($props['types'] as &$type) {
                // Coalesce defaults for cache-sourced metadata which omits runtime-only fields
                $type['resolver'] = self::buildResolver(
                    $type,
                    !($type['isBuiltin'] ??= false) && is_a($type['typename']['string'] ?? '', $self, true),
                    $type['isSVO'] ??= false
                );
            }
            $props['hydrator']           = self::createHydrator($classname, array_keys($props['types']));
            $s['properties'][$classname] = $props;
        }

        return $s['properties'];
    }

    /**
     * Iterates the compiled property type map, fills in missing keys with their
     * default values (attribute-declared, method-declared, or null), then
     * resolves each value against its declared type via resolveValue().
     *
     * @param array<string, mixed>  $data      Input array, mutated in place when a default is injected.
     * @param array<string, Type>   $types     Compiled property type metadata from scanProperties().
     * @param array<string, mixed>  $defaults  Default values returned by defaultValues().
     * @param string|null           $errorPath Reference updated to the current property name for error context.
     * @return array<string, mixed> Resolved property values keyed by property name.
     */
    private static function resolvePropertyData(array &$data, array $types, array $defaults,  ? string &$errorPath) : array
    {
        foreach ($types as $type) {
            $name = $errorPath = $type['propertyName'];
            if (!\array_key_exists($name, $data)) {
                $data[$name] = $type['defaults'] ?? match (true) {
                    \array_key_exists($name, $defaults) => $defaults[$name],
                    isset($type['propertyRef'])         => self::getAttributeArgument($type['propertyRef'], Defaults::class),
                    default                             => null
                };
            }
            match (true) {
                !isset($data[$name]) && !$type['allowsNull'] => throw new RequiredValueException($name),
                default                                      => $resolved[$name] = self::resolveValue($type, $data[$name] ?? null, false)
            };
        }

        return $resolved ?? [];
    }

    /**
     * Central value resolution dispatcher. Handles four cases in priority order:
     *   1. JSON-like string with $tryJson=true → parse and delegate to valueDecide()
     *   2. Null → accept if nullable, otherwise throw RequiredValueException
     *   3. ArrayOf property → delegate to arrayOfInitialize()
     *   4. Everything else → invoke the pre-compiled resolver closure
     *
     * @param Type $type Compiled property type metadata from scanProperties().
     * @param mixed $value The raw input value to resolve against the declared type.
     * @param bool $tryJson When true, speculatively parse JSON-like strings before type resolution.
     * @return mixed
     */
    final protected static function resolveValue(array $type, mixed $value, bool $tryJson = false): mixed
    {
        return match (true) {
            $tryJson && self::jsonLike($value) => self::valueDecide($type, self::jsonParser($value)),
            $value === null                    => $type['allowsNull'] ? null : throw new RequiredValueException($type['propertyName'] ?? $type['typename']['string']),
            ($arg = $type['arrayOf']) !== null => self::arrayOfInitialize($arg, $value),
            default                            => $type['resolver']($value)
        };
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
        $hasValidate = $isDTO ? false : $ref->hasMethod('validate');
        $classTree   = [$classname => $classname] + class_parents($classname);
        $prop        = [
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
     * The target class must be an ImmutableBase descendant, and the property type must
     * be exactly `array` (not a union or any other type).
     *
     * @param ReflectionProperty $property The property to inspect for #[ArrayOf].
     * @param ReflectionNamedType|ReflectionUnionType $refType The property's declared type, used to enforce the `array` constraint.
     * @throws InvalidArrayOfTargetException
     * @throws InvalidArrayOfUsageException
     * @return non-empty-string|null
     */
    private static function resolveArrayOf(ReflectionProperty $property, ReflectionNamedType | ReflectionUnionType $refType): ?string
    {
        $arg = self::getAttributeArgument($property, ArrayOf::class);

        return match (true) {
            $arg === null                           => null,
            $arg === []                             => throw new InvalidArrayOfTargetException(),
            $arg instanceof Native                  => $arg->value,
            enum_exists($arg)                       => $arg,
            !is_a($arg, self::class, true)          => throw new InvalidArrayOfTargetException(),
            $refType instanceof ReflectionUnionType => throw new InvalidArrayOfUsageException($property->name, (string) $refType),
            $refType->getName() !== 'array'         => throw new InvalidArrayOfUsageException($property->name, (string) $refType),
            default                                 => $arg
        };
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
     * here (as a builtin) and is rejected later in buildResolver().
     *
     * When called for a top-level property ($fromUnion=false), also resolves
     * whether the type is an SVO for use by buildResolver(). Union members
     * skip this since unionTypeDecide() resolves SVO status dynamically.
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
            $cached          = self::state()['properties'][$typename] ?? false;
            $result['isSVO'] = $cached ? $cached['isSVO'] : (!$isBuiltin && is_a($typename, SingleValueObject::class, true));
        }

        return $result;
    }

    /**
     * Scans a union type by delegating each member to scanNamedType().
     * PHP does not allow nested unions, so each member is guaranteed to be
     * a ReflectionNamedType. Members are scanned with $fromUnion=true to
     * skip isSVO resolution (handled dynamically in valueDecide()).
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
     * Resolves an array property annotated with #[ArrayOf] into a typed array
     * of ImmutableBase instances. Handles multiple input formats per element:
     *   - Already an instance of the target class (passthrough)
     *   - Associative array (fromArray)
     *   - SVO-compatible scalar (from)
     *   - Object (cast to array, then fromArray)
     *   - JSON string (parse, then fromArray)
     *
     * Empty arrays and non-array values are returned as-is to allow upstream
     * validation (e.g. nullable ArrayOf accepting null).
     *
     * @param non-empty-string $arg The #[ArrayOf] target type (ImmutableBase subclass FQCN or Native::* scalar name).
     * @param mixed $value The raw input value (array, JSON string, or passthrough for null).
     * @throws InvalidArrayOfItemException
     * @return mixed
     */
    private static function arrayOfInitialize(string $arg, mixed $value): mixed
    {
        if (\is_string($value)) {
            $value = self::jsonParser($value, false);
        }
        if (!\is_array($value) || empty($value)) {
            return $value;
        }
        $classExists = class_exists($arg);
        $isEnum      = $classExists && enum_exists($arg);
        $isSVO       = $classExists && is_a($arg, SingleValueObject::class, true);
        foreach ($value as $k => $v) {
            if (!$classExists) {
                match (true) {
                    get_debug_type($v) === $arg => $values[] = $v,
                    default                     => throw new InvalidArrayOfItemException($k, $arg)
                };
                continue;
            }
            $values[] = match (true) {
                $v instanceof $arg => $v,
                \is_array($v)      => $arg::fromArray($v),
                $isEnum            => self::analyzeEnum($arg, $v),
                $isSVO             => $arg::from($v),
                \is_object($v)     => $arg::fromArray((array) $v),
                \is_string($v)     => \is_array($json = self::jsonParser($v)) ? $arg::fromArray($json) : throw new InvalidArrayOfItemException($k, $arg),
                default            => throw new InvalidArrayOfItemException($k, $arg)
            };
        }

        return $values ?? [];
    }

    /**
     * Decodes a JSON string. When $returnInputOnException is true, returns
     * the raw json_decode result (null on failure) without throwing — used by
     * arrayOfInitialize() and resolveValue() for speculative parsing.
     * When false, throws InvalidJsonException on malformed input.
     *
     * @param string $data The raw JSON string to decode.
     * @param bool $returnInputOnException When true, returns the decode result silently on failure;
     *                                     when false, throws InvalidJsonException.
     * @throws InvalidJsonException
     * @return array<string|int, mixed>|string|int|float|bool|null
     */
    private static function jsonParser(string $data, bool $returnInputOnException = true): mixed
    {
        if (!self::jsonLike($data) && !$returnInputOnException) {
            throw new InvalidJsonException();
        }
        $data = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE && !$returnInputOnException) {
            throw new InvalidJsonException();
        }

        return $data;
    }
    /**
     * Compiles a type-specific resolver closure that validates and converts
     * input values at runtime. The resolver is built once during property
     * scanning and cached in the type metadata for repeated use.
     *
     * Dispatch order:
     *   1. Union types → defer to unionTypeDecide() for try-each resolution
     *   2. Non-builtin ImmutableBase subclass → fromArray / passthrough / SVO::from
     *   3. Non-builtin enum → passthrough if already an enum instance,
     *      string|int input is resolved via analyzeEnum()
     *   4. Builtin → strict type checking via builtinTypeResolver()
     *
     * @param Type $type Compiled property type metadata.
     * @param bool $isSub Whether the type is an ImmutableBase subclass (enables fromArray/from dispatch).
     * @param bool $isSVO Whether the type is specifically a SingleValueObject (enables scalar from() dispatch).
     * @return callable(mixed): mixed
     */
    private static function buildResolver(mixed $type, bool $isSub, bool $isSVO): callable
    {
        $typename = $type['typename']['string'];

        return match (true) {
            $type['isUnion']     => static fn(mixed $value)     => self::unionTypeDecide($type, $value),
            !$type['isBuiltin']  => match (true) {
                $isSub  => static fn(mixed $value): mixed  => match (true) {
                    \is_array($value)           => $typename::fromArray($value),
                    $value instanceof $typename => $value,
                    $isSVO                      => $typename::from($value),
                    default                     => throw new InvalidValueException($typename, $value)
                },
                default => static fn(mixed $value): mixed => match (true) {
                    $value instanceof $typename           => $value,
                    \is_string($value) || \is_int($value) => self::analyzeEnum($typename, $value),
                    default                               => throw new InvalidValueException($typename, $value),
                },
            },
            $typename === 'null' => throw new InvalidPropertyTypeException($typename),
            default              => self::builtinTypeResolver($typename),
        };
    }
    /**
     * Returns a strict type-checking closure for PHP builtin types.
     * Each resolver enforces exact type matching (no coercion under strict_types=1).
     * The `null` type is rejected at definition time as it represents a
     * contradictory declaration — a property that can only ever be null.
     * The `mixed` type (default branch) passes all values through without validation.
     *
     * @template T of (array|bool|float|int|string|null)
     * @param string $typename The PHP builtin type name (e.g. "string", "int", "array", "mixed").
     * @throws InvalidPropertyTypeException
     * @return callable(mixed): T
     */
    private static function builtinTypeResolver(string $typename)
    {
        return match ($typename) {
            'string' => static fn($v): string => \is_string($v) ? $v : throw new InvalidValueException('string', $v),
            'int'    => static fn($v): int    => \is_int($v) ? $v : throw new InvalidValueException('int', $v),
            'bool'   => static fn($v): bool   => \is_bool($v) ? $v : throw new InvalidValueException('bool', $v),
            'array'  => static fn($v): array => \is_array($v) ? $v : throw new InvalidValueException('array', $v),
            'float'  => static fn($v): float  => \is_float($v) ? $v : throw new InvalidValueException('float', $v),
            default  => static fn($v): mixed  => $v,
        };
    }
    /**
     * Resolves a value against a union type by attempting each member type
     * in declaration order. The first successful match wins. If all members
     * fail, throws InvalidValueException with the full union type signature.
     *
     * Catches InvalidValueException, ValidationChainException, and
     * InvalidEnumValueException to allow fallthrough to the next member.
     *
     * @param UnionType $unionType Compiled union type metadata containing all member types.
     * @param mixed $value The raw input value to resolve against the union members.
     * @throws InvalidValueException
     * @return mixed
     */
    private static function unionTypeDecide(array $unionType, mixed $value): mixed
    {
        foreach ($unionType['types'] as $type) {
            try {
                return self::valueDecide($type, $value);
            } catch (InvalidValueException | ValidationChainException | InvalidEnumValueException) {
                continue;
            }
        }
        throw new InvalidValueException($unionType['typename']['string'], $value);
    }
    /**
     * Runtime type resolution used exclusively by unionTypeDecide() for
     * individual union member matching. Unlike buildResolver() which compiles
     * closures at scan time, this performs inline dispatch per attempt.
     *
     * For non-builtin types, resolves in priority order:
     *   1. Already-constructed object (passthrough)
     *   2. String + enum class → analyzeEnum()
     *   3. Array + ImmutableBase subclass → fromArray()
     *   4. SVO subclass → from()
     *
     * For builtin types, performs strict type validation.
     *
     * @param NamedTypeFromUnion $type A single named type metadata entry (one member of a union).
     * @param mixed $value The raw input value to match against this type.
     * @throws InvalidValueException
     * @return mixed
     */
    private static function valueDecide(array $type, mixed $value): mixed
    {
        $typename = $type['typename']['string'];
        if (!$type['isBuiltin']) {
            return match (true) {
                $value instanceof $typename                                            => $value,
                (\is_string($value) || \is_int($value)) && $type['isEnum']             => self::analyzeEnum($typename, $value),
                \is_array($value) && is_a($typename, self::class, true)                => $typename::fromArray($value),
                is_a($typename, SingleValueObject::class, true) && !\is_object($value) => $typename::from($value),
                default                                                                => throw new InvalidValueException($typename, $value),
            };
        }
        if (
            !match ($typename) {
                'int'    => \is_int($value),
                'float'  => \is_float($value),
                'string' => \is_string($value),
                'bool'   => \is_bool($value),
                default  => \is_array($value)
            }
        ) {
            throw new InvalidValueException($typename, $value);
        }

        return $value;
    }
    /**
     * Resolves a string or integer value to an enum case. Tries two strategies:
     *   1. Constant lookup by name (works for both UnitEnum and BackedEnum)
     *   2. BackedEnum::tryFrom() by backed value
     *
     * This dual approach allows users to provide either the case name ("HIGH")
     * or the backed value (3) for BackedEnum types.
     *
     * @param class-string $class The fully-qualified enum class name.
     * @param string|int $value The case name or backed value to resolve.
     * @return BackedEnum|UnitEnum
     */
    private static function analyzeEnum(string $class, string | int $value): BackedEnum | UnitEnum
    {
        return match (true) {
            \defined($case = "$class::$value")                                       => constant($case),
            is_a($class, BackedEnum::class, true) && $case = $class::tryFrom($value) => $case,
            default                                                                  => throw new InvalidEnumValueException($class, $value)
        };
    }
    /**
     * Logs redundant keys (present in input but absent in class definition)
     * to a debug file. Only active when debug mode is enabled via debug().
     * Includes timestamp, class name, redundant keys, full input, and stack trace.
     *
     * @param array $data The full input array passed to the constructor.
     * @param class-string $class The FQCN of the class being constructed.
     * @throws DebugLogDirectoryInvalidException
     * @return void
     */
    private static function logging(array $data, string $class): void
    {
        $s    = self::state();
        $path = $s['logPath'];
        if (!is_dir($path)) {
            throw new DebugLogDirectoryInvalidException($path);
        }
        $redundant = array_diff_key($data, $s['properties'][static::class]['types']);
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
     * backed value. Any remaining object (guaranteed to be an ImmutableBase instance by
     * scan-time validation) delegates to its own toArray().
     *
     * @param mixed $value The property value to convert: scalar passthrough, SVO→value, enum→value/name, IB→toArray().
     * @return mixed The array-serializable representation.
     */
    private static function toArrayOrValue(mixed $value, KeyCase | bool $keyCase = false)
    {
        return match (true) {
            !\is_object($value)                 => $value,
            $value instanceof SingleValueObject => $value->value,
            $value instanceof BackedEnum        => $value->value,
            $value instanceof UnitEnum          => $value->name,
            default                             => $value->toArray($keyCase)
        };
    }
    /**
     * Recursively compares two arrays for deep equality, handling nested
     * ImmutableBase objects and sub-arrays. Non-ImmutableBase objects in plain `array` properties
     * (which bypass ArrayOf validation) throw InvalidCompareTargetException
     * since ImmutableBase cannot guarantee semantic equality for foreign objects.
     *
     * @param self $object The root ImmutableBase instance initiating the comparison (used for recursive dispatch).
     * @param array $a Left-hand array to compare.
     * @param array $b Right-hand array to compare.
     * @return bool
     */
    private static function arrayEquals(self $object, array $a, array $b): bool
    {
        if (\count($a) !== \count($b)) {
            return false;
        }
        foreach ($a as $k => $v) {
            if (isset($b[$k])) {
                $bv = $b[$k];
                if (
                    match (true) {
                        \is_array($v)  => self::arrayEquals($object, $v, $bv),
                        \is_object($v) => $v instanceof self ? $v->equals($bv) : throw new InvalidCompareTargetException(get_debug_type($v)),
                        default        => $v === $bv,
                    } === true
                ) {
                    continue;
                }
            }

            return false;
        }

        return true;
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
                $values[$root] = self::resolveValue($types[$root], $values[$root], false);
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
     * Fast check for JSON-like string values. Only triggers speculative
     * parsing for strings starting with '{' or '[' (after trimming whitespace).
     * Non-string values return false immediately to avoid unnecessary work
     * in the common case of scalar or object inputs.
     *
     * @param mixed $value The value to check; only strings starting with '{' or '[' return true.
     * @return bool
     */
    private static function jsonLike(mixed $value): bool
    {
        static $open = ['{' => true, '[' => true];

        return \is_string($value) && isset($open[trim($value)[0] ?? '']);
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
        $s    = &self::state();
        $path = $s['cachePath'] ??= dirname(dirname((new ReflectionClass(ClassLoader::class))->getFileName()), 2) . '/ib-cache.php'; // @codeCoverageIgnore
        if (!$s['cachedMeta'] && file_exists($path)) {
            // This file is a machine-generated array from var_export (Cache).
            // Since it's data-as-code and paths are dynamic, PSR-4 'use' is not applicable.
            $s['cachedMeta'] = require $path; // NOSONAR
        }
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
        $s            = &self::state();
        $s['debug']   = $path !== null;
        $s['logPath'] = $path;
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
        $s           = &self::state();
        $s['strict'] = $on;
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
        return new static(self::jsonParser($data, false));
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
     *     - true: Respect each layer's #[InputKeysTo] definition.
     *     - KeyCase::*: Force all keys (including nested) to the specified case.
     * @return array
     */
    final public function toArray(KeyCase | bool $keyCase = false): array
    {
        $types = self::state()['properties'][static::class]['types'];
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
     *     - true: Respect each layer's #[InputKeysTo] definition.
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
     * compares each property:
     *   - Type mismatch → false
     *   - Array → recursive arrayEquals() for nested ImmutableBase objects
     *   - ImmutableBase object → recursive equals()
     *   - Enum → compare by name (covers both UnitEnum and BackedEnum)
     *   - Scalar → strict identity (===)
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
        $a = get_object_vars($this);
        $b = get_object_vars($value);
        foreach ($a as $name => $av) {
            $bv = $b[$name] ?? null;
            if (
                !match (true) {
                    get_debug_type($av) !== get_debug_type($bv) => false,
                    \is_array($av)                              => self::arrayEquals($this, $av, $bv),
                    !\is_object($av)                            => $av === $bv,
                    default                                     => $av instanceof self ? $av->equals($bv) : $av->name === $bv->name,
                }
            ) {
                return false;
            }
        }

        return true;
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
        ImmutableBaseException::$depth++;
        try {
            $s              = &self::state();
            $values         = get_object_vars($this);
            $props          = $s['properties'][$static];
            $types          = $props['types'];
            $normalizedData = match (\is_string($data)) {
                true    => self::jsonParser($data, false),
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
                } elseif (isset($values[$path], $types[$path])) {
                    $values[$path] = self::resolveValue($types[$path], $value, true);
                }
            }
            if (isset($deepUpdates)) {
                self::resolveDeepUpdates($values, $deepUpdates, $types, $separator, $errorPath);
            }
            $s['refs'][$static] ??= new ReflectionClass($static);
            $instance = $s['refs'][$static]->newInstanceWithoutConstructor();
            $props['hydrator']($instance, $values);
            if (!$props['isDTO']) {
                $cache = $s['properties'];
                $class = $cache[$static];
                /** @var class-string<ValueObject> $static */
                $static::enforceValidationRules(
                    $instance,
                    $class['validateFromSelf'] ? $class['classTree'] : $class['classTreeReversed'],
                    $cache
                );
            }
            ImmutableBaseException::$depth--;

            return $instance;
        } catch (ImmutableBaseException $e) {
            ImmutableBaseException::$depth--;
            throw $e->prependPath($static, $errorPath ?? null);
        }
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

if (ImmutableBase::state()['cachedMeta'] === []) {
    ImmutableBase::loadCache();
}
