<?php

declare (strict_types = 1);

namespace Tests\Regressions;

use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidValueException;
use Tests\Regressions\Objects\PathItemDTO;
use Tests\Regressions\Objects\ThrowingHolderDTO;
use Tests\Regressions\Objects\UnionHolderDTO;

/**
 * Error paths must describe only the failure being reported. They used to be
 * accumulated in static counters, so a non-ImmutableBase exception escaping a
 * construction, or a union member failure swallowed by the fallback, left
 * stale state behind that corrupted every later message in the process.
 */
class ErrorPathTest extends TestCase
{
    public function testForeignExceptionDoesNotCorruptLaterErrorPaths(): void
    {
        try {
            ThrowingHolderDTO::fromArray(['inner' => ['name' => 'x']]);
            $this->fail('Should have thrown');
        } catch (\DomainException) {
        }

        try {
            PathItemDTO::fromArray(['sku' => 1]);
            $this->fail('Should have thrown');
        } catch (InvalidValueException $e) {
            $this->assertStringStartsWith(PathItemDTO::class . ' > $sku > ', $e->getMessage());
        }
    }

    public function testSwallowedUnionMemberFailureDoesNotLeakIntoPath(): void
    {
        try {
            UnionHolderDTO::fromArray(['u' => ['wrong' => 1]]);
            $this->fail('Should have thrown');
        } catch (InvalidValueException $e) {
            $this->assertStringStartsWith(UnionHolderDTO::class . ' > $u > ', $e->getMessage());
            $this->assertStringNotContainsString('$sku', $e->getMessage());
        }

        try {
            PathItemDTO::fromArray(['sku' => 1]);
            $this->fail('Should have thrown');
        } catch (InvalidValueException $e) {
            $this->assertSame(PathItemDTO::class . ' > $sku > Invalid value: expected string, got int.', $e->getMessage());
        }
    }

    public function testRepeatedFailuresProduceIdenticalMessages(): void
    {
        $messages = [];
        for ($i = 0; $i < 3; $i++) {
            try {
                UnionHolderDTO::fromArray(['u' => 'nope']);
            } catch (InvalidValueException $e) {
                $messages[] = $e->getMessage();
            }
        }
        $this->assertCount(1, array_unique($messages));
    }
}
