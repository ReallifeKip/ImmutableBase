<?php

declare (strict_types = 1);

namespace Tests\Regressions;

use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidCompareTargetException;
use Tests\Regressions\Objects\CodeItemDTO;
use Tests\Regressions\Objects\EqualsDTO;
use Tests\Regressions\Objects\Level;
use Tests\Regressions\Objects\MixedDTO;
use Tests\Regressions\Objects\PathItemDTO;

/**
 * equals() over array properties: null elements, enum elements, nested shape
 * mismatches and differing element classes must compare, not throw.
 */
class EqualsTest extends TestCase
{
    private static function make(array $raw, array $levels = []): EqualsDTO
    {
        return EqualsDTO::fromArray(['raw' => $raw, 'levels' => $levels]);
    }

    public function testNullElementsAreEqual(): void
    {
        $this->assertTrue(self::make([null])->equals(self::make([null])));
        $this->assertTrue(self::make(['a' => null])->equals(self::make(['a' => null])));
    }

    public function testNullElementDiffersFromMissingKey(): void
    {
        $this->assertFalse(self::make(['a' => null])->equals(self::make(['b' => null])));
    }

    public function testEnumElementsCompare(): void
    {
        $this->assertTrue(self::make([], [Level::Low, 'high'])->equals(self::make([], ['low', Level::High])));
        $this->assertFalse(self::make([], [Level::Low])->equals(self::make([], [Level::High])));
    }

    public function testNestedShapeMismatchIsNotEqual(): void
    {
        $this->assertFalse(self::make([[1]])->equals(self::make([1])));
        $this->assertFalse(self::make([1])->equals(self::make([[1]])));
    }

    public function testObjectVersusScalarIsNotEqual(): void
    {
        $item = PathItemDTO::fromArray(['sku' => 'a']);
        $this->assertFalse(self::make([$item])->equals(self::make(['a'])));
        $this->assertFalse(self::make(['a'])->equals(self::make([$item])));
    }

    public function testDifferentElementClassesAreNotEqual(): void
    {
        $a = PathItemDTO::fromArray(['sku' => 'a']);
        $b = CodeItemDTO::fromArray(['code' => 'a']);
        $this->assertFalse(self::make([$a])->equals(self::make([$b])));
    }

    public function testEqualNestedObjects(): void
    {
        $this->assertTrue(
            self::make([PathItemDTO::fromArray(['sku' => 'a'])])
                ->equals(self::make([PathItemDTO::fromArray(['sku' => 'a'])]))
        );
    }

    public function testForeignObjectInMixedPropertyIsUncomparable(): void
    {
        $a = MixedDTO::fromArray(['any' => new \DateTimeImmutable('2020-01-01')]);
        $b = MixedDTO::fromArray(['any' => new \DateTimeImmutable('2030-01-01')]);
        $this->expectException(InvalidCompareTargetException::class);
        $a->equals($b);
    }
}
