<?php

namespace DevWebs01\LicensingClient\Tests\Unit;

use DevWebs01\LicensingClient\Services\FingerprintCollector;
use DevWebs01\LicensingClient\Tests\TestCase;

class FingerprintCollectorTest extends TestCase
{
    private FingerprintCollector $collector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collector = new FingerprintCollector;
    }

    public function test_fingerprint_returns_64_char_hex_string(): void
    {
        $fingerprint = $this->collector->fingerprint();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $fingerprint);
    }

    public function test_fingerprint_is_consistent(): void
    {
        $first = $this->collector->fingerprint();
        $second = $this->collector->fingerprint();

        $this->assertSame($first, $second);
    }

    public function test_fingerprint_returns_same_as_collect(): void
    {
        $this->assertSame(
            $this->collector->fingerprint(),
            $this->collector->collect()
        );
    }
}
