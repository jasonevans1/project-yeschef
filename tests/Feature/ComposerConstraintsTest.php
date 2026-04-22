<?php

declare(strict_types=1);

use Illuminate\Support\Str;

test('it updates laravel/framework constraint to ^13.0 in composer.json', function () {
    $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);

    expect($composerJson['require']['laravel/framework'])->toBe('^13.0');
});

test('it updates laravel/tinker constraint to ^3.0 in composer.json', function () {
    $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);

    expect($composerJson['require']['laravel/tinker'])->toBe('^3.0');
});

test('it updates livewire/livewire constraint to ^4.0 in composer.json', function () {
    $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);

    expect($composerJson['require']['livewire/livewire'])->toBe('^4.0');
});

test('it updates laravel/boost constraint to ^2.0 in require-dev', function () {
    $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);

    expect($composerJson['require-dev']['laravel/boost'])->toBe('^2.0');
});

test('it runs composer update successfully with the new constraints', function () {
    $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);

    expect($composerLock)->toBeArray()
        ->and($composerLock)->toHaveKey('packages');
});

test('it shows Laravel 13.x version when running php artisan about', function () {
    $version = app()->version();

    expect(Str::startsWith($version, '13.'))->toBeTrue();
});

test('it shows Livewire 4.x version when running php artisan about', function () {
    $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);

    $livewirePackage = collect($composerLock['packages'])->firstWhere('name', 'livewire/livewire');

    expect($livewirePackage)->not->toBeNull()
        ->and(Str::startsWith($livewirePackage['version'], 'v4.'))->toBeTrue();
});

test('it updates livewire/flux to 2.13.2 in composer.lock', function () {
    $composerLock = json_decode(file_get_contents(base_path('composer.lock')), true);

    $fluxPackage = collect($composerLock['packages'])->firstWhere('name', 'livewire/flux');

    expect($fluxPackage)->not->toBeNull()
        ->and($fluxPackage['version'])->toBe('v2.13.2');
});
