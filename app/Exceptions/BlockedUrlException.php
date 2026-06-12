<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class BlockedUrlException extends Exception
{
    public function __construct(string $message = 'This URL is not allowed.')
    {
        parent::__construct($message);
    }
}
