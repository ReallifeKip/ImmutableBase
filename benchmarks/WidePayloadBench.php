<?php

declare (strict_types = 1);

namespace Benchmarks;

use Benchmarks\DataTransferObjects\TargetDTO;
use Benchmarks\DataTransferObjects\WideFlatDTO;

require_once dirname(__DIR__) . '/vendor/autoload.php';

/**
 * @Revs(100000)
 * @Iterations(5)
 * @OutputTimeUnit("milliseconds")
 */
class WidePayloadBench
{
    private array $noisePayload;
    private array $widePayload;

    public function __construct()
    {
        $this->noisePayload = [];
        for ($i = 0; $i < 2000; $i++) {
            $key                      = 'unused_field_' . $i;
            $this->noisePayload[$key] = "rubbish_data_{$i}";
        }
        $this->noisePayload['id']       = 101;
        $this->noisePayload['name']     = 'Kip';
        $this->noisePayload['email']    = 'kip@bench.mark';
        $this->noisePayload['isActive'] = true;
        $this->noisePayload['score']    = 99.9;

        $this->widePayload = [];
        for ($i = 0; $i < 100; $i++) {
            $this->widePayload["col_{$i}"] = "value_{$i}";
        }
    }

    /**
     * @Revs(1)
     * @Iterations(50)
     */
    public function benchNoiseFiltering(): void
    {
        TargetDTO::fromArray($this->noisePayload);
    }

    /**
     * @Revs(1)
     * @Iterations(50)
     */
    public function benchWideFlattened(): void
    {
        WideFlatDTO::fromArray($this->widePayload);
    }
}
