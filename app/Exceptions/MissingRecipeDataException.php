<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class MissingRecipeDataException extends Exception
{
    public function __construct()
    {
        parent::__construct(
            'No recipe data found on this page. The site may not use standard schema.org markup. Try copying the recipe manually or use a different source.'
        );
    }
}
