<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Laravel\Pennant\Feature;
use Laravel\Pennant\PennantServiceProvider;

test('it registers the Pennant service provider', function () {
    expect(app()->getLoadedProviders())->toHaveKey(PennantServiceProvider::class);
});

test('it resolves the Pennant Feature facade without error', function () {
    expect(Feature::getFacadeRoot())->not->toBeNull();
});

test('it has a features table from the published Pennant migration', function () {
    expect(Schema::hasTable('features'))->toBeTrue();
});

test('it defines and resolves a throwaway feature flag using the default store', function () {
    Feature::define('scratch-test-flag', fn () => true);

    expect(Feature::active('scratch-test-flag'))->toBeTrue();
});
