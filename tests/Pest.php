<?php

use App\Services\RecipeImporter\UrlSafetyValidator;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Test-Only Container Bindings
|--------------------------------------------------------------------------
|
| Register a stub UrlSafetyValidator in the container for all Feature and
| Browser tests so that no real DNS lookups are performed. The stub maps
| known test hostnames to a fixed public IP (93.184.216.34 = example.com)
| so that Http::fake() usage in those tests remains hermetic.
|
| Host => IP map:
|   example.com          => 93.184.216.34
|   slow-site.com        => 93.184.216.34
|   unreachable.com      => 93.184.216.34
|   yeschef.ddev.site    => 93.184.216.34
|
*/

pest()->beforeEach(function () {
    /** @var array<string, string> $testHostMap */
    $testHostMap = [
        'example.com' => '93.184.216.34',
        'slow-site.com' => '93.184.216.34',
        'unreachable.com' => '93.184.216.34',
        'yeschef.ddev.site' => '93.184.216.34',
    ];

    app()->bind(UrlSafetyValidator::class, function () use ($testHostMap) {
        return new UrlSafetyValidator(function (string $host) use ($testHostMap): array {
            return isset($testHostMap[$host]) ? [$testHostMap[$host]] : [];
        });
    });
})->in('Feature', 'Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something(): void
{
    // ..
}
