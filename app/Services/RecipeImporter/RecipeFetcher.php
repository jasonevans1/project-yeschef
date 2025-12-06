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
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Sec-Fetch-User' => '?1',
                'Upgrade-Insecure-Requests' => '1',
            ])
            ->get($url);

        return $response->body();
    }
}
