<?php

namespace DevWebs01\LicensingClient\Tests\Unit;

use DevWebs01\LicensingClient\Enums\LicenseStatus;
use DevWebs01\LicensingClient\Services\FingerprintCollector;
use DevWebs01\LicensingClient\Services\LicenseCacheService;
use DevWebs01\LicensingClient\Services\LicenseClientService;
use DevWebs01\LicensingClient\Tests\TestCase;
use DevWebs01\LicensingClient\ValueObjects\ActivationResult;
use DevWebs01\LicensingClient\ValueObjects\ValidationResult;
use Illuminate\Support\Facades\Http;

class LicenseClientServiceTest extends TestCase
{
    private LicenseClientService $service;

    private LicenseCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheService = new LicenseCacheService(
            cacheStore: 'array'
        );

        $this->service = new LicenseClientService(
            cache: $this->cacheService,
            fingerprint: new FingerprintCollector,
            githubRawBase: 'https://raw.githubusercontent.com/org/repo/main',
            licenseKey: 'TEST-ABCD-EFGH-1234',
            appName: 'Test App',
            graceDays: 7,
        );
    }

    public function test_activate_returns_success_when_github_json_valid(): void
    {
        Http::fake([
            'raw.githubusercontent.com/*' => Http::response([
                'license_hash' => sha1('VALID-KEY'),
                'status' => 'active',
                'expires_at' => now()->addMonths(6)->toDateString(),
                'max_devices' => 3,
                'updated_at' => now()->toIso8601String(),
            ]),
        ]);

        $result = $this->service->activate('VALID-KEY');

        $this->assertInstanceOf(ActivationResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertNotNull($result->offlineUntil);
    }

    public function test_activate_returns_failure_when_github_json_not_found(): void
    {
        Http::fake([
            'raw.githubusercontent.com/*' => Http::response(null, 404),
        ]);

        $result = $this->service->activate('INVALID-KEY');

        $this->assertFalse($result->success);
    }

    public function test_activate_returns_failure_when_expired(): void
    {
        Http::fake([
            'raw.githubusercontent.com/*' => Http::response([
                'license_hash' => sha1('EXPIRED-KEY'),
                'status' => 'active',
                'expires_at' => now()->subDays(1)->toDateString(),
                'max_devices' => 3,
                'updated_at' => now()->toIso8601String(),
            ]),
        ]);

        $result = $this->service->activate('EXPIRED-KEY');

        $this->assertFalse($result->success);
    }

    public function test_activate_returns_failure_when_status_not_active(): void
    {
        Http::fake([
            'raw.githubusercontent.com/*' => Http::response([
                'license_hash' => sha1('SUSPENDED-KEY'),
                'status' => 'suspended',
                'expires_at' => now()->addMonths(6)->toDateString(),
                'max_devices' => 3,
                'updated_at' => now()->toIso8601String(),
            ]),
        ]);

        $result = $this->service->activate('SUSPENDED-KEY');

        $this->assertFalse($result->success);
    }

    public function test_sync_returns_valid_when_github_json_valid(): void
    {
        $this->cacheService->storeToken([
            'license_key' => 'TEST-ABCD-EFGH-1234',
            'fingerprint' => (new FingerprintCollector)->fingerprint(),
            'status' => 'active',
            'offline_until' => now()->addDays(7)->toIso8601String(),
            'server_time' => now()->toIso8601String(),
        ]);

        Http::fake([
            'raw.githubusercontent.com/*' => Http::response([
                'license_hash' => sha1('TEST-ABCD-EFGH-1234'),
                'status' => 'active',
                'expires_at' => now()->addMonths(6)->toDateString(),
                'max_devices' => 3,
                'updated_at' => now()->toIso8601String(),
            ]),
        ]);

        $result = $this->service->sync();

        $this->assertInstanceOf(ValidationResult::class, $result);
        $this->assertTrue($result->valid);
        $this->assertSame(LicenseStatus::Active, $result->status);
    }

    public function test_sync_returns_invalid_when_github_json_not_found(): void
    {
        $this->cacheService->storeToken([
            'license_key' => 'TEST-ABCD-EFGH-1234',
            'fingerprint' => (new FingerprintCollector)->fingerprint(),
            'status' => 'active',
            'offline_until' => now()->addDays(7)->toIso8601String(),
            'server_time' => now()->toIso8601String(),
        ]);

        Http::fake([
            'raw.githubusercontent.com/*' => Http::response(null, 404),
        ]);

        $result = $this->service->sync();

        $this->assertFalse($result->valid);
        $this->assertFalse($this->cacheService->hasToken());
    }

    public function test_sync_returns_invalid_when_expired(): void
    {
        $this->cacheService->storeToken([
            'license_key' => 'TEST-ABCD-EFGH-1234',
            'fingerprint' => (new FingerprintCollector)->fingerprint(),
            'status' => 'active',
            'offline_until' => now()->addDays(7)->toIso8601String(),
            'server_time' => now()->toIso8601String(),
        ]);

        Http::fake([
            'raw.githubusercontent.com/*' => Http::response([
                'license_hash' => sha1('TEST-ABCD-EFGH-1234'),
                'status' => 'expired',
                'expires_at' => now()->subDays(1)->toDateString(),
                'max_devices' => 3,
                'updated_at' => now()->toIso8601String(),
            ]),
        ]);

        $result = $this->service->sync();

        $this->assertFalse($result->valid);
        $this->assertFalse($this->cacheService->hasToken());
    }

    public function test_status_uses_memory_cache_within_request(): void
    {
        $this->cacheService->storeStatus('active', true, now()->addDays(7)->toIso8601String());

        $info1 = $this->service->status();
        $info2 = $this->service->status();

        $this->assertTrue($info1->isValid);
        $this->assertTrue($info2->isValid);
    }

    public function test_deactivate_clears_cache(): void
    {
        $this->cacheService->storeToken([
            'license_key' => 'TEST-ABCD-EFGH-1234',
            'fingerprint' => 'abc123',
            'status' => 'active',
            'offline_until' => now()->addDays(7)->toIso8601String(),
        ]);

        $result = $this->service->deactivate();

        $this->assertTrue($result);
        $this->assertFalse($this->cacheService->hasToken());
    }

    public function test_status_returns_not_activated_when_no_cache(): void
    {
        $info = $this->service->status();

        $this->assertFalse($info->isValid);
        $this->assertSame(LicenseStatus::NotActivated, $info->status);
    }

    public function test_status_returns_active_when_status_cache_valid(): void
    {
        $this->cacheService->storeStatus('active', true, now()->addDays(7)->toIso8601String());

        $info = $this->service->status();

        $this->assertTrue($info->isValid);
    }

    public function test_is_valid_returns_false_when_no_status(): void
    {
        $this->assertFalse($this->service->isValid());
    }

    public function test_is_valid_returns_true_when_status_valid(): void
    {
        $this->cacheService->storeStatus('active', true, now()->addDays(7)->toIso8601String());

        $this->assertTrue($this->service->isValid());
    }
}
