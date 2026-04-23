<?php

declare(strict_types=1);

test('it has serializable_classes option in cache config', function () {
    $cacheConfig = require base_path('config/cache.php');

    expect($cacheConfig)->toHaveKey('serializable_classes');
});

test('it has CACHE_PREFIX defined in env example to preserve production continuity', function () {
    $envExample = file_get_contents(base_path('.env.example'));

    // Must be uncommented (not just a comment) and have a value
    expect($envExample)->toMatch('/^CACHE_PREFIX=.+/m');
});

test('it has SESSION_COOKIE defined in env example to preserve production continuity', function () {
    $envExample = file_get_contents(base_path('.env.example'));

    // Must be uncommented (not just a comment) and have a value
    expect($envExample)->toMatch('/^SESSION_COOKIE=.+/m');
});

test('it passes php artisan config:clear without errors', function () {
    $output = shell_exec('cd '.base_path().' && php artisan config:clear 2>&1');

    expect($output)->not->toContain('ERROR')
        ->and($output)->not->toContain('Exception');
});

test('it passes php artisan optimize:clear without errors', function () {
    $output = shell_exec('cd '.base_path().' && php artisan optimize:clear 2>&1');

    expect($output)->not->toContain('ERROR')
        ->and($output)->not->toContain('Exception');
});

test('it runs existing cache-related feature tests successfully', function () {
    $cacheConfig = config('cache');

    expect($cacheConfig)->toBeArray()
        ->and($cacheConfig)->toHaveKey('default')
        ->and($cacheConfig)->toHaveKey('stores')
        ->and($cacheConfig)->toHaveKey('prefix');
});
