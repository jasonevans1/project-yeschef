<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Env;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // In DDEV, the container's OS environment overrides env vars set by phpunit.xml
        // (PHPUnit only calls putenv() when getenv() returns false, so DDEV values persist).
        // This causes tests to run against MariaDB instead of SQLite :memory: and breaks
        // CSRF handling. We explicitly set test values before the app bootstraps.
        $this->overrideEnvironmentForTesting();

        parent::setUp();

        // Force the app's env binding to 'testing' so CSRF and other middleware work correctly.
        // This is needed because the config was loaded from the DDEV .env file during bootstrap,
        // and app()->environment() may still reflect 'local'.
        $this->app['env'] = 'testing';
    }

    private function overrideEnvironmentForTesting(): void
    {
        $testVars = [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'array',
            'BCRYPT_ROUNDS' => '4',
        ];

        foreach ($testVars as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        // Reset the Dotenv singleton so the next app bootstrap re-reads from
        // the updated env vars above, rather than returning cached DDEV values.
        Env::enablePutenv();
    }
}
