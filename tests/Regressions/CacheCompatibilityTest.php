<?php

declare (strict_types = 1);

namespace Tests\Regressions;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\StrictViolationException;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\ValidationChainException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\Internal\Metadata;
use Tests\DataTransferObjects\DefaultValuesBothDTO;
use Tests\DataTransferObjects\KeyConversion\HeadersMimicDTO;
use Tests\DataTransferObjects\KeyConversion\NestedOutputParentDTO;
use Tests\DataTransferObjects\KeyConversion\PropertyInputKeyDTO;
use Tests\DataTransferObjects\KeyConversion\RoundTripDTO;
use Tests\DataTransferObjects\MultiArrayOfWithNativeDTO;
use Tests\DataTransferObjects\PrepareInput\PrepareInputChildDTO;
use Tests\DataTransferObjects\SkipOnNullDTO;
use Tests\DataTransferObjects\StrictDTO;
use Tests\SingleValueObjects\GmailSVO;
use Tests\SingleValueObjects\MailSVO;

/**
 * A cache written by the v4.6.0 ib-cacher (Fixtures/ib-cache-v4.6.0.php) is
 * served as-is by the current engine, without rescanning, and gives the same
 * results as reflection. Upgrading must not require regenerating the cache.
 */
class CacheCompatibilityTest extends TestCase
{
    private const MARKER = '__servedFromV460Cache';

    private array $original;

    protected function setUp(): void
    {
        $this->original = ImmutableBase::state();
    }

    protected function tearDown(): void
    {
        $s = &ImmutableBase::state();
        $s = $this->original;
    }

    /** @param bool $cached Whether to serve metadata from the v4.6.0 cache (with a marker) or by reflection. */
    private static function reset(bool $cached): void
    {
        $s               = &ImmutableBase::state();
        $s['properties'] = [];
        $s['refs']       = [];
        $s['cachedMeta'] = [];
        if ($cached) {
            foreach (require __DIR__ . '/Fixtures/ib-cache-v4.6.0.php' as $class => $entry) {
                $s['cachedMeta'][$class] = $entry + [self::MARKER => true];
            }
        }
    }

    public static function objects(): array
    {
        return [
            'class + output key cases'  => [RoundTripDTO::class, ['first_name' => 'A', 'last_name' => 'B']],
            'property key case'         => [HeadersMimicDTO::class, ['cache-control' => 'c', 'sec-ch-ua' => 'u', 'sec-ch-ua-mobile' => 'm', 'sec-ch-ua-platform' => 'p']],
            'property-level input keys' => [PropertyInputKeyDTO::class, ['first_name' => 'A', 'lastName' => 'B', 'city' => 'C']],
            'nested output keys'        => [NestedOutputParentDTO::class, ['parentName' => 'P', 'childItem' => ['childField' => 'F']]],
            'prepareInput chain'        => [PrepareInputChildDTO::class, ['a' => 'x', 'b' => 'y']],
            'cached defaults'           => [DefaultValuesBothDTO::class, []],
            'ArrayOf class + native'    => [MultiArrayOfWithNativeDTO::class, ['items' => [['string1' => 's'], 3]]],
            'SkipOnNull'                => [SkipOnNullDTO::class, ['string' => null]],
            'SVO validate()'            => [MailSVO::class, 'a@b.c'],
        ];
    }

    #[DataProvider('objects')]
    public function testServedFromCacheWithReflectionResults(string $class, array | string $input): void
    {
        $build = static fn() => \is_string($input) ? $class::from($input) : $class::fromArray($input);
        self::reset(false);
        $expected = $build();
        $this->assertArrayNotHasKey(self::MARKER, Metadata::get($class));

        self::reset(true);
        $actual = $build();
        $this->assertArrayHasKey(self::MARKER, Metadata::get($class), "$class was rescanned instead of served from the cache");
        $this->assertTrue($actual->equals($expected));
        if (!\is_string($input)) {
            $this->assertSame($expected->toArray(true), $actual->toArray(true));
        }
    }

    public function testCachedStrictStillRejectsUnknownKeys(): void
    {
        self::reset(true);
        $this->expectException(StrictViolationException::class);
        try {
            StrictDTO::fromArray(['string' => 's', 'typo' => 1]);
        } finally {
            $this->assertArrayHasKey(self::MARKER, Metadata::get(StrictDTO::class));
        }
    }

    public function testCachedValidateMethodStillRuns(): void
    {
        self::reset(true);
        $this->expectException(ValidationChainException::class);
        try {
            GmailSVO::from('a@outlook.com');
        } finally {
            $this->assertArrayHasKey(self::MARKER, Metadata::get(GmailSVO::class));
        }
    }
}
