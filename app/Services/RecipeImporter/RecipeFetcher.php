<?php

namespace App\Services\RecipeImporter;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class RecipeFetcher
{
    /**
     * Fetch HTML content from a URL.
     *
     * @param  string  $url  The URL to fetch
     * @return string The HTML content (returns body for all HTTP status codes)
     *
     * @throws ConnectionException If timeout or network error occurs
     */
    public function fetch(string $url): string
    {
        $response = Http::timeout(30)
            ->connectTimeout(10)
            ->get($url);

        return $response->body();
    }
}
