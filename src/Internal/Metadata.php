<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Internal;

use Composer\Autoload\ClassLoader;
use ReallifeKip\ImmutableBase\Types;
use ReflectionClass;

/**
 * Process-wide registry of compiled class metadata and engine configuration.
 *
 * Every engine component reads and writes through the narrow methods below;
 * none holds a reference into the storage. state() remains only as the
 * backing for the deprecated ImmutableBase::state() handle.
 *
 * @internal Not part of the public API; may change without notice.
 *
 * @phpstan-import-type Property from Types
 * @phpstan-import-type Caches from Types
 * @phpstan-import-type State from Types
 */
final class Metadata
{
    /** @var State */
    private static array $state = [
        'debug'      => false,
        'logPath'    => null,
        'cachePath'  => null,
        'strict'     => false,
        'refs'       => [],
        'properties' => [],
        'cachedMeta' => [],
    ];

    /**
     * By-reference handle to the whole storage. Exists only so the
     * deprecated ImmutableBase::state() keeps working; engine code must not
     * use it.
     *
     * @return State
     */
    public static function &state(): array
    {
        return self::$state;
    }

    /** Whether metadata for $class has been compiled in this process. */
    public static function has(string $class): bool
    {
        return isset(self::$state['properties'][$class]);
    }

    /**
     * Compiled metadata for $class, or null when not compiled yet.
     *
     * @return Property|null
     */
    public static function get(string $class): ?array
    {
        return self::$state['properties'][$class] ?? null;
    }

    /**
     * A copy of every class compiled so far, keyed by FQCN.
     *
     * @return Caches
     */
    public static function all(): array
    {
        return self::$state['properties'];
    }

    /** @param Property $metadata */
    public static function put(string $class, array $metadata): void
    {
        self::$state['properties'][$class] = $metadata;
    }

    /**
     * The shared ReflectionClass for $class, created on first use.
     *
     * @param class-string $class
     */
    public static function reflection(string $class): ReflectionClass
    {
        return self::$state['refs'][$class] ??= new ReflectionClass($class);
    }

    /**
     * The pre-generated cache entry for $class, if the cache holds one.
     *
     * @return Property|null
     */
    public static function cached(string $class): ?array
    {
        return self::$state['cachedMeta'][$class] ?? null;
    }

    /**
     * Location of ib-cache.php: the configured path, else the project root
     * (the directory holding vendor/).
     */
    public static function cachePath(): string
    {
        return self::$state['cachePath'] ??= \dirname(\dirname((new ReflectionClass(ClassLoader::class))->getFileName()), 2) . '/ib-cache.php'; // @codeCoverageIgnore
    }

    /**
     * Loads ib-cache.php once, unless cached metadata is already present.
     * The file is machine-generated data-as-code (var_export), so it is
     * required rather than parsed.
     */
    public static function loadCache(): void
    {
        $path = self::cachePath();
        if (!self::$state['cachedMeta'] && file_exists($path)) {
            self::$state['cachedMeta'] = require $path; // NOSONAR
        }
    }

    public static function isStrict(): bool
    {
        return self::$state['strict'];
    }

    public static function setStrict(bool $on): void
    {
        self::$state['strict'] = $on;
    }

    /** The debug log directory, or null when debug logging is off. */
    public static function debugPath(): ?string
    {
        return self::$state['debug'] ? self::$state['logPath'] : null;
    }

    public static function setDebugPath(?string $path): void
    {
        self::$state['debug']   = $path !== null;
        self::$state['logPath'] = $path;
    }
}
