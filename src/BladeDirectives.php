<?php

namespace DevWebs01\LicensingClient;

use DevWebs01\LicensingClient\Facades\LicenseClient;
use Illuminate\Support\Facades\Blade;

final class BladeDirectives
{
    public static function register(): void
    {
        Blade::if('licensed', function () {
            return LicenseClient::isValid();
        });

        Blade::if('licenseWarning', function () {
            return session()->has('license_warning');
        });
    }
}
