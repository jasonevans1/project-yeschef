<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class MalformedRecipeDataException extends Exception
{
    public function __construct(string $reason = '')
    {
        $message = 'The recipe data on this page is malformed or incomplete.';

        if ($reason) {
            $message .= ' '.$reason;
        }

        parent::__construct($message);
    }
}
