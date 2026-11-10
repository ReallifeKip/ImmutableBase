<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Attributes;

/**
 * Names the class an ImmutableProxy property holds, when the property is
 * typed as the ImmutableProxy base class:
 *
 *     #[Proxy(VendorConfig::class)]
 *     public ImmutableProxy $config;
 *
 * Not needed for a typed subclass, whose get() return type already names the
 * class; if both are present they must agree.
 *
 * @example #[Proxy(\Symfony\Component\HttpFoundation\Request::class)]
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Proxy
{
    /**
     * @param class-string $class The class (or interface) of the held object.
     */
    private function __construct(string $class)
    {
        // Read through reflection only; prevents manual instantiation.
    }
}
