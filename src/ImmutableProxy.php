<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase;

use JsonSerializable;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidProxyException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidSerializeTargetException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidValueException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\UnserializableValueException;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

/**
 * Holds any object — mutable or not, from any package — so that the
 * ImmutableBase object owning it stays immutable.
 *
 * The proxy stores only a serialized snapshot of the object, never the object
 * itself, and every get() rebuilds a fresh deep copy. Mutating a copy, or the
 * original object after it was handed over, never reaches the owner.
 *
 * Declare a property either with a typed subclass (recommended: get()'s
 * native return type gives every IDE completion and names the held class):
 *
 *     final readonly class VendorConfigProxy extends ImmutableProxy
 *     {
 *         public function get(): VendorConfig { return parent::get(); }
 *     }
 *     public VendorConfigProxy $config;
 *
 * or with the base class and #[Proxy] naming the held class:
 *
 *     #[Proxy(VendorConfig::class)]
 *     public ImmutableProxy $config;   // @var ImmutableProxy<VendorConfig>
 *
 * In both cases ImmutableBase wraps a given instance automatically.
 *
 * @template T of object
 */
readonly class ImmutableProxy implements JsonSerializable
{
    /** @var class-string<T> The class of the held object. */
    public string $class;
    private string $snapshot;

    /** @param T $object */
    final protected function __construct(object $object)
    {
        try {
            $this->snapshot = serialize($object);
        } catch (Throwable $e) {
            throw new UnserializableValueException($object, $e->getMessage());
        }
        $this->class = $object::class;
    }

    /**
     * Wraps $object. A typed subclass only accepts instances of the class its
     * get() declares.
     *
     * @param T $object
     * @throws InvalidValueException When a typed subclass is given another class.
     * @throws UnserializableValueException When the object cannot be serialized (e.g. holds a Closure or a resource).
     * @return static
     */
    final public static function of(object $object): static
    {
        $target = static::declaredTarget();
        if ($target !== null && !$object instanceof $target) {
            throw new InvalidValueException($target, $object);
        }

        return new static($object);
    }

    /**
     * A fresh deep copy of the held object. Every call returns a new
     * instance; changing it does not change what the proxy holds.
     *
     * @return T
     */
    public function get(): object
    {
        return unserialize($this->snapshot);
    }

    /**
     * Whether both proxies are of the same class and hold structurally
     * identical objects (identical snapshots).
     */
    final public function equals(self $other): bool
    {
        return $other::class === static::class && $other->snapshot === $this->snapshot;
    }

    /**
     * The output form used by toArray() / toJson(): the held object's own
     * JsonSerializable form.
     *
     * @throws InvalidSerializeTargetException When the held object is not JsonSerializable.
     */
    final public function jsonSerialize(): mixed
    {
        $copy = $this->get();
        if (!$copy instanceof JsonSerializable) {
            throw new InvalidSerializeTargetException($this->class);
        }

        return $copy->jsonSerialize();
    }

    /**
     * The class a typed subclass holds, read from its get() return type;
     * null for ImmutableProxy itself, whose target comes from #[Proxy].
     *
     * @internal Used by the engine to validate declarations and input.
     * @throws InvalidProxyException When get() does not declare a single class (e.g. a union) as its return type.
     * @return class-string|null
     */
    final public static function declaredTarget(): ?string
    {
        static $targets = [];
        if (\array_key_exists(static::class, $targets)) {
            return $targets[static::class];
        }
        $method = new ReflectionMethod(static::class, 'get');
        if ($method->getDeclaringClass()->name === self::class) {
            return $targets[static::class] = null;
        }
        $type = $method->getReturnType();
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin() || $type->allowsNull()) {
            throw new InvalidProxyException(static::class . '::get() must declare a single class as its return type, ' . ($type === null ? 'none' : "'$type'") . ' given.');
        }

        return $targets[static::class] = $type->getName();
    }
}
