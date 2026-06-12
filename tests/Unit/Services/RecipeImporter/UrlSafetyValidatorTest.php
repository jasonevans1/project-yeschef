<?php

declare(strict_types=1);

use App\Exceptions\BlockedUrlException;
use App\Services\RecipeImporter\UrlSafetyValidator;

/**
 * Build a stub resolver Closure returning a fixed set of IPs.
 *
 * @param  array<string>  $ips
 */
function makeResolver(array $ips): Closure
{
    return function (string $host) use ($ips): array {
        return $ips;
    };
}

it('allows a public http url whose host resolves to a public ip', function () {
    $validator = new UrlSafetyValidator(makeResolver(['93.184.216.34']));

    expect(fn () => $validator->validate('http://example.com/recipe'))->not->toThrow(BlockedUrlException::class);
});

it('allows a public https url whose host resolves to a public ip', function () {
    $validator = new UrlSafetyValidator(makeResolver(['93.184.216.34']));

    expect(fn () => $validator->validate('https://example.com/recipe'))->not->toThrow(BlockedUrlException::class);
});

it('allows a literal public ip without dns lookup', function () {
    $resolverCalled = false;
    $resolver = function (string $host) use (&$resolverCalled): array {
        $resolverCalled = true;

        return [];
    };

    $validator = new UrlSafetyValidator($resolver);

    expect(fn () => $validator->validate('http://93.184.216.34/recipe'))->not->toThrow(BlockedUrlException::class)
        ->and($resolverCalled)->toBeFalse();
});

it('rejects a malformed url with no host', function (string $url) {
    $validator = new UrlSafetyValidator(makeResolver(['93.184.216.34']));

    expect(fn () => $validator->validate($url))->toThrow(BlockedUrlException::class);
})->with([
    'no host path only' => ['http:///path'],
    'completely malformed' => ['not-a-url-at-all'],
    'just a string' => ['example.com'],
]);

it('rejects a host that resolves to a mix of public and private ips', function () {
    // Even if one IP is public, a private IP in the list must cause rejection
    $validator = new UrlSafetyValidator(makeResolver(['93.184.216.34', '192.168.1.1']));

    expect(fn () => $validator->validate('http://mixed.example/resource'))->toThrow(BlockedUrlException::class);
});

it('rejects a host that fails to resolve to any address', function () {
    $validator = new UrlSafetyValidator(makeResolver([]));

    expect(fn () => $validator->validate('http://nonexistent.invalid/resource'))->toThrow(BlockedUrlException::class);
});

it('rejects the cloud metadata ip given as a literal without dns lookup', function () {
    $resolverCalled = false;
    $resolver = function (string $host) use (&$resolverCalled): array {
        $resolverCalled = true;

        return ['93.184.216.34'];
    };

    $validator = new UrlSafetyValidator($resolver);

    expect(fn () => $validator->validate('http://169.254.169.254/latest/meta-data/'))->toThrow(BlockedUrlException::class)
        ->and($resolverCalled)->toBeFalse();
});

it('rejects an ipv6 literal loopback host given in brackets without dns lookup', function () {
    $resolverCalled = false;
    $resolver = function (string $host) use (&$resolverCalled): array {
        $resolverCalled = true;

        return ['93.184.216.34'];
    };

    $validator = new UrlSafetyValidator($resolver);

    expect(fn () => $validator->validate('http://[::1]/resource'))->toThrow(BlockedUrlException::class)
        ->and($resolverCalled)->toBeFalse();
});

it('rejects a url given as a literal private ip without dns lookup', function (string $url) {
    // Resolver should never be called for literal IP hosts
    $resolverCalled = false;
    $resolver = function (string $host) use (&$resolverCalled): array {
        $resolverCalled = true;

        return ['93.184.216.34'];
    };

    $validator = new UrlSafetyValidator($resolver);

    expect(fn () => $validator->validate($url))->toThrow(BlockedUrlException::class)
        ->and($resolverCalled)->toBeFalse();
})->with([
    'ipv4 private 10.x' => ['http://10.0.0.1/resource'],
    'ipv4 private 192.168.x' => ['http://192.168.1.1/resource'],
    'ipv4 loopback 127.0.0.1' => ['http://127.0.0.1/resource'],
]);

it('rejects a host resolving to an ipv6 loopback or link local address', function (string $ip) {
    $validator = new UrlSafetyValidator(makeResolver([$ip]));

    expect(fn () => $validator->validate('http://ipv6host.example/resource'))->toThrow(BlockedUrlException::class);
})->with([
    'ipv6 loopback ::1' => ['::1'],
    'ipv6 link-local fe80::1' => ['fe80::1'],
    'ipv6 link-local fe80::abcd' => ['fe80::abcd'],
]);

it('rejects a host resolving to the link local cloud metadata address', function (string $ip) {
    $validator = new UrlSafetyValidator(makeResolver([$ip]));

    expect(fn () => $validator->validate('http://metadata.internal/iam/security-credentials/'))->toThrow(BlockedUrlException::class);
})->with([
    'cloud metadata' => ['169.254.169.254'],
    'link local start' => ['169.254.0.1'],
    'link local end' => ['169.254.255.254'],
]);

it('rejects a host resolving to a private network range', function (string $ip) {
    $validator = new UrlSafetyValidator(makeResolver([$ip]));

    expect(fn () => $validator->validate('http://internal.example/resource'))->toThrow(BlockedUrlException::class);
})->with([
    '10.x.x.x' => ['10.0.0.1'],
    '172.16.x.x' => ['172.16.0.1'],
    '192.168.x.x' => ['192.168.1.1'],
    '172.31.x.x' => ['172.31.255.254'],
]);

it('rejects a host resolving to a loopback address', function () {
    $validator = new UrlSafetyValidator(makeResolver(['127.0.0.1']));

    expect(fn () => $validator->validate('http://localhost.internal/secret'))->toThrow(BlockedUrlException::class);
});

it('rejects urls with a non http or https scheme', function (string $url) {
    $validator = new UrlSafetyValidator(makeResolver(['93.184.216.34']));

    expect(fn () => $validator->validate($url))->toThrow(BlockedUrlException::class);
})->with([
    'ftp scheme' => ['ftp://example.com/file'],
    'file scheme' => ['file:///etc/passwd'],
    'mailto scheme' => ['mailto:user@example.com'],
    'javascript scheme' => ['javascript:alert(1)'],
    'gopher scheme' => ['gopher://example.com'],
]);
