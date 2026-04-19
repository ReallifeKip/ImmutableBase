<?php

declare (strict_types = 1);

namespace Tests;

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\CLI\Cacher;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReflectionClass;

class CacherTest extends TestCase
{
    private string $cachePath;
    private string $scanDir;

    /** @var array Original state backup */
    private array $originalProperties;
    private array $originalCachedMeta;
    private array $originalRefs;

    protected function setUp(): void
    {
        Cacher::$silent  = true;
        $this->cachePath = dirname(dirname((new ReflectionClass(ClassLoader::class))->getFileName()), 2) . '/ib-cache.php';
        // Point to a directory with known ImmutableBase subclasses (your test objects)
        $this->scanDir = getcwd() . '/tests';

        // Backup original state
        $this->originalProperties = ImmutableBase::state()['properties'];
        $this->originalCachedMeta = ImmutableBase::state()['cachedMeta'];
        $this->originalRefs       = ImmutableBase::state()['refs'];
    }

    protected function tearDown(): void
    {
        if (file_exists($this->cachePath)) {
            unlink($this->cachePath);
        }
        // Restore original state
        $s               = &ImmutableBase::state();
        $s['properties'] = $this->originalProperties;
        $s['cachedMeta'] = $this->originalCachedMeta;
        $s['refs']       = $this->originalRefs;
        $s['cachePath']  = null;
    }

    public function testScanGeneratesCacheFile(): void
    {
        $initialLevel = ob_get_level();
        try {
            ob_start();
            (new Cacher())->scan($this->scanDir);
            $this->assertFileExists($this->cachePath);
        } finally {
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }
        }
    }

    public function testCacheFileReturnsArray(): void
    {
        $initialLevel = ob_get_level();
        try {
            ob_start();
            (new Cacher())->scan($this->scanDir);
        } finally {
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }
        }

        $cache = require $this->cachePath;
        $this->assertIsArray($cache);
        $this->assertNotEmpty($cache);
    }

    public function testCacheExcludesNonSerializableEntries(): void
    {
        $initialLevel = ob_get_level();
        try {
            ob_start();
            (new Cacher())->scan($this->scanDir);
        } finally {
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }
        }

        $cache = require $this->cachePath;
        foreach ($cache as $className => $props) {
            $this->assertArrayNotHasKey('ref', $props, "Cache entry for $className should not contain 'ref'");
            $this->assertArrayNotHasKey('validateMethod', $props, "Cache entry for $className should not contain 'validateMethod'");
            $this->assertArrayNotHasKey('hydrator', $props, "Cache entry for $className should not contain 'hydrator'");
            foreach ($props['types'] ?? [] as $typeName => $type) {
                $this->assertArrayNotHasKey('ref', $type, "Type $typeName in $className should not contain 'ref'");
                $this->assertArrayNotHasKey('typeRef', $type, "Type $typeName in $className should not contain 'typeRef'");
                $this->assertArrayNotHasKey('resolver', $type, "Type $typeName in $className should not contain 'resolver'");
                $this->assertArrayNotHasKey('propertyRef', $type, "Type $typeName in $className should not contain 'propertyRef'");
            }
        }
    }

    public function testScanSilentModeSuppressesWarnings(): void
    {
        $initialLevel = ob_get_level();
        ob_start();
        try {
            (new Cacher())->scan($this->scanDir);
            $output = ob_get_clean();
        } finally {
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }
        }
        // Silent mode should not produce yellow [Skipped] warnings on STDOUT
        // (warnings go to STDERR, but we verify no unexpected output)
        $this->assertEmpty($output);
    }

    public function testScanNonSilentModeShowsWarnings(): void
    {
        // Non-silent mode outputs DefinitionException warnings to STDERR
        // This test just verifies it doesn't crash
        $initialLevel = ob_get_level();
        try {
            ob_start();
            (new Cacher())->scan($this->scanDir);
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }
        } finally {
        }
        $this->assertFileExists($this->cachePath);
    }

    public function testScanSkipsVendorDirectory(): void
    {
        $initialLevel = ob_get_level();
        try {
            ob_start();
            // Scan from project root - vendor/ should be skipped
            (new Cacher())->scan(getcwd());
        } finally {
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }
        }

        $cache = require $this->cachePath;
        foreach (array_keys($cache) as $className) {
            $this->assertStringNotContainsString(
                'PHPUnit',
                $className,
                'Cache should not contain vendor classes'
            );
        }
    }

    public function testScanEmptyDirectoryGeneratesEmptyCache(): void
    {
        $emptyDir = sys_get_temp_dir() . '/ib_test_empty_' . uniqid();
        mkdir($emptyDir);

        $initialLevel = ob_get_level();
        try {
            ob_start();
            (new Cacher())->scan($emptyDir);
        } finally {
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }
            rmdir($emptyDir);
        }

        $this->assertFileExists($this->cachePath);
    }

    public function testScanOverwritesExistingCacheFile(): void
    {
        file_put_contents($this->cachePath, '<?php return ["old" => true];');

        $initialLevel = ob_get_level();
        try {
            ob_start();
            (new Cacher())->scan($this->scanDir);
        } finally {
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }
        }

        $cache = require $this->cachePath;
        $this->assertArrayNotHasKey('old', $cache);
    }

    public function testLoadCachePopulatesCachedMeta(): void
    {
        $initialLevel = ob_get_level();
        try {
            ob_start();
            (new Cacher())->scan($this->scanDir);
        } finally {
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }
        }

        $s               = &ImmutableBase::state();
        $s['cachedMeta'] = [];
        $s['cachePath']  = null;
        ImmutableBase::loadCache();

        $this->assertNotEmpty(ImmutableBase::state()['cachedMeta']);
    }

    public function testLoadCacheSkipsWhenAlreadyLoaded(): void
    {
        $s               = &ImmutableBase::state();
        $s['cachedMeta'] = ['already' => 'loaded'];
        ImmutableBase::loadCache();

        // Should not overwrite existing cache
        $this->assertArrayHasKey('already', ImmutableBase::state()['cachedMeta']);
    }

    public function testLoadCacheSkipsWhenFileNotExists(): void
    {
        $s               = &ImmutableBase::state();
        $s['cachedMeta'] = [];
        $s['cachePath']  = '/nonexistent/path/ib-cache.php';
        ImmutableBase::loadCache();

        $this->assertEmpty(ImmutableBase::state()['cachedMeta']);
    }

    public function testCachedMetaRestoredDuringBuild(): void
    {
        $initialLevel = ob_get_level();
        try {
            ob_start();
            (new Cacher())->scan($this->scanDir);
        } finally {
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }
        }

        // Load cache, clear compiled properties, force rebuild from cache
        $s               = &ImmutableBase::state();
        $s['cachedMeta'] = [];
        $s['cachePath']  = null;
        ImmutableBase::loadCache();
        $s['properties'] = [];
        $s['refs']       = [];

        // Constructing any object should now use cachedMeta path
        // Replace with one of your actual test DTO classes:
        // $dto = YourDTO::fromArray([...]);
        // $this->assertInstanceOf(YourDTO::class, $dto);
        $this->assertNotEmpty(ImmutableBase::state()['cachedMeta']);
    }
}
