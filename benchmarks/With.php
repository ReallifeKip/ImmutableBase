<?php

declare (strict_types = 1);

namespace ImmutableBase\Benchmarks;

use Benchmarks\DataTransferObjects\NestedDTO;
use Benchmarks\DataTransferObjects\Order;
use Benchmarks\DataTransferObjects\SimpleDTO;
use Benchmarks\Enums\OrderStatus;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use ReallifeKip\ImmutableBase\ImmutableBase;

/**
 * Benchmark suite for with() method.
 *
 * Run independently:
 *   vendor/bin/phpbench run benchmarks/WithBench.php --report=default
 *
 * Compare before/after optimization:
 *   vendor/bin/phpbench run benchmarks/WithBench.php --tag=before
 *   (apply changes)
 *   vendor/bin/phpbench run benchmarks/WithBench.php --tag=after --ref=before --report=compare
 */
#[BeforeMethods('setUp')]
#[Warmup(3)]
#[Iterations(10)]
#[Revs(100)]
class WithBench
{
    private SimpleDTO $simpleDTO;
    private NestedDTO $nestedDTO;
    private Order $order;

    private array $simpleData;
    private array $nestedData;
    private array $orderData;

    public function setUp(): void
    {
        ImmutableBase::loadCache();

        $this->simpleData = [
            'name'     => 'Alice',
            'age'      => 30,
            'isActive' => true,
        ];

        $this->nestedData = [
            'title' => 'root',
            'items' => array_map(
                fn(int $i) => $this->simpleData,
                range(1, 50)
            ),
        ];

        $this->orderData = [
            'id'       => 'ORD-001',
            'status'   => OrderStatus::PROCESSING,
            'customer' => [
                'name'            => 'Bob',
                'email'           => 'bob@example.com',
                'billingAddress'  => [
                    'street'  => '123 Main St',
                    'city'    => 'Taipei',
                    'country' => 'Taiwan',
                    'zipCode' => '100',
                ],
                'shippingAddress' => [
                    'street'  => '123 Main St',
                    'city'    => 'Taipei',
                    'country' => 'Taiwan',
                    'zipCode' => '100',
                ],
            ],
            'items'    => array_map(fn(int $i) => [
                'sku'         => "SKU$i",
                'productName' => 'example',
                'quantity'    => $i,
                'unitPrice'   => $i * 100,
                'totalPrice'  => $i * 100,
            ], range(1, 50)),
        ];

        $this->simpleDTO = SimpleDTO::fromArray($this->simpleData);
        $this->nestedDTO = NestedDTO::fromArray($this->nestedData);
        $this->order     = Order::fromArray($this->orderData);
    }

    // ─── Flat scalar updates ─────────────────────────────────

    /** Single scalar property on a flat DTO. */
    public function benchWithScalar(): void
    {
        $this->simpleDTO->with(['name' => 'Bob']);
    }

    /** Multiple scalar properties on a flat DTO. */
    public function benchWithMultipleScalars(): void
    {
        $this->simpleDTO->with(['name' => 'Bob', 'age' => 25]);
    }

    // ─── Dot-notation deep updates ───────────────────────────

    /** One level deep via dot notation. */
    public function benchWithDotOneLevelDeep(): void
    {
        $this->order->with(['customer.name' => 'Charlie']);
    }

    /** Two levels deep via dot notation. */
    public function benchWithDotTwoLevelsDeep(): void
    {
        $this->order->with(['customer.billingAddress.city' => 'Kaohsiung']);
    }

    /** Multiple deep paths in a single with() call. */
    public function benchWithDotMultiplePaths(): void
    {
        $this->order->with([
            'customer.name'                => 'Charlie',
            'customer.billingAddress.city' => 'Kaohsiung',
        ]);
    }

    // ─── Bracket notation ────────────────────────────────────

    /** Bracket notation equivalent of dot path. */
    public function benchWithBracketNotation(): void
    {
        $this->order->with(['customer[shippingAddress][city]' => 'Tainan']);
    }

    // ─── Nested VO / SVO replacement ─────────────────────────

    /** Replace an entire nested VO subtree. */
    public function benchWithNestedVOReplacement(): void
    {
        $this->order->with([
            'customer' => [
                'name'            => 'Dave',
                'email'           => 'dave@example.com',
                'billingAddress'  => [
                    'street'  => '456 Other St',
                    'city'    => 'Tainan',
                    'country' => 'Taiwan',
                    'zipCode' => '700',
                ],
                'shippingAddress' => [
                    'street'  => '456 Other St',
                    'city'    => 'Tainan',
                    'country' => 'Taiwan',
                    'zipCode' => '700',
                ],
            ],
        ]);
    }

    // ─── Chained with() ──────────────────────────────────────

    /** Two consecutive with() calls simulating real usage. */
    public function benchWithChained(): void
    {
        $this->order
            ->with(['status' => OrderStatus::SHIPPED])
            ->with(['customer.shippingAddress.city' => 'Hsinchu']);
    }

    // ─── with() + toArray() roundtrip ────────────────────────

    /** with() followed by toArray() — common API response pattern. */
    public function benchWithThenToArray(): void
    {
        $this->order->with(['customer.shippingAddress.city' => 'Taichung'])->toArray();
    }

    // ─── Batch: 100× with() on same object ───────────────────

    /**
     * Simulate repeated updates (e.g., event sourcing, reducer).
     * Each iteration produces a new immutable instance.
     */
    #[Revs(10)]
    public function benchWithBatch100(): void
    {
        $obj = $this->simpleDTO;
        for ($i = 0; $i < 100; $i++) {
            $obj = $obj->with(['age' => $i]);
        }
    }
}
