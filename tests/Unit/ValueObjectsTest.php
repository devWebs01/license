<?php

namespace DevWebs01\LicensingClient\Tests\Unit;

use DevWebs01\LicensingClient\Enums\LicenseStatus;
use DevWebs01\LicensingClient\Tests\TestCase;
use DevWebs01\LicensingClient\ValueObjects\ActivationResult;
use DevWebs01\LicensingClient\ValueObjects\LicenseInfo;
use DevWebs01\LicensingClient\ValueObjects\ValidationResult;

class ValueObjectsTest extends TestCase
{
    public function test_activation_result_constructor(): void
    {
        $result = new ActivationResult(
            success: true,
            offlineUntil: '2026-05-23T13:00:00Z',
            message: 'Sukses'
        );

        $this->assertTrue($result->success);
        $this->assertSame('2026-05-23T13:00:00Z', $result->offlineUntil);
        $this->assertSame('Sukses', $result->message);
    }

    public function test_validation_result_constructor(): void
    {
        $result = new ValidationResult(
            valid: true,
            status: LicenseStatus::Active,
            expiresAt: '2026-06-16',
        );

        $this->assertTrue($result->valid);
        $this->assertSame(LicenseStatus::Active, $result->status);
    }

    public function test_validation_result_from_array_active(): void
    {
        $result = ValidationResult::fromArray([
            'status' => 'active',
            'expires_at' => '2026-06-16',
        ]);

        $this->assertTrue($result->valid);
        $this->assertSame(LicenseStatus::Active, $result->status);
    }

    public function test_validation_result_from_array_suspended(): void
    {
        $result = ValidationResult::fromArray([
            'status' => 'suspended',
            'expires_at' => '2026-06-16',
        ]);

        $this->assertFalse($result->valid);
        $this->assertSame(LicenseStatus::Suspended, $result->status);
    }

    public function test_license_info_constructor(): void
    {
        $info = new LicenseInfo(
            isValid: true,
            status: LicenseStatus::Active,
            offlineUntil: '2026-05-23T13:00:00Z',
            isWithinGracePeriod: true,
            graceDaysRemaining: 3,
            product: 'Test App',
        );

        $this->assertTrue($info->isValid);
        $this->assertSame(LicenseStatus::Active, $info->status);
        $this->assertSame(3, $info->graceDaysRemaining);
        $this->assertTrue($info->isWithinGracePeriod);
    }

    public function test_license_status_blocking_values(): void
    {
        $this->assertFalse(LicenseStatus::Active->isBlocking());
        $this->assertFalse(LicenseStatus::GraceWarning->isBlocking());
        $this->assertTrue(LicenseStatus::Suspended->isBlocking());
        $this->assertTrue(LicenseStatus::Expired->isBlocking());
        $this->assertTrue(LicenseStatus::Revoked->isBlocking());
        $this->assertTrue(LicenseStatus::Locked->isBlocking());
        $this->assertTrue(LicenseStatus::NotActivated->isBlocking());
    }

    public function test_license_status_labels(): void
    {
        $this->assertSame('Active', LicenseStatus::Active->label());
        $this->assertSame('Suspended', LicenseStatus::Suspended->label());
        $this->assertSame('Locked', LicenseStatus::Locked->label());
        $this->assertSame('Not Activated', LicenseStatus::NotActivated->label());
    }
}
