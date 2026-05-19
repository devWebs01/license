<?php

namespace DevWebs01\LicensingClient\Tests\Feature;

use DevWebs01\LicensingClient\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class ActivationWizardTest extends TestCase
{
    public function test_activate_page_returns_welcome_screen(): void
    {
        $response = $this->get(route('licensing.activate'));

        $response->assertOk();
        $response->assertSee('Aktivasi Lisensi');
        $response->assertSee('license_key');
        $response->assertSee('Fingerprint');
    }

    public function test_activate_post_with_valid_key_succeeds(): void
    {
        Http::fake([
            'raw.githubusercontent.com/*' => Http::response([
                'license_hash' => sha1('TEST-ABCD-EFGH-1234'),
                'status' => 'active',
                'expires_at' => now()->addMonths(6)->toDateString(),
                'max_devices' => 3,
                'updated_at' => now()->toIso8601String(),
            ]),
        ]);

        $response = $this->post(route('licensing.activate'), [
            'license_key' => 'TEST-ABCD-EFGH-1234',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('success');
    }

    public function test_activate_post_with_invalid_key_fails(): void
    {
        Http::fake([
            'raw.githubusercontent.com/*' => Http::response(null, 404),
        ]);

        $response = $this->post(route('licensing.activate'), [
            'license_key' => 'INVALID-KEY',
        ]);

        $response->assertSessionHasErrors(['license_key']);
    }

    public function test_activate_post_with_empty_key_returns_error(): void
    {
        $response = $this->post(route('licensing.activate'), [
            'license_key' => '',
        ]);

        $response->assertSessionHasErrors(['license_key']);
    }

    public function test_locked_page_renders(): void
    {
        $response = $this->get(route('licensing.locked', ['reason' => 'expired']));

        $response->assertOk();
        $response->assertSee('Akses Diblokir');
    }

    public function test_status_page_renders(): void
    {
        $response = $this->get(route('licensing.status'));

        $response->assertOk();
    }

    public function test_activate_post_with_suspended_key_fails(): void
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

        $response = $this->post(route('licensing.activate'), [
            'license_key' => 'SUSPENDED-KEY',
        ]);

        $response->assertSessionHasErrors(['license_key']);
    }
}
