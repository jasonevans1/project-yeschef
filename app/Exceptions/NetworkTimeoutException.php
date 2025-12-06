<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class NetworkTimeoutException extends Exception
{
    public function __construct()
    {
        parent::__construct(
            'The request timed out after 30 seconds. The site may be slow or unavailable. Please try again later.'
        );
    }
}
