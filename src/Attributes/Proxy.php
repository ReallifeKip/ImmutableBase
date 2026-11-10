<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Attributes;

/**
 * Names the class an ImmutableProxy holds. On a typed subclass (recommended,
 * paired with @mixin for the IDE):
 *
 *     /** @mixin VendorConfig *\/
 *     #[Proxy(VendorConfig::class)]
 *     final readonly class VendorConfigProxy extends ImmutableProxy {}
 *
 * or on a property typed as the ImmutableProxy base class:
 *
 *     #[Proxy(VendorConfig::class)]
 *     public ImmutableProxy $config;
 *
 * When both the subclass and the property carry one, they must agree.
 *
 * @example #[Proxy(\Symfony\Component\HttpFoundation\Request::class)]
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY)]
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
