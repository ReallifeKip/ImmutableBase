<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase;

use ReallifeKip\ImmutableBase\Internal\ProxyAccess;

/**
 * Operations on an ImmutableProxy that cannot live on the proxy itself: its
 * public names all belong to the held object.
 */
final class Proxies
{
    /**
     * A real instance of the held object: a fresh deep copy, free to use and
     * to modify. Use it where the proxy cannot stand in for the object — a
     * parameter typed as the held class, instanceof, ArrayAccess /
     * iteration / count(), or many reads in a row (each forwarded read
     * rebuilds the object; one unwrap() rebuilds it once). Changing it does
     * not change the holder; to store a change, pass it to with().
     *
     * @template T of object
     * @param ImmutableProxy<T> $proxy
     * @return T
     */
    public static function unwrap(ImmutableProxy $proxy): object
    {
        return ProxyAccess::copy($proxy);
    }

    /**
     * The class of the object $proxy holds.
     *
     * @return class-string
     */
    public static function heldClass(ImmutableProxy $proxy): string
    {
        return ProxyAccess::heldClass($proxy);
    }
}
