<?php

declare(strict_types=1);

namespace ReallifeKip\ImmutableBase;

use Closure;
use ReflectionClass;
use ReflectionProperty;
use ReflectionNamedType;
use ReflectionUnionType;
use ReflectionAttribute;
use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\SingleValueObject;
use ReallifeKip\ImmutableBase\Exceptions\AttributeException;
use ReallifeKip\ImmutableBase\Exceptions\InvalidJsonException;
use ReallifeKip\ImmutableBase\Exceptions\InvalidTypeException;
use ReallifeKip\ImmutableBase\Exceptions\InheritanceException;
use ReallifeKip\ImmutableBase\Exceptions\ImmutableBaseException;
use ReallifeKip\ImmutableBase\Exceptions\InvalidArrayItemException;
use ReallifeKip\ImmutableBase\Exceptions\InvalidArrayValueException;
use ReallifeKip\ImmutableBase\Exceptions\InvalidArrayOfClassException;
use ReallifeKip\ImmutableBase\Exceptions\NonNullablePropertyException;
use ReallifeKip\ImmutableBase\Exceptions\InvalidPropertyVisibilityException;

$namespace = __NAMESPACE__;
$attrNamespace = "$namespace\\Attributes";

class_alias(
    "$attrNamespace\\DataTransferObject",
    "$namespace\\DataTransferObject",
);

class_alias(
    "$attrNamespace\\ValueObject",
    "$namespace\\ValueObject",
);

class_alias(
    "$attrNamespace\\Entity",
    "$namespace\\Entity",
);
class_alias(
    "$attrNamespace\\ArrayOf",
    "$namespace\\ArrayOf",
);

abstract class ImmutableBase
{
    /** @var array<string, int> */
    private static array $modes;
    private bool $initialized = false;
    private static bool $byNamedConstruct = false;
    /** @var array<string, ReflectionProperty[]> */
    private static array $classes = [];
    private ReflectionClass $ref;
    /** @var ReflectionClass[] $reflectionsCache */
    private static array $reflectionsCache = [];
    private static array $classBoundSetter = [];
    /**
     * Initializes an immutable object using the given data array.
     *
     * This constructor performs full reflection-based hydration and validation
     * of all declared properties according to their type definitions, attributes,
     * and visibility rules.
     *
     * Direct instantiation via `new` is discouraged and will be deprecated in v4.0.0.
     * Consumers should prefer {@see static::fromArray()} or {@see static::fromJson()},
     * which provide clearer intent and allow future optimization or alternate
     * construction strategies.
     *
     * @param array<string, mixed> $data The associative array used to populate the object's properties.
     *
     * @throws ImmutableBaseException When any internal validation or hydration error occurs.
     *
     * @deprecated Direct instantiation will be deprecated in v4.0.0.
     *             Use static::fromArray() or static::fromJson() instead.
     */
    public function __construct(array $data = [])
    {
        $this->constructInitialize();
        if (self::$byNamedConstruct === false) {
            trigger_error(
                sprintf(
                    'Direct instantiation of %s will be deprecated in v4.0.0. Use %s::fromArray() or %s::fromJson() instead.',
                    static::class,
                    static::class,
                    static::class
                ),
                E_USER_WARNING
            );
        }
        $this->walkProperties([$this, 'analyzeClass'], $data);
    }
    /**
     * Analyzes and initializes a single property during object construction.
     *
     * This method is invoked for each declared property via {@see walkProperties()}.
     * It determines the appropriate value to assign based on:
     * - Property existence in the input data
     * - Nullability and default values
     * - Declared type (including union and named types)
     * - Presence of the #[ArrayOf] attribute
     *
     * The resolved value is then assigned using {@see propertyInitialize()},
     * which safely handles readonly properties across inheritance boundaries.
     *
     * Any validation or type mismatch errors are wrapped with contextual
     * information (class and property name) before being rethrown.
     *
     * @param ReflectionProperty      $property The property being analyzed and initialized.
     * @param array<string, mixed>    $data     The input data array used for hydration.
     *
     * @throws ImmutableBaseException When validation fails or the value cannot be assigned.
     *
     * @return void
     */
    private function analyzeClass(ReflectionProperty $property, array $data)
    {
        try {
            $name               = $property->name;
            /** @var ReflectionNamedType|ReflectionUnionType $type */
            $type               = $property->getType();
            $exists             = array_key_exists($name, $data);
            $isNull             = !isset($data[$name]) || $data[$name] === null;
            $notExistsOrIsNull  = !$exists || $isNull;
            $nullable           = $type->allowsNull();
            $hasDefault         = $property->hasDefaultValue();
            $arg                = $this->isArrayOf($property);
            $this->propertyInitialize(
                $property,
                match(true) {
                    $notExistsOrIsNull => match(true) {
                        !$nullable  => throw new NonNullablePropertyException("value is required and must be $type."),
                        $nullable   => $hasDefault ? $property->getDefaultValue() : null,
                    },
                    $arg !== false   =>
                        match(true) {
                            $notExistsOrIsNull              => throw new InvalidArrayValueException("must be an array or array<{$arg}>."),
                            is_array($data[$name])   => $this->arrayOfInitilize($arg, $data[$name]),
                            default                         => throw new InvalidTypeException("must be an array."),
                        },
                    $exists         => $this->valueDecide($type, $data[$name]),
                }
            );
        } catch (ImmutableBaseException $e) {
            throw new $e(static::class." $name {$e->getMessage()}");
        }
    }
    /**
     * Determines whether a property is annotated with the #[ArrayOf] attribute and validates its target class.
     *
     * This method inspects the given property for an #[ArrayOf] attribute, supporting both the direct
     * and fully-qualified attribute namespaces. If the attribute is found, it verifies that:
     * - The attribute specifies a valid target class.
     * - The target class extends {@see ImmutableBase}.
     *
     * If the attribute definition is invalid or references a non-ImmutableBase subclass, a corresponding
     * {@see InvalidArrayOfClassException} is thrown.
     *
     * @param ReflectionProperty $property The property to inspect for the #[ArrayOf] attribute.
     *
     * @throws InvalidArrayOfClassException When the #[ArrayOf] attribute is misconfigured or targets an invalid class.
     *
     * @return class-string<ImmutableBase>|false The fully qualified class name referenced in the #[ArrayOf] attribute,
     * or false if the property is not annotated with #[ArrayOf].
     */
    private function isArrayOf(ReflectionProperty $property)
    {
        if (
            $arrayOf = $property->getAttributes(ArrayOf::class, ReflectionAttribute::IS_INSTANCEOF)
        ) {
            if ($arrayOf[0]->newInstance()->error) {
                throw new InvalidArrayOfClassException('needs to specify a target class in its #[ArrayOf] attribute.');
            }
            $arg = $arrayOf[0]->getArguments()[0];
            if (!is_subclass_of($arg, self::class)) {
                throw new InvalidArrayOfClassException('must reference a class that extends ImmutableBase in its #[ArrayOf] attribute.');
            }
        }
        return $arg ?? false;
    }
    /**
     * Initializes an array of immutable objects based on the specified target class.
     *
     * This method constructs a collection of {@see ImmutableBase} instances from a given input array.
     * Each element of the provided array is individually validated and transformed according to the following rules:
     * - If the element is an associative array, it is converted into a new instance of the target class via {@see ImmutableBase::fromArray()}.
     * - If the element is a JSON string, it is parsed and then converted into a new instance via {@see ImmutableBase::fromArray()}.
     * - If the element is already an instance of the target class, it is preserved as-is.
     * - Otherwise, an {@see InvalidArrayItemException} is thrown to enforce strict type consistency.
     *
     * This ensures that arrays annotated with #[ArrayOf(SomeClass::class)] contain only
     * valid, type-consistent immutable objects — whether provided as arrays, JSON strings, or pre-initialized instances.
     *
     * @param class-string<ImmutableBase> $arg   The fully qualified class name of the target immutable object type.
     * @param array<mixed>                $value The raw input array to be validated and transformed.
     *
     * @throws InvalidArrayItemException When any element of the array cannot be converted into a valid instance of the target class.
     *
     * @return array<int, ImmutableBase> An array of initialized immutable objects of the specified type.
     */
    private function arrayOfInitilize(string $arg, mixed $value)
    {
        return array_map(function ($item) use ($arg) {
            return match(true) {
                $item instanceof $arg      => $item,
                is_array($item)     => $arg::fromArray($item),
                is_string($item)    => $arg::fromArray($this->jsonParser($item)),
                default => throw new InvalidArrayItemException("each element in the array must be either an instance of {$arg}, an associative array, or a valid JSON string representing one.")
            };
        }, $value);
    }
    /**
     * Creates a new immutable instance from a PHP array.
     *
     * This is the preferred way to instantiate ImmutableBase objects starting from v4.0.0.
     * Performs inheritance validation before construction.
     *
     * @param array $array The associative array used to populate the object's properties.
     *
     * @throws InheritanceException When the subclass incorrectly extends ImmutableBase.
     *
     * @return static A new instance of the immutable object.
     */
    final public static function fromArray(array $array)
    {
        self::extendsValidate();
        self::$byNamedConstruct = true;
        return new static($array);
    }
    /**
     * Creates a new immutable instance from a JSON string.
     *
     * The JSON must represent an associative structure compatible with the target object's
     * property definitions. This method automatically decodes the JSON and passes the resulting
     * array to the constructor.
     *
     * @param string $data The JSON string representing the object's structure.
     *
     * @throws InvalidJsonException   When the provided JSON string is invalid or cannot be decoded.
     * @throws InheritanceException   When the subclass incorrectly extends ImmutableBase.
     *
     * @return static A new instance of the immutable object.
     */
    final public static function fromJson(string $data)
    {
        self::extendsValidate();
        self::$byNamedConstruct = true;
        return new static(
            self::jsonParser($data, false)
        );
    }
    /**
     * Decodes a JSON string into an associative array with optional fallback behavior.
     *
     * This method centralizes JSON parsing logic for object hydration within {@see ImmutableBase}.
     * It decodes the provided JSON string and returns its associative array representation.
     * If decoding fails, behavior depends on the `$returnInputOnException` flag:
     * - When true, the original input is returned unchanged.
     * - When false, an {@see InvalidJsonException} is thrown.
     *
     * This ensures consistent JSON handling across `fromJson()` and `with()` operations.
     *
     * @param string $data The JSON string to decode.
     * @param bool   $returnInputOnException Whether to return the input instead of throwing on failure.
     *
     * @throws InvalidJsonException When decoding fails and `$returnInputOnException` is false.
     *
     * @return array|string|null The decoded associative array, or the input when returning on exception.
     */

    private static function jsonParser(string $data, bool $returnInputOnException = true)
    {
        $data = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            if ($returnInputOnException) {
                return $data;
            } else {
                throw new InvalidJsonException('Invalid JSON string.');
            }
        }
        return $data;
    }
    /**
     * Validates the subclass inheritance hierarchy for {@see ImmutableBase}.
     *
     * This method ensures that a subclass correctly extends one of the supported immutable object types:
     * - {@see Objects\DataTransferObject}
     * - {@see Objects\ValueObject}
     * - {@see Objects\Entity}
     *
     * If a class directly extends {@see ImmutableBase}, a deprecation warning is triggered instead of an exception.
     * This behavior prepares for stricter enforcement in version 4.0.0 and beyond.
     *
     * @throws InheritanceException When invalid inheritance is detected (future enforcement).
     *
     * @return void
     */

    private static function extendsValidate()
    {
        $namespace = __NAMESPACE__;
        if ($parent = get_parent_class(static::class)) {
            $parent = basename(str_replace('\\', '/', $parent));
            if (
                !(
                    is_subclass_of(static::class, "$namespace\\Objects\\DataTransferObject") ||
                    is_subclass_of(static::class, "$namespace\\Objects\\ValueObject")
                )
            ) {
                trigger_error(
                    sprintf(
                        "%s directly extends ImmutableBase, which will be deprecated in v4.0.0. " .
                        "Please extend from 'Objects\\DataTransferObject', 'Objects\\ValueObject', or 'Objects\\Entity' instead.",
                        static::class
                    ),
                    E_USER_WARNING
                );
            }
        }
    }
    /**
     * Initializes internal reflection and determines the operational mode
     * based on the subclass inheritance hierarchy.
     *
     * Starting from v4.0.0, ImmutableBase no longer uses class-level attributes
     * (#[DataTransferObject], #[ValueObject], #[Entity]) to determine mode.
     * Instead, mode detection relies solely on inheritance:
     * - Mode 1 → subclasses of {@see Objects\DataTransferObject}
     * - Mode 2 → subclasses of {@see Objects\ValueObject}
     * - Mode 3 → subclasses of {@see Objects\Entity}
     *
     * @throws AttributeException When the subclass does not extend any supported immutable base type.
     */
    final protected function constructInitialize()
    {
        if ($this->initialized) {
            return;
        }
        $namespace = __NAMESPACE__;
        $attrNamespace = "$namespace\\Attributes";
        $this->ref ??= self::getReflection($this);
        foreach ($this->ref->getAttributes() as $attr) {
            $set[$attr->name ?? $attr->getName()] = true;
        }
        self::$modes[static::class] ??= match (true) {
            is_subclass_of(static::class, "$namespace\\Objects\\SingleValueObject")       => 4,
            $this instanceof SingleValueObject                     => 4,
            isset($set["$namespace\\DataTransferObject"]) || isset($set["$attrNamespace\\DataTransferObject"])    => 1,
            is_subclass_of(static::class, "$namespace\\Objects\\DataTransferObject")      => 1,
            isset($set["$namespace\\ValueObject"]) || isset($set["$attrNamespace\\ValueObject"])                  => 2,
            is_subclass_of(static::class, "$namespace\\Objects\\ValueObject")             => 2,
            isset($set["$namespace\\Entity"]) || isset($set["$attrNamespace\\Entity"])                            => 3,
            is_subclass_of(static::class, "$namespace\\Objects\\Entity")                  => 3,
            default => throw new AttributeException('ImmutableBase subclasses must be annotated with either #[DataTransferObject] or #[ValueObject] or #[Entity].'),
        };
    }
    /**
     * Initializes a property with the given value, handling readonly visibility across class boundaries.
     *
     * If the property is declared as readonly in a parent class and has not yet been initialized,
     * this method uses a bound closure to set its value. Otherwise, it assigns the value directly.
     *
     * This mechanism ensures compatibility with inheritance while respecting PHP's readonly semantics.
     *
     * @param ReflectionProperty $property The property to initialize.
     * @param mixed $value                  The value to assign to the property.
     *
     * @return void
     */
    private function propertyInitialize(ReflectionProperty $property, mixed $value): void
    {
        $declaring = $property->class;
        if ($declaring !== $this::class && $property->isReadOnly()) {
            if ($property->isInitialized($this)) {
                return;
            }
            (self::$classBoundSetter[$declaring] ??= Closure::bind(
                fn (object $obj, string $prop, mixed $val) => $obj->$prop = $val,
                null,
                $declaring
            ))($this, $property->name, $value);
        } else {
            $property->setValue($this, $value);
        }
    }
    // TODO: 好像可以更優化
    private static function getReflection(object $obj): ReflectionClass
    {
        return self::$reflectionsCache[static::class] ??= new ReflectionClass($obj);
    }
    /**
     * Iterates through all declared properties in the current class hierarchy
     * (excluding {@see ImmutableBase}) and applies a callback to each.
     *
     * On first invocation per concrete class, this method:
     * - Collects all properties from the inheritance chain (child → parent)
     * - Caches the resulting property list for reuse
     * - Validates each property's visibility and readonly constraints according
     *   to the resolved object mode
     *
     * Subsequent invocations reuse the cached property list and skip validation,
     * ensuring minimal overhead during repeated hydration or serialization passes.
     *
     * Visibility rules enforced by mode:
     * - DataTransferObject (mode 1): properties must be declared `public readonly`
     * - ValueObject / Entity (other modes): properties must not be `public`
     *
     * The provided callback is executed for each property after validation,
     * receiving the {@see ReflectionProperty} instance and the associated data array.
     *
     * @param callable               $callback The function to execute for each property.
     *                                         Signature: fn(ReflectionProperty $property, array &$data): void
     * @param array<string, mixed>   $data     The data array passed through the iteration process.
     *
     * @throws InvalidPropertyVisibilityException
     *         When a property's visibility or readonly status violates the rules
     *         for the current object mode.
     *
     * @return void
     */
    private function walkProperties(callable $callback, array &$data): void
    {
        $this->constructInitialize();
        $new = false;
        $static = static::class;
        if (isset(self::$classes[$static]) === false) {
            $new = true;
            $this->buildPropertyInheritanceChain($static);
        }
        foreach (self::$classes[$static] as $property) {
            if ($new) {
                $propertyName   = $property->name;
                $className      = $property->class;
                $isPublic       = $property->isPublic();
                $isReadonly     = $property->isReadOnly();
                $mode           = self::$modes[$static];
                if ($mode === 1 && !$isPublic) {
                    throw new InvalidPropertyVisibilityException("$className $propertyName must be declared public and readonly.");
                }
                if (!$isReadonly) {
                    trigger_error("$className $propertyName is not readonly. This will be required in version 4.0.0 (should be declared private or protected and readonly)");
                }
            }
            $callback($property, $data);
        }
    }
    /**
     * Builds and caches the ordered property inheritance chain for a concrete class.
     *
     * This method traverses the reflection class hierarchy starting from the
     * concrete class and walking upward through parent classes, stopping before
     * {@see ImmutableBase}. All declared properties are collected in parent-to-child
     * order to ensure deterministic processing during hydration and serialization.
     *
     * The resulting {@see ReflectionProperty} list is cached per concrete class
     * to avoid repeated reflection and traversal costs on subsequent access.
     *
     * @param class-string $static The concrete class name used as the cache key.
     *
     * @return void
     */
    private function buildPropertyInheritanceChain($static)
    {
        $properties = [];
        for ($c = $this->ref; $c && $c->name !== self::class; $c = $c->getParentClass()) {
            foreach ($c->getProperties() as $property) {
                if (!$property->isStatic()) {
                    $properties[] = $property;
                }
            }
        }
        self::$classes[$static] = $properties;
    }

    /**
     * Update and return a new instance.
     * @param mixed $data
     * @throws ImmutableBaseException When any internal error occurs within this package during execution.
     * @return static
     */
    final public function with(mixed $data): static
    {
        $data = is_string($data) ? self::jsonParser($data, false) : $data;
        $ref = self::getReflection($this);
        $new = [];
        try {
            foreach ($ref->getProperties() as $property) {
                $name = $property->name;
                [$exists, $value] = $this->extractValue($data, $name);
                $new[$name] = $exists
                    ? $this->resolveValue($property, $value)
                    : $property->getValue($this);
            }
        } catch (ImmutableBaseException $e) {
            throw new $e(static::class . " $name {$e->getMessage()}");
        }

        return static::fromArray($new);
    }
    /**
     * Extract a value by property name from array|object input.
     *
     * @param mixed  $data Source data (array|object expected, others ignored)
     * @param string $name Property name to lookup
     * @return array{0: bool, 1: mixed} [exists, value]
     */
    private function extractValue(mixed $data, string $name): array
    {
        return match (true) {
            is_array($data)  && array_key_exists($name, $data)   => [true, $data[$name]],
            is_object($data) && property_exists($data, $name)    => [true, $data->$name],
            default => [false, null],
        };
    }
    /**
     * Resolve the final value for a property according to its type and rules.
     *
     * Handles:
     * - non-nullable validation
     * - nested immutable objects
     * - array-of initialization
     * - JSON string auto-parsing
     * - final type coercion via valueDecide()
     *
     * @param ReflectionProperty $property Target property metadata
     * @param mixed              $v        Incoming value
     * @return mixed
     * @throws ImmutableBaseException
     */
    private function resolveValue(ReflectionProperty $property, mixed $v): mixed
    {
        $type    = $property->getType();
        $current = $property->getValue($this);
        if ($v === null) {
            $type->allowsNull() || throw new NonNullablePropertyException("value is required and must be $type.");
            return null;
        }
        return match (true) {
            is_array($v) && is_object($current) && is_subclass_of($current, self::class)
                => $current->with($v),
            ($arg = $this->isArrayOf($property)) && is_array($v)
                => $this->arrayOfInitilize($arg, $v),
            is_string($v) && is_array($parsed = self::jsonParser($v, true))
                => $this->valueDecide($type, $parsed),
            default
            => $this->valueDecide($type, $v),
        };
    }

    /**
     * Converts the current immutable object into an associative array.
     *
     * Each property is traversed via {@see walkProperties()} and converted to an array value.
     * Nested ImmutableBase objects or collections of such objects are recursively transformed
     * using {@see toArrayOrValue()}.
     *
     * This method is typically used for serialization or debugging purposes and guarantees
     * a consistent, array-based representation of the immutable structure.
     *
     * @return array<string, mixed> An associative array representing all properties of the object,
     * with nested objects recursively expanded.
     */
    final public function toArray(): array
    {
        $properties = [];
        $this->walkProperties([$this, 'analyzeClassForToArray'], $properties);
        return $properties;
    }
    /**
     * Collects a property's value into the resulting array representation.
     *
     * This method is used as a callback by {@see walkProperties()} during
     * {@see toArray()} execution. For each property:
     * - If the value is an array, each element is normalized via {@see toArrayOrValue()}
     * - Otherwise, the value is normalized directly
     *
     * Objects implementing a `toArray()` method are recursively converted,
     * while all other values are returned as-is.
     *
     * @param ReflectionProperty $property   The property being converted.
     * @param array<string, mixed> &$properties The accumulating array representation.
     *
     * @return void
     */
    private function analyzeClassForToArray(ReflectionProperty $property, array &$properties)
    {
        $properties[$property->name] = is_array($value = $property->getValue($this)) ?
            array_map([$this, 'toArrayOrValue'], $value) :
            $this->toArrayOrValue($value);
    }
    final public function toJson()
    {
        return json_encode($this->toArray());
    }
    /**
     * Converts an object to an array if possible, otherwise returns the original value.
     *
     * This method checks whether the given value is an object implementing a `toArray()` method.
     * If so, it invokes that method and returns the resulting array. For all other values, the
     * original input is returned unchanged.
     *
     * This utility provides a lightweight normalization step for mixed-type data, ensuring that
     * objects capable of array conversion are consistently represented as arrays.
     *
     * @param mixed $value The value to be normalized or returned as-is.
     *
     * @return mixed The array representation of the object, or the original value if no conversion applies.
     */
    private function toArrayOrValue(mixed $value)
    {
        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }
        return $value;
    }
    /**
     * Determines how to handle a property's value based on its declared type.
     *
     * This method performs runtime type validation and, when necessary, delegates processing to
     * {@see unionTypeDecide()} or {@see namedTypeDecide()} depending on whether the type is
     * a union type or a class/interface type.
     *
     * Built-in scalar types are validated using {@see builtinTypeValidate()}, while nullable
     * types are handled via {@see validNullValue()}.
     *
     * @param ReflectionNamedType|ReflectionUnionType $type  The declared type of the property.
     * @param mixed                                   $value The value being assigned or validated.
     *
     * @throws InvalidTypeException When the provided value does not match the declared type.
     *
     * @return mixed The validated or transformed value.
     */
    private function valueDecide(ReflectionNamedType|ReflectionUnionType $type, mixed $value): mixed
    {
        if ($type instanceof ReflectionUnionType) {
            return $this->unionTypeDecide($type, $value);
        } else {
            if (!$type->isBuiltin()) {
                return $this->namedTypeDecide($type, $value);
            } elseif (
                $this->builtinTypeValidate($value, $type->getName()) === false &&
                !$this->validNullValue($type, $value)
            ) {
                throw new InvalidTypeException(
                    sprintf(
                        "type mismatch: expected %s, got %s.",
                        $type->getName(),
                        is_object($value) ? $value::class : gettype($value)
                    )
                );
            }
        }
        return $value;
    }
    /**
     * Resolves and validates a property's value when its declared type is a union.
     *
     * This method iterates through all possible types within a {@see ReflectionUnionType}
     * and attempts to validate the given value against each. The first compatible type
     * is accepted and its processed value is returned.
     *
     * If none of the union members accept the value, an {@see InvalidTypeException} is thrown.
     * Additionally, if the union does not include `array` but the given value *is* an array,
     * the method explicitly rejects it to prevent unintended hydration.
     *
     * @param ReflectionUnionType $type  The union type declared for the property.
     * @param mixed               $value The value being validated or assigned.
     *
     * @throws InvalidTypeException When no matching type in the union can accept the provided value.
     *
     * @return mixed The validated or transformed value that matched one of the union types.
     */
    private function unionTypeDecide(ReflectionUnionType $type, mixed $value)
    {
        $types = $type->getTypes();
        $names = array_map(fn ($e) => $e->getName(), $types);
        if (!in_array('array', $names, true) && is_array($value)) {
            throw new InvalidTypeException('type is union and does not include array; an instantiated object is required.');
        }
        foreach ($types as $t) {
            try {
                return $this->valueDecide($t, $value);
            } catch (InvalidTypeException) {
                continue;
            }
        }
        $expected = implode('|', $names);
        $actual = is_object($value) ? $value::class : gettype($value);
        throw new InvalidTypeException("expected types: $expected, got $actual.");
    }
    /**
     * Resolves and validates a property's value when its declared type is a named (class or enum) type.
     *
     * This method determines how to construct or validate a value based on its declared class:
     * - If the value is an array and the target type extends {@see ImmutableBase}, a new instance is constructed.
     * - If the value is already an object, it is returned as-is.
     * - If the type allows null and the value is null, null is returned.
     * - If the type represents an enum, the method first attempts to resolve it by matching the case name
     *   via constant lookup (e.g. `MyEnum::CASE`), and if that fails and the enum implements {@see BackedEnum},
     *   it will attempt resolution via `::tryFrom($value)`.
     *
     * Any mismatch between the provided value and the expected class or enum type will result
     * in an {@see InvalidTypeException}.
     *
     * @param ReflectionNamedType $type  The declared named type of the property.
     * @param mixed               $value The value to validate or transform.
     *
     * @throws InvalidTypeException When the provided value does not match the declared class or enum type.
     *
     * @return mixed The validated or constructed value appropriate for the declared type.
     */
    private function namedTypeDecide(ReflectionNamedType $type, mixed $value)
    {
        $class = $type->getName();
        return match(true) {
            is_array($value) && is_subclass_of($class, self::class) => $class::fromArray($value),
            is_object($value) => $value,
            $this->validNullValue($type, $value) => null,
            is_string($value) && enum_exists($class) => $this->analyzeEnum($class, $value),
            is_subclass_of($class, SingleValueObject::class) => $class::from($value),
            default => throw new InvalidTypeException(
                "expected types: $class, got " .
                (is_object($value) ? $value::class : gettype($value)) . '.'
            )
        };
    }
    /**
     * Resolves and validates a value against a declared enum type.
     *
     * This method attempts to map the provided string value to a valid enum case
     * using the following resolution strategy:
     * - First, it checks for a matching enum case name via constant lookup
     *   (e.g. `MyEnum::CASE_NAME`).
     * - If no matching case name is found and the enum implements {@see BackedEnum},
     *   it attempts to resolve the value using {@see BackedEnum::tryFrom()}.
     *
     * If neither strategy results in a valid enum case, an {@see InvalidTypeException}
     * is thrown to indicate that the value does not correspond to any case
     * of the declared enum type.
     *
     * @param string $class The fully qualified enum class name.
     * @param string $value The raw value to resolve into an enum case.
     *
     * @throws InvalidTypeException When the value cannot be resolved to any enum case.
     *
     * @return object The resolved enum case instance.
     */
    private function analyzeEnum(string $class, string $value)
    {
        if (defined($case = "$class::$value")) {
            return constant($case);
        }
        if (is_subclass_of($class, \BackedEnum::class) && $case = $class::tryFrom($value)) {
            return $case;
        }
        throw new InvalidTypeException("is $class and does not include '$value'.");
    }
    /**
     * Determines whether the given value is a valid null according to the property's type definition.
     *
     * @param ReflectionNamedType $type  The property's declared type.
     * @param mixed               $value The value to check.
     *
     * @return bool True if the property type allows null and the value is null, false otherwise.
     */
    private function validNullValue(ReflectionNamedType $type, $value)
    {
        return $type->allowsNull() && $value === null;
    }
    /**
     * Validates whether a given value matches the specified built-in PHP type.
     *
     * @param mixed  $value The value to validate.
     * @param string $type  The name of the built-in type (e.g., "int", "string", "array").
     *
     * @return bool True if the value matches the built-in type, false otherwise.
     */
    private function builtinTypeValidate(mixed $value, string $type): bool
    {
        return match ($type) {
            'int'               => is_int($value),
            'float'             => is_float($value),
            'string'            => is_string($value),
            'bool'              => is_bool($value),
            'array'             => is_array($value),
            'object'            => is_object($value),
            default             => false,
        };
    }
}
