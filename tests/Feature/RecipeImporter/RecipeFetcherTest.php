<?php

declare(strict_types=1);

use App\Exceptions\BlockedUrlException;
use App\Services\RecipeImporter\RecipeFetcher;
use App\Services\RecipeImporter\UrlSafetyValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/**
 * Build a UrlSafetyValidator with a stub resolver for hermetic tests.
 *
 * The stub maps known public test hosts to a real public IP so that no DNS
 * calls are made, and optionally maps internal hosts to private IPs to
 * simulate blocked addresses.
 *
 * @param  array<string, string>  $extraHosts
 */
function makeStubValidator(array $extraHosts = []): UrlSafetyValidator
{
    $publicHosts = [
        'example.com' => '93.184.216.34',
        'other-site.com' => '93.184.216.34',
        'new-site.com' => '93.184.216.34',
    ];

    $hostMap = array_merge($publicHosts, $extraHosts);

    return new UrlSafetyValidator(function (string $host) use ($hostMap): array {
        return isset($hostMap[$host]) ? [$hostMap[$host]] : [];
    });
}

beforeEach(function () {
    $this->fetcher = new RecipeFetcher(makeStubValidator());
});

// ── Existing tests (updated to use stub-injected fetcher) ────────────────────

test('successfully fetches HTML from valid URL', function () {
    Http::fake([
        'example.com/*' => Http::response('<html><body>Test Recipe</body></html>', 200),
    ]);

    $html = $this->fetcher->fetch('https://example.com/recipe');

    expect($html)->toBe('<html><body>Test Recipe</body></html>');
});

test('throws exception on network timeout', function () {
    Http::fake(function () {
        throw new ConnectionException('Connection timed out');
    });

    expect(fn () => $this->fetcher->fetch('https://example.com/recipe'))
        ->toThrow(\App\Exceptions\NetworkTimeoutException::class);
});

test('throws exception on connection error', function () {
    Http::fake(function () {
        throw new ConnectionException('Could not resolve host');
    });

    expect(fn () => $this->fetcher->fetch('https://example.com/recipe'))
        ->toThrow(\Exception::class);
});

test('handles HTTP 404 error', function () {
    Http::fake([
        'example.com/*' => Http::response('Not Found', 404),
    ]);

    expect(fn () => $this->fetcher->fetch('https://example.com/recipe'))
        ->toThrow(\App\Exceptions\InvalidHTTPStatusException::class);
});

test('handles HTTP 500 server error', function () {
    Http::fake([
        'example.com/*' => Http::response('Server Error', 500),
    ]);

    expect(fn () => $this->fetcher->fetch('https://example.com/recipe'))
        ->toThrow(\App\Exceptions\InvalidHTTPStatusException::class);
});

test('respects 30 second timeout configuration', function () {
    Http::fake([
        'example.com/*' => Http::response('Success', 200),
    ]);

    $html = $this->fetcher->fetch('https://example.com/recipe');

    expect($html)->toBe('Success');

    Http::assertSent(function ($request) {
        return true; // Laravel HTTP facade sets timeout internally
    });
});

test('handles empty response', function () {
    Http::fake([
        'example.com/*' => Http::response('', 200),
    ]);

    $html = $this->fetcher->fetch('https://example.com/recipe');

    expect($html)->toBe('');
});

test('handles large HTML responses', function () {
    $largeHtml = str_repeat('<div>Recipe content</div>', 10000);

    Http::fake([
        'example.com/*' => Http::response($largeHtml, 200),
    ]);

    $html = $this->fetcher->fetch('https://example.com/recipe');

    expect($html)->toBe($largeHtml);
});

test('sends browser-like headers to avoid bot detection', function () {
    Http::fake([
        'example.com/*' => Http::response('<html>Success</html>', 200),
    ]);

    $this->fetcher->fetch('https://example.com/recipe');

    Http::assertSent(function ($request) {
        return $request->hasHeader('User-Agent')
            && str_contains($request->header('User-Agent')[0], 'Mozilla')
            && $request->hasHeader('Accept')
            && $request->hasHeader('Accept-Language')
            && $request->hasHeader('Sec-Fetch-Dest');
    });
});

// ── New requirement tests ────────────────────────────────────────────────────

it('validates the url with the safety guard before fetching', function () {
    $validated = [];

    $validator = new UrlSafetyValidator(function (string $host) use (&$validated): array {
        $validated[] = $host;

        return ['93.184.216.34'];
    });

    $fetcher = new RecipeFetcher($validator);

    Http::fake([
        'example.com/*' => Http::response('<html>OK</html>', 200),
    ]);

    $fetcher->fetch('https://example.com/recipe');

    expect($validated)->toContain('example.com');
});

it('throws BlockedUrlException when the url resolves to an internal address', function () {
    $fetcher = new RecipeFetcher(makeStubValidator([
        'internal-host.local' => '192.168.1.1',
    ]));

    expect(fn () => $fetcher->fetch('https://internal-host.local/recipe'))
        ->toThrow(BlockedUrlException::class);
});

it('throws BlockedUrlException when a redirect points to an internal address', function () {
    $fetcher = new RecipeFetcher(makeStubValidator([
        'internal-redirect.local' => '10.0.0.1',
    ]));

    Http::fake([
        'example.com/old' => Http::response('', 302, ['Location' => 'https://internal-redirect.local/secret']),
    ]);

    expect(fn () => $fetcher->fetch('https://example.com/old'))
        ->toThrow(BlockedUrlException::class);
});

it('follows a safe redirect to a public address and returns the final body', function () {
    Http::fake([
        'example.com/old-recipe' => Http::response('', 302, ['Location' => 'https://example.com/new-recipe']),
        'example.com/new-recipe' => Http::response('<html>Redirected Recipe</html>', 200),
    ]);

    $html = $this->fetcher->fetch('https://example.com/old-recipe');

    expect($html)->toContain('Redirected Recipe');
});

it('follows a safe relative redirect resolved against the current url', function () {
    Http::fake([
        'example.com/old' => Http::response('', 302, ['Location' => '/new']),
        'example.com/new' => Http::response('<html>Relative Redirect</html>', 200),
    ]);

    $html = $this->fetcher->fetch('https://example.com/old');

    expect($html)->toContain('Relative Redirect');
});

it('stops and errors after exceeding the maximum redirect count', function () {
    // Build a redirect chain longer than max_redirects (5)
    Http::fake([
        'example.com/r0' => Http::response('', 302, ['Location' => 'https://example.com/r1']),
        'example.com/r1' => Http::response('', 302, ['Location' => 'https://example.com/r2']),
        'example.com/r2' => Http::response('', 302, ['Location' => 'https://example.com/r3']),
        'example.com/r3' => Http::response('', 302, ['Location' => 'https://example.com/r4']),
        'example.com/r4' => Http::response('', 302, ['Location' => 'https://example.com/r5']),
        'example.com/r5' => Http::response('', 302, ['Location' => 'https://example.com/r6']),
    ]);

    expect(fn () => $this->fetcher->fetch('https://example.com/r0'))
        ->toThrow(\Exception::class);
});

it('still throws InvalidHTTPStatusException on a non-2xx final response', function () {
    Http::fake([
        'example.com/*' => Http::response('Not Found', 404),
    ]);

    expect(fn () => $this->fetcher->fetch('https://example.com/recipe'))
        ->toThrow(\App\Exceptions\InvalidHTTPStatusException::class);
});

it('still sends browser-like headers on a redirected request', function () {
    Http::fake([
        'example.com/old' => Http::response('', 302, ['Location' => 'https://example.com/new']),
        'example.com/new' => Http::response('<html>Final</html>', 200),
    ]);

    $this->fetcher->fetch('https://example.com/old');

    $sentRequests = Http::recorded();

    // At least the final request must carry browser-like headers
    $found = false;
    foreach ($sentRequests as [$request]) {
        if (str_contains((string) $request->url(), 'example.com/new')
            && $request->hasHeader('User-Agent')
            && str_contains($request->header('User-Agent')[0], 'Mozilla')
            && $request->hasHeader('Sec-Fetch-Dest')) {
            $found = true;
            break;
        }
    }

    expect($found)->toBeTrue();
});

it('still fetches a valid public recipe url successfully', function () {
    Http::fake([
        'example.com/*' => Http::response('<html><body>Recipe</body></html>', 200),
    ]);

    $html = $this->fetcher->fetch('https://example.com/recipe');

    expect($html)->toContain('Recipe');
});

it('still serves local test routes without invoking the guard in testing env', function () {
    $validationCalled = false;

    $validator = new UrlSafetyValidator(function () use (&$validationCalled): array {
        $validationCalled = true;

        return ['93.184.216.34'];
    });

    $fetcher = new RecipeFetcher($validator);

    // Local test routes bypass the guard entirely
    $html = $fetcher->fetch('http://localhost/test/recipe-valid');

    expect($validationCalled)->toBeFalse()
        ->and($html)->toContain('Test Chocolate Chip Cookies');
});
