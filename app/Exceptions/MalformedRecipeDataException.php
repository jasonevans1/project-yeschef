<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class MalformedRecipeDataException extends Exception
{
    public function __construct(string $reason = '', string $message = 'The recipe data on this page is malformed or incomplete.')
    {
        if ($reason) {
            $message .= ' '.$reason;
        }

        parent::__construct($message);
    }

    public static function forFile(string $reason = ''): self
    {
        return new self($reason, 'The recipe file is malformed or incomplete.');
    }
}
