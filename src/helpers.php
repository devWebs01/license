<?php

use DevWebs01\LicensingClient\Facades\LicenseClient;
use DevWebs01\LicensingClient\ValueObjects\LicenseInfo;

if (! function_exists('license_is_valid')) {
    function license_is_valid(): bool
    {
        return LicenseClient::isValid();
    }
}

if (! function_exists('license_has_feature')) {
    function license_has_feature(string $feature): bool
    {
        return LicenseClient::hasFeature($feature);
    }
}

if (! function_exists('license_info')) {
    function license_info(): LicenseInfo
    {
        return LicenseClient::info();
    }
}
