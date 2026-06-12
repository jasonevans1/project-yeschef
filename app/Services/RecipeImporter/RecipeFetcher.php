<?php

declare(strict_types=1);

namespace App\Services\RecipeImporter;

use App\Exceptions\InvalidHTTPStatusException;
use App\Exceptions\NetworkTimeoutException;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class RecipeFetcher
{
    public function __construct(
        private readonly UrlSafetyValidator $guard = new UrlSafetyValidator
    ) {}

    /**
     * Fetch HTML content from a URL.
     *
     * The guard is invoked on the initial URL and on every redirect Location
     * before the hop is followed, preventing SSRF via open redirects.
     * Redirects are followed manually (Guzzle auto-redirect is disabled) so
     * that each hop can be validated.
     *
     * @throws \App\Exceptions\BlockedUrlException If the URL (or any redirect) is unsafe
     * @throws NetworkTimeoutException If the request times out
     * @throws InvalidHTTPStatusException If the final response status is not 2xx
     * @throws Exception If connection fails or redirect limit exceeded
     */
    public function fetch(string $url): string
    {
        if ($this->isLocalTestRoute($url)) {
            return $this->fetchLocalRoute($url);
        }

        $maxRedirects = config('recipe-import.max_redirects', 5);
        $hops = 0;
        $currentUrl = $url;

        try {
            while (true) {
                // Validate the current URL before fetching
                $this->guard->validate($currentUrl);

                $response = Http::timeout(30)
                    ->connectTimeout(10)
                    ->withOptions(['allow_redirects' => false])
                    ->withHeaders($this->browserHeaders())
                    ->get($currentUrl);

                $statusCode = $response->status();

                // Follow 3xx redirects manually
                if ($statusCode >= 300 && $statusCode <= 399) {
                    $location = $response->header('Location');

                    if (empty($location)) {
                        // 3xx with no Location — treat as a bad response
                        throw new InvalidHTTPStatusException($statusCode);
                    }

                    // Resolve relative Location against the current absolute URL
                    $currentUrl = $this->resolveUrl($location, $currentUrl);

                    $hops++;

                    if ($hops > $maxRedirects) {
                        throw new Exception(
                            'Could not connect to the site: too many redirects.'
                        );
                    }

                    continue;
                }

                // Non-redirect: validate status and return body
                if (! $response->successful()) {
                    throw new InvalidHTTPStatusException($statusCode);
                }

                return $response->body();
            }
        } catch (ConnectionException $e) {
            if (str_contains($e->getMessage(), 'timed out') ||
                str_contains($e->getMessage(), 'timeout')) {
                throw new NetworkTimeoutException;
            }

            throw new Exception(
                'Could not connect to the site. Please check your internet connection and try again.'
            );
        }
    }

    /**
     * Check if URL is a local test route (to avoid self-referential HTTP requests).
     */
    protected function isLocalTestRoute(string $url): bool
    {
        if (! app()->environment(['local', 'testing'])) {
            return false;
        }

        return str_contains($url, '/test/');
    }

    /**
     * Fetch content from a local route without making HTTP request.
     */
    protected function fetchLocalRoute(string $url): string
    {
        if (str_contains($url, '/test/recipe-valid')) {
            return <<<'HTML'
<html>
<head>
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Recipe",
    "name": "Test Chocolate Chip Cookies",
    "description": "Delicious test cookies for E2E testing",
    "prepTime": "PT15M",
    "cookTime": "PT12M",
    "recipeYield": "24 cookies",
    "recipeIngredient": [
      "2 cups all-purpose flour",
      "1 cup white sugar",
      "1/2 cup butter, softened",
      "2 eggs",
      "1 tsp vanilla extract",
      "1 tsp baking soda",
      "1/2 tsp salt",
      "2 cups chocolate chips"
    ],
    "recipeInstructions": "Preheat oven to 350°F. Mix butter and sugar. Add eggs and vanilla. Combine dry ingredients. Fold in chocolate chips. Drop by spoonfuls onto baking sheet. Bake 10-12 minutes.",
    "image": "https://example.com/cookies.jpg",
    "recipeCuisine": "American",
    "recipeCategory": "Dessert"
  }
  </script>
</head>
<body>Test Recipe Content</body>
</html>
HTML;
        }

        if (str_contains($url, '/test/recipe-invalid')) {
            return '<html><body>Just a regular page with no recipe data</body></html>';
        }

        return '<html><body></body></html>';
    }

    /**
     * Resolve a Location header value against the current (base) URL.
     *
     * Handles absolute URLs unchanged and resolves relative paths
     * (both root-relative and relative) against the base URL scheme + host.
     */
    private function resolveUrl(string $location, string $baseUrl): string
    {
        // Already absolute
        if (preg_match('#^https?://#i', $location)) {
            return $location;
        }

        $parsed = parse_url($baseUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';

        // Root-relative path (starts with /)
        if (str_starts_with($location, '/')) {
            return $scheme.'://'.$host.$port.$location;
        }

        // Protocol-relative (starts with //)
        if (str_starts_with($location, '//')) {
            return $scheme.':'.$location;
        }

        // Relative to the current path directory
        $basePath = $parsed['path'] ?? '/';
        $dir = rtrim(dirname($basePath), '/');

        return $scheme.'://'.$host.$port.$dir.'/'.$location;
    }

    /**
     * Returns the browser-like headers sent on every request.
     *
     * @return array<string, string>
     */
    private function browserHeaders(): array
    {
        return [
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
        ];
    }
}
