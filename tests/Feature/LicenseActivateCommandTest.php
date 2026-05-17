<?php

namespace DevWebs01\LicensingClient\Tests\Feature;

use DevWebs01\LicensingClient\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class LicenseActivateCommandTest extends TestCase
{
    public function test_activate_command_success(): void
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

        $this->artisan('license:activate', ['key' => 'VALID-KEY'])
            ->assertSuccessful()
            ->expectsOutputToContain('berhasil');
    }

    public function test_activate_command_failure(): void
    {
        Http::fake([
            'raw.githubusercontent.com/*' => Http::response(null, 404),
        ]);

        $this->artisan('license:activate', ['key' => 'INVALID-KEY'])
            ->assertFailed();
    }

    public function test_status_command_runs(): void
    {
        $this->artisan('license:status')
            ->assertSuccessful();
    }
}
