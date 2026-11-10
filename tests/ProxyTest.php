<?php

declare (strict_types = 1);

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\CLI\Cacher;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidProxyException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidSerializeTargetException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidWithPathException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidValueException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\UnserializableValueException;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\ImmutableViolationException;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\ValidationChainException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\ImmutableProxy;
use ReallifeKip\ImmutableBase\Proxies;
use Tests\Proxy\ApiClientDTO;
use Tests\Proxy\ApiClientVO;
use Tests\Proxy\ClosureHolder;
use Tests\Proxy\Endpoint;
use Tests\Proxy\Invalid\MismatchedTargetDTO;
use Tests\Proxy\Invalid\MissingClassProxyDTO;
use Tests\Proxy\Invalid\MissingTargetDTO;
use Tests\Proxy\Invalid\ProxyInUnionDTO;
use Tests\Proxy\Invalid\ProxyOnScalarDTO;
use Tests\Proxy\Invalid\UnknownTargetDTO;
use Tests\Proxy\OpaqueThing;
use Tests\Proxy\VendorConfig;
use Tests\Proxy\VendorConfigProxy;

/**
 * ImmutableProxy lets a property hold any object — mutable or not, from any
 * package — while the owning object stays immutable: the proxy stores only a
 * serialized snapshot, forwards reads to a fresh deep copy, and refuses
 * writes. Its own members are private, so every public name belongs to the
 * held object.
 */
class ProxyTest extends TestCase
{
    private static function config(int $timeout = 30, string $url = 'https://api.example.com'): VendorConfig
    {
        return new VendorConfig($timeout, new Endpoint($url));
    }

    private static function client(array $override = []): ApiClientDTO
    {
        return ApiClientDTO::fromArray($override + ['name' => 'billing', 'config' => self::config(), 'fallback' => null, 'opaque' => null, 'hook' => null]);
    }

    // ─── Holding ─────────────────────────────────────────────

    public function testTypedProxyWrapsTheGivenObject(): void
    {
        $client = self::client();
        $this->assertInstanceOf(VendorConfigProxy::class, $client->config);
        $this->assertSame(VendorConfig::class, Proxies::heldClass($client->config));
    }

    public function testAttributeProxyWrapsTheGivenObject(): void
    {
        $client = self::client(['opaque' => new OpaqueThing(7)]);
        $this->assertSame(ImmutableProxy::class, $client->opaque::class);
        $this->assertSame(7, $client->opaque->n);
    }

    public function testAnExistingProxyIsAcceptedAsIs(): void
    {
        $proxy = self::client(['config' => self::config(45)])->config;
        $this->assertSame($proxy, self::client(['config' => $proxy])->config);
    }

    public function testNullableProxy(): void
    {
        $this->assertNull(self::client()->fallback);
        $this->assertSame(5, self::client(['fallback' => self::config(5)])->fallback->timeout);
    }

    public function testProxiesCannotBeBuiltOutsideTheEngine(): void
    {
        $this->expectException(\Error::class);
        new VendorConfigProxy(self::config());
    }

    // ─── Forwarded reads ─────────────────────────────────────

    public function testPropertyReadsAreForwarded(): void
    {
        $config = self::client()->config;
        $this->assertSame(30, $config->timeout);
        $this->assertSame('https://api.example.com', $config->endpoint->url);
        $this->assertTrue(isset($config->timeout));
        $this->assertFalse(isset($config->nope));
    }

    public function testMethodCallsAreForwarded(): void
    {
        $this->assertSame('30s @ https://api.example.com', self::client()->config->describe());
    }

    public function testStringConversionAndInvocationAreForwarded(): void
    {
        $config = self::client()->config;
        $this->assertSame('30s @ https://api.example.com', (string) $config);
        $this->assertSame(35, $config(5));
    }

    public function testNamesThatAProxyMightUseBelongToTheHeldObject(): void
    {
        $config = self::client()->config;
        $source = self::config();
        $source->headers['Accept'] = 'json';
        $withHeader = self::client(['config' => $source])->config;

        $this->assertSame('json', $withHeader->get('Accept'));            // VendorConfig::get()
        $this->assertTrue($config->equals(self::config()));                // VendorConfig::equals()
        $this->assertSame('vendor-owned property', $config->class);       // VendorConfig::$class
    }

    // ─── Writes are refused ──────────────────────────────────

    public function testAMutatingMethodIsRefused(): void
    {
        $this->expectException(ImmutableViolationException::class);
        $this->expectExceptionMessage('setTimeout() would modify the held ' . VendorConfig::class);
        self::client()->config->setTimeout(5);
    }

    public function testAssigningAPropertyIsRefused(): void
    {
        $this->expectException(ImmutableViolationException::class);
        self::client()->config->timeout = 5;
    }

    public function testUnsettingAPropertyIsRefused(): void
    {
        $config = self::client()->config;
        $this->expectException(ImmutableViolationException::class);
        unset($config->timeout);
    }

    public function testANestedWriteCannotReachTheHolder(): void
    {
        $config                = self::client()->config;
        $config->endpoint->url = 'changed'; // lands on a throwaway copy
        $this->assertSame('https://api.example.com', $config->endpoint->url);
    }

    /** Documented limit: a reader that caches into the object is seen as a write. */
    public function testAMemoizingReaderIsRefused(): void
    {
        $this->expectException(ImmutableViolationException::class);
        self::client()->config->memoizedLabel();
    }

    public function testTheProxyCannotBeModifiedEvenThroughReflection(): void
    {
        $proxy = self::client()->config;
        $this->expectException(\Error::class);
        (new \ReflectionProperty(ImmutableProxy::class, 'snapshot'))->setValue($proxy, 'tampered');
    }

    // ─── No pollution ────────────────────────────────────────

    public function testTheOriginalObjectCannotPolluteTheHolder(): void
    {
        $source = self::config();
        $client = self::client(['config' => $source]);
        $source->setTimeout(999);
        $source->endpoint->url = 'https://polluted.example.com';

        $this->assertSame(30, $client->config->timeout);
        $this->assertSame(['timeout' => 30, 'url' => 'https://api.example.com', 'headers' => []], $client->toArray()['config']);
        $this->assertStringContainsString('"timeout":30', $client->toJson());
    }

    public function testAnUnwrappedCopyCannotPolluteTheHolder(): void
    {
        $client = self::client();
        $copy   = Proxies::unwrap($client->config);
        $copy->setTimeout(1);
        $copy->endpoint->url = 'changed';
        $copy->headers[]     = 'X-Injected';

        $this->assertSame(['timeout' => 30, 'url' => 'https://api.example.com', 'headers' => []], $client->toArray()['config']);
    }

    public function testSerializationFollowsWithAndLeavesThePreviousVersionAlone(): void
    {
        $v1 = self::client();
        $v2 = $v1->with(['config' => self::config(45, 'https://b.example.com')]);
        $v3 = $v2->with(['name' => 'renamed']);

        $this->assertSame(45, $v2->toArray()['config']['timeout']);
        $this->assertSame(30, $v1->toArray()['config']['timeout']);
        $this->assertSame('https://b.example.com', $v3->toArray()['config']['url']);
    }

    public function testTheSameSourceWrappedTwiceKeepsEachSnapshot(): void
    {
        $source = self::config(1);
        $a      = self::client(['config' => $source]);
        $source->setTimeout(2);
        $b = self::client(['config' => $source]);

        $this->assertSame(1, $a->config->timeout);
        $this->assertSame(2, $b->config->timeout);
    }

    // ─── unwrap() ────────────────────────────────────────────

    public function testUnwrapReturnsARealInstanceForTypedApis(): void
    {
        $client = self::client();
        $typed  = static fn(VendorConfig $c): int => $c->timeout;
        $this->assertSame(30, $typed(Proxies::unwrap($client->config)));
        $this->assertNotSame(Proxies::unwrap($client->config), Proxies::unwrap($client->config));
    }

    public function testTheProxyItselfIsNotTheHeldClass(): void
    {
        $this->assertNotInstanceOf(VendorConfig::class, self::client()->config);
    }

    // ─── Input rejection ─────────────────────────────────────

    public function testAnObjectOfTheWrongClassIsRejected(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('expected ' . VendorConfig::class . ', got ' . OpaqueThing::class);
        self::client(['config' => new OpaqueThing(1)]);
    }

    public function testAProxyHoldingTheWrongClassIsRejected(): void
    {
        $this->expectException(InvalidValueException::class);
        self::client(['opaque' => self::client()->config]);
    }

    #[DataProvider('nonObjects')]
    public function testNonObjectsAreRejected(mixed $value): void
    {
        $this->expectException(InvalidValueException::class);
        self::client(['config' => $value]);
    }

    public static function nonObjects(): array
    {
        return ['array' => [['timeout' => 1]], 'json' => ['{"timeout":1}'], 'int' => [1], 'string' => ['x']];
    }

    public function testAnUnserializableObjectIsRejected(): void
    {
        $this->expectException(UnserializableValueException::class);
        $this->expectExceptionMessage(ClosureHolder::class);
        self::client(['hook' => new ClosureHolder(fn() => null)]);
    }

    // ─── Serialization and comparison ───────────────────────

    public function testToArrayUsesJsonSerializable(): void
    {
        $this->assertSame(
            ['name' => 'billing', 'config' => ['timeout' => 30, 'url' => 'https://api.example.com', 'headers' => []], 'fallback' => null, 'opaque' => null, 'hook' => null],
            self::client()->toArray()
        );
        $this->assertSame(json_encode(self::client()->toArray()), self::client()->toJson());
    }

    public function testToArrayWithoutAnOutputFormatThrows(): void
    {
        $this->expectException(InvalidSerializeTargetException::class);
        self::client(['opaque' => new OpaqueThing(1)])->toArray();
    }

    public function testEqualsComparesSnapshots(): void
    {
        $this->assertTrue(self::client()->equals(self::client()));
        $this->assertFalse(self::client()->equals(self::client(['config' => self::config(60)])));
        $this->assertFalse(self::client()->equals(self::client(['config' => self::config(30, 'https://other.example.com')])));
    }

    // ─── with() ──────────────────────────────────────────────

    public function testWithReplacesTheHeldObject(): void
    {
        $client  = self::client();
        $updated = $client->with(['config' => Proxies::unwrap($client->config)->setTimeout(45)]);
        $this->assertSame(45, $updated->config->timeout);
        $this->assertSame(30, $client->config->timeout);
    }

    public function testWithCannotTraverseIntoTheHeldObject(): void
    {
        $this->expectException(InvalidWithPathException::class);
        self::client()->with(['config.timeout' => 5]);
    }

    // ─── Value objects ───────────────────────────────────────

    public function testValidationCanReadTheHeldObject(): void
    {
        $this->assertSame(1, ApiClientVO::fromArray(['config' => self::config(1)])->config->timeout);
        $this->expectException(ValidationChainException::class);
        ApiClientVO::fromArray(['config' => self::config(0)]);
    }

    // ─── Definition errors ───────────────────────────────────

    #[DataProvider('invalidDefinitions')]
    public function testInvalidDefinitionsAreRejected(string $class, array $data, string $message): void
    {
        $this->expectException(InvalidProxyException::class);
        $this->expectExceptionMessage($message);
        $class::fromArray($data);
    }

    public static function invalidDefinitions(): array
    {
        $config = new VendorConfig(1, new Endpoint('u'));

        return [
            'base proxy without #[Proxy]'           => [MissingTargetDTO::class, ['thing' => $config], 'declare its target class'],
            'property #[Proxy] contradicts subclass' => [MismatchedTargetDTO::class, ['config' => $config], 'contradicts'],
            '#[Proxy] on a non-proxy property'      => [ProxyOnScalarDTO::class, ['name' => 'x'], 'only applies to ImmutableProxy'],
            'property #[Proxy] naming no class'     => [UnknownTargetDTO::class, ['thing' => $config], 'does not exist'],
            'subclass #[Proxy] naming no class'     => [MissingClassProxyDTO::class, ['thing' => $config], 'does not exist'],
            'proxy inside a union'                  => [ProxyInUnionDTO::class, ['config' => 'x'], 'union'],
        ];
    }

    // ─── Cache ───────────────────────────────────────────────

    public function testProxyMetadataSurvivesTheCache(): void
    {
        $original  = ImmutableBase::state();
        $cacheFile = sys_get_temp_dir() . '/ib_proxy_cache_' . uniqid() . '.php';
        try {
            Cacher::$silent = true;
            $s              = &ImmutableBase::state();
            $s['cachePath'] = $cacheFile;
            $level          = ob_get_level();
            ob_start();
            try {
                (new Cacher())->scan(__DIR__ . '/Proxy');
            } finally {
                while (ob_get_level() > $level) {
                    ob_end_clean();
                }
            }
            $s['cachedMeta'] = require $cacheFile;
            $s['properties'] = [];
            $this->assertSame(VendorConfig::class, $s['cachedMeta'][ApiClientDTO::class]['types']['config']['proxyTarget']);

            $client = self::client(['opaque' => new OpaqueThing(3)]);
            $this->assertArrayNotHasKey('propertyRef', ImmutableBase::state()['properties'][ApiClientDTO::class]['types']['config'], 'served from cache');
            $this->assertSame(30, $client->config->timeout);
            $this->assertSame(3, $client->opaque->n);
            $this->expectException(InvalidValueException::class);
            self::client(['config' => new OpaqueThing(1)]);
        } finally {
            @unlink($cacheFile);
            $s = &ImmutableBase::state();
            $s = $original;
        }
    }
}
