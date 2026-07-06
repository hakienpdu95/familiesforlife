<?php

namespace App\Foundation\Exceptions;

use RuntimeException;

class TenantNotSetException extends RuntimeException
{
    public function __construct(string $message = 'No tenant (Organization) has been resolved for this request.')
    {
        parent::__construct($message);
    }
}
