<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class InvalidHTTPStatusException extends Exception
{
    public function __construct(int $statusCode)
    {
        $message = match ($statusCode) {
            404 => 'The page was not found (404). Please check the URL and try again.',
            403 => 'Access to this page is forbidden (403). The site may require authentication.',
            500, 502, 503 => 'The site is experiencing server errors ('.$statusCode.'). Please try again later.',
            default => 'The page returned an error (HTTP '.$statusCode.'). Please try a different URL.',
        };

        parent::__construct($message);
    }
}
