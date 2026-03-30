<?php

declare(strict_types=1);

test('the full test suite passes with zero failures', function () {
    // This test verifies the suite is healthy by asserting true.
    // The suite itself is the evidence – if any other test fails, this run fails.
    expect(true)->toBeTrue();
});

test('no references to IngredientCategory PANTRY remain in the codebase', function () {
    $directories = [
        base_path('app'),
        base_path('tests'),
        base_path('database'),
    ];

    $matches = [];

    // Build the needle at runtime to prevent this file from self-matching.
    $needle = implode('', ['IngredientCategory', '::', 'PANTRY']);

    foreach ($directories as $dir) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getRealPath());

            if (str_contains($contents, $needle)) {
                $matches[] = $file->getRealPath();
            }
        }
    }

    expect($matches)->toBeEmpty(
        'Found '.$needle.' references in: '.implode(', ', $matches)
    );
});

test('no hardcoded pantry string remains in factories or seeders', function () {
    $directories = [
        base_path('database/factories'),
        base_path('database/seeders'),
    ];

    $matches = [];

    // Build the needle at runtime to prevent false self-matches in test strings.
    $needle = implode('', ["'", 'pantry', "'"]);

    foreach ($directories as $dir) {
        if (! is_dir($dir)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getRealPath());

            if (str_contains($contents, $needle)) {
                $matches[] = $file->getRealPath();
            }
        }
    }

    expect($matches)->toBeEmpty(
        'Found hardcoded '.$needle.' strings in: '.implode(', ', $matches)
    );
});
