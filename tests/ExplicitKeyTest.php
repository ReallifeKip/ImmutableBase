<?php

declare (strict_types = 1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\CLI\Cacher;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidKeyNameException;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\StrictViolationException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use Tests\DataTransferObjects\KeyConversion\DuplicateInputKeyDTO;
use Tests\DataTransferObjects\KeyConversion\EmptyInputKeyDTO;
use Tests\DataTransferObjects\KeyConversion\ExplicitKeyDTO;
use Tests\DataTransferObjects\KeyConversion\ExplicitKeyPrecedenceDTO;
use Tests\DataTransferObjects\KeyConversion\NonStringOutputKeyDTO;

/**
 * #[InputKey] / #[OutputKey]: exact key names for input and output, for keys
 * that no KeyCase conversion can produce.
 */
class ExplicitKeyTest extends TestCase
{
    private const INPUT = ['2fa' => true, 'user.id' => 7, '$ref' => 'r'];

    public function testInputKeyReadsTheExactKey(): void
    {
        $dto = ExplicitKeyDTO::fromArray(self::INPUT);
        $this->assertSame([true, 7, 'r'], [$dto->twoFactor, $dto->userId, $dto->ref]);
    }

    public function testFromJsonUsesInputKey(): void
    {
        $this->assertSame(7, ExplicitKeyDTO::fromJson(json_encode(self::INPUT))->userId);
    }

    public function testPropertyNameIsStillAccepted(): void
    {
        $dto = ExplicitKeyDTO::fromArray(['twoFactor' => false, 'userId' => 1]);
        $this->assertSame([false, 1, null], [$dto->twoFactor, $dto->userId, $dto->ref]);
    }

    public function testOutputKeyRoundTrips(): void
    {
        $dto = ExplicitKeyDTO::fromArray(self::INPUT);
        $this->assertSame(['2fa' => true, 'user.id' => 7, 'ref' => 'r'], $dto->toArray(true));
        $this->assertSame($dto->toArray(true), ExplicitKeyDTO::fromArray($dto->toArray(true))->toArray(true));
    }

    public function testOutputKeyIsIgnoredWithoutTrue(): void
    {
        $this->assertSame(['twoFactor' => true, 'userId' => 7, 'ref' => 'r'], ExplicitKeyDTO::fromArray(self::INPUT)->toArray());
    }

    public function testForcedKeyCaseConvertsThePropertyName(): void
    {
        $this->assertSame(['two_factor' => true, 'user_id' => 7, 'ref' => 'r'], ExplicitKeyDTO::fromArray(self::INPUT)->toArray(KeyCase::Snake));
    }

    public function testWithUsesInputKey(): void
    {
        $this->assertSame(9, ExplicitKeyDTO::fromArray(self::INPUT)->with(['user.id' => 9])->userId);
    }

    public function testInputKeyTakesPrecedenceOverKeyCasesUnderStrict(): void
    {
        $dto = ExplicitKeyPrecedenceDTO::fromArray(['first_name' => 'Kip', 'x-api-key' => 'k']);
        $this->assertSame(['Kip', 'k'], [$dto->firstName, $dto->apiKey]);
    }

    public function testOutputKeyTakesPrecedenceOverKeyCases(): void
    {
        $dto = ExplicitKeyPrecedenceDTO::fromArray(['first_name' => 'Kip', 'x-api-key' => 'k']);
        $this->assertSame(['first_name' => 'Kip', 'X-Api-Key' => 'k'], $dto->toArray(true));
    }

    public function testStrictStillRejectsUnmappedKeys(): void
    {
        $this->expectException(StrictViolationException::class);
        ExplicitKeyPrecedenceDTO::fromArray(['first_name' => 'Kip', 'x-api-key' => 'k', 'typo' => 1]);
    }

    public function testDuplicateInputKeyIsRejected(): void
    {
        $this->expectException(InvalidKeyNameException::class);
        $this->expectExceptionMessage("Duplicate key 'id' in #[InputKey] on " . DuplicateInputKeyDTO::class . '::$b: already declared on ' . DuplicateInputKeyDTO::class . '::$a');
        DuplicateInputKeyDTO::fromArray(['id' => 1]);
    }

    public function testEmptyInputKeyIsRejected(): void
    {
        $this->expectException(InvalidKeyNameException::class);
        $this->expectExceptionMessage("Invalid key '' in #[InputKey] on " . EmptyInputKeyDTO::class . '::$a: expected a non-empty string');
        EmptyInputKeyDTO::fromArray(['a' => 1]);
    }

    public function testNonStringOutputKeyIsRejected(): void
    {
        $this->expectException(InvalidKeyNameException::class);
        $this->expectExceptionMessage('Invalid key int in #[OutputKey] on ' . NonStringOutputKeyDTO::class . '::$a: expected a non-empty string');
        NonStringOutputKeyDTO::fromArray(['a' => 1]);
    }

    public function testCachedMetadataKeepsExplicitKeys(): void
    {
        $original  = ImmutableBase::state();
        $cacheFile = sys_get_temp_dir() . '/ib_explicit_key_cache_' . uniqid() . '.php';
        try {
            Cacher::$silent = true;
            $s              = &ImmutableBase::state();
            $s['cachePath'] = $cacheFile;
            $s['properties'] = [];
            $s['refs']       = [];
            $level          = ob_get_level();
            ob_start();
            try {
                (new Cacher())->scan(__DIR__ . '/DataTransferObjects/KeyConversion');
            } finally {
                while (ob_get_level() > $level) {
                    ob_end_clean();
                }
            }
            $cache = require $cacheFile;
            $this->assertSame(['2fa' => 'twoFactor', 'user.id' => 'userId', '$ref' => 'ref'], $cache[ExplicitKeyDTO::class]['propertyInputKeys']);
            $s['cachedMeta'] = $cache;
            $s['properties'] = [];
            $s['refs']       = [];
            $this->assertSame(['2fa' => true, 'user.id' => 7, 'ref' => 'r'], ExplicitKeyDTO::fromArray(self::INPUT)->toArray(true));
        } finally {
            @unlink($cacheFile);
            $s = &ImmutableBase::state();
            $s = $original;
        }
    }
}
