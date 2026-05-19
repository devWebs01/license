<?php

declare(strict_types=1);

namespace DevWebs01\LicensingClient\Exceptions;

use Exception;

final class ServerUnreachableException extends Exception
{
    public function __construct(string $message = 'Server lisensi tidak reachable')
    {
        parent::__construct($message);
    }
}
