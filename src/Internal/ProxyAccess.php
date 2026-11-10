<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Internal;

use Closure;
use ReallifeKip\ImmutableBase\Attributes\Proxy;
use ReallifeKip\ImmutableBase\BasicTrait;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidProxyException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidValueException;
use ReallifeKip\ImmutableBase\ImmutableProxy;
use ReflectionClass;

/**
 * The engine's way into ImmutableProxy. The proxy keeps all its own members
 * private so that every public name forwards to the held object; the engine
 * reaches them through closures bound to the proxy's scope.
 *
 * @internal Not part of the public API; may change without notice.
 */
final class ProxyAccess
{
    use BasicTrait;

    /**
     * Wraps $object in a new $proxyClass. A typed subclass only accepts
     * instances of the class its #[Proxy] names.
     *
     * @param class-string<ImmutableProxy> $proxyClass
     * @throws InvalidValueException When $object is not of the declared class.
     */
    public static function wrap(string $proxyClass, object $object): ImmutableProxy
    {
        $target = self::declaredTarget($proxyClass);
        if ($target !== null && !$object instanceof $target) {
            throw new InvalidValueException($target, $object);
        }
        static $wrap = null;
        $wrap ??= Closure::bind(static fn(string $class, object $o) => new $class($o), null, ImmutableProxy::class);

        return $wrap($proxyClass, $object);
    }

    /** A fresh deep copy of the held object. */
    public static function copy(ImmutableProxy $proxy): object
    {
        static $copy = null;
        $copy ??= Closure::bind(static fn(ImmutableProxy $p) => $p->copy(), null, ImmutableProxy::class);

        return $copy($proxy);
    }

    /** @return class-string The held object's class. */
    public static function heldClass(ImmutableProxy $proxy): string
    {
        static $held = null;
        $held ??= Closure::bind(static fn(ImmutableProxy $p) => $p->heldClass, null, ImmutableProxy::class);

        return $held($proxy);
    }

    /** Same proxy class and structurally identical held objects (identical snapshots). */
    public static function equals(ImmutableProxy $a, ImmutableProxy $b): bool
    {
        static $equals = null;
        $equals ??= Closure::bind(static fn(ImmutableProxy $x, ImmutableProxy $y) => $x->sameAs($y), null, ImmutableProxy::class);

        return $equals($a, $b);
    }

    /**
     * The class a typed subclass holds, from its class-level #[Proxy]; null
     * for ImmutableProxy itself and for subclasses without one (their
     * properties must then carry #[Proxy]).
     *
     * @param class-string<ImmutableProxy> $proxyClass
     * @throws InvalidProxyException When the #[Proxy] argument is not an existing class or interface.
     * @return class-string|null
     */
    public static function declaredTarget(string $proxyClass): ?string
    {
        static $targets = [];
        if (\array_key_exists($proxyClass, $targets)) {
            return $targets[$proxyClass];
        }
        $ref = new ReflectionClass($proxyClass);

        return $targets[$proxyClass] = $ref->getAttributes(Proxy::class) === []
        ? null
        : self::validTarget(self::getAttributeArgument($ref, Proxy::class), "$proxyClass::class");
    }

    /**
     * Validates a #[Proxy] argument: an existing class or interface name.
     *
     * @param string $where Human-readable declaration location for the message.
     * @throws InvalidProxyException
     * @return class-string
     */
    public static function validTarget(mixed $class, string $where): string
    {
        if (!\is_string($class) || !(class_exists($class) || interface_exists($class))) {
            throw new InvalidProxyException("#[Proxy] on $where names a class that does not exist: " . var_export($class, true) . '.');
        }

        return ltrim($class, '\\');
    }
}
