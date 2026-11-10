<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase;

use JsonSerializable;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidSerializeTargetException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\UnserializableValueException;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\ImmutableViolationException;
use Throwable;

/**
 * Holds any object — mutable or not, from any package — so that the
 * ImmutableBase object owning it stays immutable, while reading as if it
 * were the object itself.
 *
 * The proxy stores only a serialized snapshot, never the object. Reads are
 * forwarded to a fresh deep copy rebuilt from that snapshot, so neither the
 * original object (mutated after it was handed over) nor any copy can reach
 * the owner:
 *
 *     $client->config->timeout;        // property read
 *     $client->config->describe();     // method call
 *     (string) $client->config;        // __toString
 *
 * Writes are refused with ImmutableViolationException: assigning or
 * unsetting a property, and calling a method that changes the object
 * (detected by comparing the copy with the snapshot after the call). To
 * change the held object, replace it with with().
 *
 * The proxy's own members are all private (jsonSerialize() aside, which
 * the interface requires and which forwards anyway), so every public name
 * belongs to the held object: $proxy->get('key') calls the held object's
 * get(). Proxies::unwrap() returns a real instance when one is needed, e.g.
 * for a parameter typed as the held class.
 *
 * Declare the held class twice: #[Proxy] for ImmutableBase (read at
 * runtime) and a doc comment for the IDE (attributes carry no type
 * information for it, and ImmutableBase never reads doc comments):
 *
 *     /** @var ImmutableProxy&VendorConfig *\/
 *     #[Proxy(VendorConfig::class)]
 *     public ImmutableProxy $config;
 *
 * The intersection gives completion even in the free tier of Intelephense
 * (where @mixin is a premium feature). Tools that resolve @mixin on generic
 * templates (PHPStan, Intelephense premium) also accept
 * `@var ImmutableProxy<VendorConfig>`. A typed subclass carrying the class-level #[Proxy] lets several
 * properties share one declaration. ImmutableBase wraps a given instance
 * automatically.
 *
 * @template T of object
 * @mixin T
 */
readonly class ImmutableProxy implements JsonSerializable
{
    /** @var class-string<T> */
    private string $heldClass;
    private string $snapshot;

    /**
     * Engine-only: ImmutableBase wraps values through Internal\ProxyAccess.
     *
     * @param T $object
     * @throws UnserializableValueException When the object cannot be serialized (e.g. holds a Closure or a resource).
     */
    private function __construct(object $object)
    {
        try {
            $this->snapshot = serialize($object);
        } catch (Throwable $e) {
            throw new UnserializableValueException($object, $e->getMessage());
        }
        $this->heldClass = $object::class;
    }

    /** @return T A fresh deep copy of the held object. */
    private function copy(): object
    {
        return unserialize($this->snapshot);
    }

    /** Same proxy class and structurally identical held objects. */
    private function sameAs(self $other): bool
    {
        return $other::class === static::class && $other->snapshot === $this->snapshot;
    }

    /**
     * Runs $operation against a fresh copy and returns its result; refuses
     * the operation when it changed the copy, since that change could never
     * reach the held object.
     *
     * @throws ImmutableViolationException
     */
    private function forward(string $operation, \Closure $call): mixed
    {
        $copy   = $this->copy();
        $result = $call($copy);
        if (serialize($copy) !== $this->snapshot) {
            throw new ImmutableViolationException("$operation would modify the held {$this->heldClass}, which is immutable; replace it with with() instead.");
        }

        return $result;
    }

    public function __get(string $name): mixed
    {
        return $this->copy()->$name;
    }

    public function __isset(string $name): bool
    {
        return isset($this->copy()->$name);
    }

    /** @throws ImmutableViolationException */
    public function __set(string $name, mixed $value): never
    {
        throw new ImmutableViolationException("Cannot assign \$$name: the held {$this->heldClass} is immutable; replace it with with() instead.");
    }

    /** @throws ImmutableViolationException */
    public function __unset(string $name): never
    {
        throw new ImmutableViolationException("Cannot unset \$$name: the held {$this->heldClass} is immutable; replace it with with() instead.");
    }

    /** @throws ImmutableViolationException */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->forward("$method()", static fn(object $copy) => $copy->$method(...$arguments));
    }

    /** @throws ImmutableViolationException */
    public function __invoke(mixed ...$arguments): mixed
    {
        return $this->forward('Invoking it', static fn(object $copy) => $copy(...$arguments));
    }

    public function __toString(): string
    {
        return (string) $this->copy();
    }

    /**
     * The output form used by toArray() / toJson(): the held object's own
     * JsonSerializable form.
     *
     * @throws InvalidSerializeTargetException When the held object is not JsonSerializable.
     */
    final public function jsonSerialize(): mixed
    {
        $copy = $this->copy();
        if (!$copy instanceof JsonSerializable) {
            throw new InvalidSerializeTargetException($this->heldClass);
        }

        return $copy->jsonSerialize();
    }
}
