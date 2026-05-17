<?php

namespace DevWebs01\LicensingClient\ValueObjects;

readonly class ActivationResult
{
    public function __construct(
        public bool $success,
        public ?string $offlineUntil = null,
        public ?string $message = null,
    ) {}
}
