<?php

declare (strict_types = 1);

namespace Tests\Regressions;

use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidValueException;
use Tests\Regressions\Objects\PathItemDTO;
use Tests\Regressions\Objects\WithJsonDTO;
use Tests\SingleValueObjects\SVO;

/**
 * with() resolves each value exactly as fromArray() does. The only JSON it
 * decodes beyond that is for a plain `array` property (no #[ArrayOf]), which
 * with() has always accepted.
 */
class WithJsonTest extends TestCase
{
    private static function base(): WithJsonDTO
    {
        return WithJsonDTO::fromArray([
            'note'       => 'n',
            'meta'       => null,
            'raw'        => [],
            'rawOrInt'   => 0,
            'textOrList' => '',
            'label'      => null,
            'items'      => [],
        ]);
    }

    public function testStringPropertyKeepsBracketedText(): void
    {
        $this->assertSame('[draft] hi', self::base()->with(['note' => '[draft] hi'])->note);
        $this->assertSame('{"a":1}', self::base()->with(['note' => '{"a":1}'])->note);
    }

    public function testMixedPropertyKeepsJsonLookingString(): void
    {
        $this->assertSame('[1,2]', self::base()->with(['meta' => '[1,2]'])->meta);
    }

    public function testStringOrArrayUnionPrefersTheString(): void
    {
        $this->assertSame('[1]', self::base()->with(['textOrList' => '[1]'])->textOrList);
    }

    public function testSvoPropertyWrapsBracketedText(): void
    {
        $label = self::base()->with(['label' => '[x]'])->label;
        $this->assertInstanceOf(SVO::class, $label);
        $this->assertSame('[x]', $label->value);
    }

    public function testArrayOfJsonStringBuildsTypedItems(): void
    {
        $items = self::base()->with(['items' => '[{"sku":"a"},{"sku":"b"}]'])->items;
        $this->assertContainsOnlyInstancesOf(PathItemDTO::class, $items);
        $this->assertSame('b', $items[1]->sku);
    }

    public function testPlainArrayStillAcceptsJson(): void
    {
        $this->assertSame([1, 2], self::base()->with(['raw' => '[1,2]'])->raw);
        $this->assertSame(['a' => 1], self::base()->with(['raw' => '{"a":1}'])->raw);
    }

    public function testUnionWithArrayMemberRejectsJsonLikeFromArray(): void
    {
        $this->expectException(InvalidValueException::class);
        self::base()->with(['rawOrInt' => '{"a":1}']);
    }

    public function testPlainArrayRejectsMalformedJson(): void
    {
        $this->expectException(InvalidValueException::class);
        self::base()->with(['raw' => '[1,']);
    }

    public function testWithMatchesFromArrayForStringValues(): void
    {
        foreach (['plain', '[draft]', '{"a":1}', ' [x', '[]', '{}'] as $text) {
            $viaWith = self::base()->with(['note' => $text, 'meta' => $text, 'textOrList' => $text]);
            $viaNew  = WithJsonDTO::fromArray(['note' => $text, 'meta' => $text, 'textOrList' => $text] + self::base()->toArray());
            $this->assertTrue($viaWith->equals($viaNew), "Mismatch for '$text'");
        }
    }
}
