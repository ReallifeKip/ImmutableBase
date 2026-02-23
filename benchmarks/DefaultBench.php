<?php

declare (strict_types = 1);
namespace Benchmarks;

use Benchmarks\DataTransferObjects\NestedDTO;
use Benchmarks\DataTransferObjects\Order;
use Benchmarks\DataTransferObjects\SimpleDTO;

require_once dirname(__DIR__) . '/vendor/autoload.php';
use ReallifeKip\ImmutableBase\ImmutableBase;

if (file_exists('cache.php')) {
    ImmutableBase::loadCache('cache.php');
}

/**
 * @Revs(1000)
 * @Iterations(5)
 * @OutputTimeUnit("milliseconds")
 */
class DefaultBench
{
    private array $simplePayload;
    private array $nestedPayload;
    private array $orderPayload;

    public function __construct()
    {
        $this->simplePayload = [
            'name'     => 'Kip',
            'age'      => 30,
            'isActive' => true,
        ];

        $items = [];
        for ($i = 0; $i < 50; $i++) {
            $items[] = [
                'name'     => "Item $i",
                'age'      => $i,
                'isActive' => ($i % 2 === 0),
            ];
        }

        $this->nestedPayload = [
            'title' => 'Benchmark Test Payload',
            'items' => $items,
        ];

        $items = [];
        for ($i = 0; $i < 50; $i++) {
            $items[] = [
                'sku'         => 'ITEM' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'productName' => "Product Bench $i",
                'quantity'    => rand(1, 10),
                'unitPrice'   => rand(100, 5000),
                'totalPrice'  => rand(100, 5000),
            ];
        }

        $this->orderPayload = [
            'id'       => 'ORD-' . uniqid(),
            'status'   => 'processing',
            'metadata' => ['source' => 'web', 'campaign' => 'black_friday'],
            'customer' => [
                'name'            => 'Kip The Architect',
                'email'           => 'kip@example.com',
                'billingAddress'  => [
                    'street'  => '123 Code Ave',
                    'city'    => 'Tech City',
                    'country' => 'TW',
                    'zipCode' => '404',
                ],
                'shippingAddress' => null,
            ],
            'items'    => $items,
        ];
    }
    /**
     * @Revs(1)
     * @Iterations(50)
     */
    public function benchSimpleHydration(): void
    {
        SimpleDTO::fromArray($this->simplePayload);
    }

    public function benchNestedArrayOfHydration(): void
    {
        NestedDTO::fromArray($this->nestedPayload);
    }
    /**
     * @Revs(1)
     * @Iterations(50)
     */
    public function benchBulkSimpleHydration(): void
    {
        for ($i = 0; $i < 1000; $i++) {
            SimpleDTO::fromArray($this->simplePayload);
        }
    }

    public function benchRealisticHydration(): void
    {
        Order::fromArray($this->orderPayload);
    }

    public function benchBulkRealisticHydration(): void
    {
        for ($i = 0; $i < 100; $i++) {
            Order::fromArray($this->orderPayload);
        }
    }
}
