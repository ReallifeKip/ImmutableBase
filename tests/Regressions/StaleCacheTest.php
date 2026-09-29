<?php

declare (strict_types = 1);

namespace Tests\Regressions;

use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\CLI\Cacher;
use ReallifeKip\ImmutableBase\ImmutableBase;
use Tests\Regressions\Objects\PathItemDTO;
use Tests\Regressions\Objects\UnionHolderDTO;

/**
 * A cache entry that no longer matches its class (a property added, removed,
 * renamed or retyped after ib-cacher ran) must not be trusted: the class is
 * scanned by reflection instead, as if it had no cache entry.
 */
class StaleCacheTest extends TestCase
{
    private array $original;
    private string $cacheFile;

    protected function setUp(): void
    {
        $this->original  = ImmutableBase::state();
        $this->cacheFile = sys_get_temp_dir() . '/ib_stale_cache_' . uniqid() . '.php';
        Cacher::$silent  = true;

        $s              = &ImmutableBase::state();
        $s['cachePath'] = $this->cacheFile;
        $level          = ob_get_level();
        ob_start();
        try {
            (new Cacher())->scan(__DIR__ . '/Objects');
        } finally {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }
        $s['cachedMeta'] = require $this->cacheFile;
        $s['properties'] = [];
        $s['refs']       = [];
    }

    protected function tearDown(): void
    {
        @unlink($this->cacheFile);
        $s = &ImmutableBase::state();
        $s = $this->original;
    }

    /** @param callable(array): array $mutate Applied to the cached `types` of $class. */
    private static function staleEntry(string $class, callable $mutate): void
    {
        $s                                  = &ImmutableBase::state();
        $s['cachedMeta'][$class]['types'] = $mutate($s['cachedMeta'][$class]['types']);
    }

    public function testFreshCacheIsUsed(): void
    {
        $this->assertArrayHasKey(PathItemDTO::class, ImmutableBase::state()['cachedMeta']);
        $this->assertSame('a', PathItemDTO::fromArray(['sku' => 'a'])->sku);
    }

    public function testRenamedPropertyFallsBackToReflection(): void
    {
        self::staleEntry(PathItemDTO::class, static function (array $types) {
            $types['oldSku']                 = $types['sku'];
            $types['oldSku']['propertyName'] = 'oldSku';
            unset($types['sku']);

            return $types;
        });
        $this->assertSame('a', PathItemDTO::fromArray(['sku' => 'a'])->sku);
    }

    public function testAddedPropertyFallsBackToReflection(): void
    {
        self::staleEntry(PathItemDTO::class, static fn(array $types) => []);
        $this->assertSame('a', PathItemDTO::fromArray(['sku' => 'a'])->sku);
    }

    public function testRetypedPropertyFallsBackToReflection(): void
    {
        self::staleEntry(PathItemDTO::class, static function (array $types) {
            $types['sku']['typename'] = ['string' => 'int', 'array' => ['int']];

            return $types;
        });
        $this->assertSame('a', PathItemDTO::fromArray(['sku' => 'a'])->sku);
    }

    public function testNullabilityChangeFallsBackToReflection(): void
    {
        self::staleEntry(UnionHolderDTO::class, static function (array $types) {
            $types['u']['allowsNull'] = true;

            return $types;
        });
        $this->expectException(\ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\RequiredValueException::class);
        UnionHolderDTO::fromArray(['u' => null]);
    }
}
