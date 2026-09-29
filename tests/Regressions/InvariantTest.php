<?php

declare (strict_types = 1);

namespace Tests\Regressions;

use Benchmarks\DataTransferObjects\Order;
use Benchmarks\Enums\OrderStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\ImmutableBase;
use Tests\Attacks\Objects\DeepNesting\CompanyDTO;
use Tests\Attacks\Objects\DeepNesting\OrderDTO;
use Tests\Attacks\Objects\DeepNesting\ValidatedOrderVO;
use Tests\Regressions\Objects\Level;
use Tests\Regressions\Objects\TypeErrorFreeDTO;
use Tests\Regressions\Objects\WithJsonDTO;

/**
 * Properties that must hold for every object, whatever its shape. Unlike the
 * per-feature tests, these catch divergence between the construction,
 * mutation and serialization paths:
 *
 *   - fromArray(x->toArray()) equals x, and likewise through JSON;
 *   - x->with(y->toArray()) equals y (with() resolves like fromArray());
 *   - a deep-path with() equals rebuilding from the edited array.
 */
class InvariantTest extends TestCase
{
    /** @return array<string, array{class-string<ImmutableBase>, array, array}> */
    public static function objects(): array
    {
        $address = static fn(string $city) => ['street' => '1 Main St', 'city' => $city, 'country' => 'TW', 'zipCode' => null];
        $line    = static fn(int $i) => ['sku' => "SKU$i", 'productName' => 'p', 'quantity' => $i, 'unitPrice' => 100, 'totalPrice' => 100 * $i];
        $item    = static fn(string $sku, int|float $price) => ['sku' => $sku, 'quantity' => 2, 'price' => $price];
        $person  = static fn(string $name) => [
            'name'    => $name,
            'email'   => strtolower($name) . '@example.com',
            'address' => ['street' => 's', 'city' => 'c', 'zipCode' => '100'],
        ];

        return [
            'benchmark Order (enum, VO, SVO, ArrayOf, nullable)' => [
                Order::class,
                ['id' => 'A', 'status' => OrderStatus::PENDING, 'customer' => ['name' => 'Ann', 'email' => 'ann@example.com', 'billingAddress' => $address('Taipei'), 'shippingAddress' => null], 'items' => [$line(1), $line(2)], 'metadata' => ['k' => 'v']],
                ['id' => 'B', 'status' => 'shipped', 'customer' => ['name' => 'Bob', 'email' => 'bob@example.com', 'billingAddress' => $address('Tainan'), 'shippingAddress' => $address('Hsinchu')], 'items' => [$line(3)], 'metadata' => null],
            ],
            'nested DTOs with float widening' => [
                OrderDTO::class,
                ['orderId' => 'O1', 'customer' => $person('Ann'), 'items' => [$item('a', 1.5)], 'note' => null],
                ['orderId' => 'O2', 'customer' => $person('Bob'), 'items' => [$item('b', 3), $item('c', 0.25)], 'note' => '[draft] rush'],
            ],
            'ArrayOf of nested DTOs' => [
                CompanyDTO::class,
                ['name' => 'Acme', 'employees' => [$person('Ann')]],
                ['name' => 'Initech', 'employees' => [$person('Bob'), $person('Cid')]],
            ],
            'validated VO' => [
                ValidatedOrderVO::class,
                ['orderId' => 'V1', 'items' => [$item('a', 2.0)]],
                ['orderId' => 'V2', 'items' => [$item('b', 1), $item('c', 4.5)]],
            ],
            'unions, enums, literal false' => [
                TypeErrorFreeDTO::class,
                ['svoOrInt' => 'x', 'items' => ['code' => 'c'], 'nullableUnion' => ['sku' => 's'], 'list' => [['sku' => 'a']], 'levels' => [Level::Low, 'high'], 'level' => 'DEFAULT', 'stringOrFalse' => false],
                ['svoOrInt' => 7, 'items' => null, 'nullableUnion' => null, 'list' => [], 'levels' => [], 'level' => null, 'stringOrFalse' => 'text'],
            ],
            'JSON-looking strings' => [
                WithJsonDTO::class,
                ['note' => '[draft]', 'meta' => '{"a":1}', 'raw' => [1, [2]], 'rawOrInt' => 3, 'textOrList' => '[x]', 'label' => '[y]', 'items' => [['sku' => 'a']]],
                ['note' => '{}', 'meta' => ['nested' => [true]], 'raw' => ['k' => null], 'rawOrInt' => [1], 'textOrList' => ['a'], 'label' => null, 'items' => []],
            ],
        ];
    }

    #[DataProvider('objects')]
    public function testArrayRoundTrip(string $class, array $a, array $b): void
    {
        foreach ([$a, $b] as $data) {
            $x = $class::fromArray($data);
            $this->assertTrue($class::fromArray($x->toArray())->equals($x));
        }
    }

    #[DataProvider('objects')]
    public function testJsonRoundTrip(string $class, array $a, array $b): void
    {
        foreach ([$a, $b] as $data) {
            $x = $class::fromArray($data);
            $this->assertTrue($class::fromJson($x->toJson())->equals($x));
        }
    }

    #[DataProvider('objects')]
    public function testWithMatchesFromArray(string $class, array $a, array $b): void
    {
        $x = $class::fromArray($a);
        $y = $class::fromArray($b);
        $this->assertTrue($x->with($y->toArray())->equals($y));
        $this->assertTrue($y->with($x->toArray())->equals($x));
        $this->assertTrue($x->with([])->equals($x));
    }

    public function testDeepPathWithMatchesRebuild(): void
    {
        [, $a] = self::objects()['benchmark Order (enum, VO, SVO, ArrayOf, nullable)'];
        $order = Order::fromArray($a);

        $edited                                     = $order->toArray();
        $edited['customer']['billingAddress']['city'] = 'Kaohsiung';
        $edited['items'][1]['quantity']             = 9;

        $viaWith = $order->with(['customer.billingAddress.city' => 'Kaohsiung', 'items[1].quantity' => 9]);
        $this->assertTrue($viaWith->equals(Order::fromArray($edited)));
    }
}
