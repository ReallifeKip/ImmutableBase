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
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\ValidationChainException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\ImmutableProxy;
use Tests\Proxy\ApiClientDTO;
use Tests\Proxy\ApiClientVO;
use Tests\Proxy\ClosureHolder;
use Tests\Proxy\Endpoint;
use Tests\Proxy\Invalid\MismatchedTargetDTO;
use Tests\Proxy\Invalid\MissingTargetDTO;
use Tests\Proxy\Invalid\UnionGetDTO;
use Tests\Proxy\Invalid\ProxyInUnionDTO;
use Tests\Proxy\Invalid\ProxyOnScalarDTO;
use Tests\Proxy\Invalid\UnknownTargetDTO;
use Tests\Proxy\OpaqueThing;
use Tests\Proxy\VendorConfig;
use Tests\Proxy\VendorConfigProxy;

/**
 * ImmutableProxy lets a property hold any object — mutable or not, from any
 * package — while the owning object stays immutable: the proxy stores only a
 * serialized snapshot, and every get() rebuilds a fresh deep copy.
 */
class ProxyTest extends TestCase
{
    private static function config(int $timeout = 30, string $url = 'https://api.example.com'): VendorConfig
    {
        return new VendorConfig($timeout, new Endpoint($url));
    }

    private static function client(array $override = []): ApiClientDTO
    {
        return ApiClientDTO::fromArray($override + ['name' => 'billing', 'config' => self::config(), 'fallback' => null, 'opaque' => null]);
    }

    // ─── Holding ─────────────────────────────────────────────

    public function testTypedProxyWrapsTheGivenObject(): void
    {
        $client = self::client();
        $this->assertInstanceOf(VendorConfigProxy::class, $client->config);
        $this->assertSame(VendorConfig::class, $client->config->class);
        $this->assertInstanceOf(VendorConfig::class, $client->config->get());
    }

    public function testAttributeProxyWrapsTheGivenObject(): void
    {
        $client = self::client(['opaque' => new OpaqueThing(7)]);
        $this->assertSame(ImmutableProxy::class, $client->opaque::class);
        $this->assertSame(7, $client->opaque->get()->n);
    }

    public function testAnExistingProxyIsAcceptedAsIs(): void
    {
        $proxy = VendorConfigProxy::of(self::config(45));
        $this->assertSame($proxy, self::client(['config' => $proxy])->config);
    }

    public function testNullableProxy(): void
    {
        $this->assertNull(self::client()->fallback);
        $this->assertSame(5, self::client(['fallback' => self::config(5)])->fallback->get()->timeout);
    }

    // ─── Immutability ────────────────────────────────────────

    public function testMutatingTheOriginalObjectDoesNotAffectTheHolder(): void
    {
        $input  = self::config();
        $client = self::client(['config' => $input]);
        $input->setTimeout(999);
        $input->endpoint->url = 'https://changed.example.com';

        $this->assertSame(30, $client->config->get()->timeout);
        $this->assertSame('https://api.example.com', $client->config->get()->endpoint->url);
    }

    public function testMutatingAReadCopyDoesNotAffectTheHolder(): void
    {
        $client = self::client();
        $copy   = $client->config->get();
        $copy->setTimeout(1);
        $copy->endpoint->url = 'changed';
        $copy->headers[]     = 'X-Injected';

        $fresh = $client->config->get();
        $this->assertSame(30, $fresh->timeout);
        $this->assertSame('https://api.example.com', $fresh->endpoint->url);
        $this->assertSame([], $fresh->headers);
    }

    public function testEveryReadIsANewInstance(): void
    {
        $client = self::client();
        $this->assertNotSame($client->config->get(), $client->config->get());
    }

    public function testTheProxyCannotBeModifiedEvenThroughReflection(): void
    {
        $proxy = self::client()->config;
        $this->expectException(\Error::class);
        (new \ReflectionProperty(ImmutableProxy::class, 'snapshot'))->setValue($proxy, 'tampered');
    }

    // ─── Input rejection ─────────────────────────────────────

    public function testAnObjectOfTheWrongClassIsRejected(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('expected ' . VendorConfig::class . ', got ' . OpaqueThing::class);
        self::client(['config' => new OpaqueThing(1)]);
    }

    public function testAProxyOfTheWrongClassIsRejected(): void
    {
        $this->expectException(InvalidValueException::class);
        self::client(['opaque' => VendorConfigProxy::of(self::config())]);
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
        ImmutableProxy::of(new ClosureHolder(fn() => null));
    }

    public function testATypedProxyRejectsTheWrongClassWhenBuiltDirectly(): void
    {
        $this->expectException(InvalidValueException::class);
        VendorConfigProxy::of(new OpaqueThing(1));
    }

    // ─── Serialization and comparison ───────────────────────

    public function testToArrayUsesJsonSerializable(): void
    {
        $this->assertSame(
            ['name' => 'billing', 'config' => ['timeout' => 30, 'url' => 'https://api.example.com', 'headers' => []], 'fallback' => null, 'opaque' => null],
            self::client()->toArray()
        );
        $this->assertSame('{"name":"billing","config":{"timeout":30,"url":"https:\/\/api.example.com","headers":[]},"fallback":null,"opaque":null}', self::client()->toJson());
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
        $updated = $client->with(['config' => $client->config->get()->setTimeout(45)]);
        $this->assertSame(45, $updated->config->get()->timeout);
        $this->assertSame(30, $client->config->get()->timeout);
    }

    public function testWithCannotTraverseIntoTheHeldObject(): void
    {
        $this->expectException(InvalidWithPathException::class);
        self::client()->with(['config.timeout' => 5]);
    }

    // ─── Value objects ───────────────────────────────────────

    public function testValidationCanReadTheHeldObject(): void
    {
        $this->assertSame(1, ApiClientVO::fromArray(['config' => self::config(1)])->config->get()->timeout);
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
            'base proxy without #[Proxy]'      => [MissingTargetDTO::class, ['thing' => $config], 'declare its target class'],
            '#[Proxy] contradicts get()'       => [MismatchedTargetDTO::class, ['config' => $config], 'contradicts'],
            '#[Proxy] on a non-proxy property' => [ProxyOnScalarDTO::class, ['name' => 'x'], 'only applies to ImmutableProxy'],
            '#[Proxy] naming a missing class'  => [UnknownTargetDTO::class, ['thing' => $config], 'does not exist'],
            'proxy inside a union'             => [ProxyInUnionDTO::class, ['config' => 'x'], 'union'],
            'union get() return type'          => [UnionGetDTO::class, ['config' => $config], 'single class'],
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
            $this->assertSame(30, $client->config->get()->timeout);
            $this->assertSame(3, $client->opaque->get()->n);
            $this->expectException(InvalidValueException::class);
            self::client(['config' => new OpaqueThing(1)]);
        } finally {
            @unlink($cacheFile);
            $s = &ImmutableBase::state();
            $s = $original;
        }
    }
}
