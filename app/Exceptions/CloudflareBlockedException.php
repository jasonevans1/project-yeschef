<?php

namespace App\Exceptions;

use Exception;

class CloudflareBlockedException extends Exception
{
    public function __construct(string $message = 'This site is protected by Cloudflare and cannot be imported automatically. Please try copying the recipe manually or use a different URL.')
    {
        parent::__construct($message);
    }
}
