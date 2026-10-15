<?php

declare(strict_types=1);

test('it has no workflow that references the develop branch', function () {
    $workflows = glob(base_path('.github/workflows/*.yml'));

    foreach ($workflows as $workflow) {
        expect(file_get_contents($workflow))->not->toContain('develop');
    }
});

test('it triggers ci workflows only on pull requests targeting main', function () {
    $pattern = '/on:\s*\n\s*pull_request:\s*\n\s*branches:\s*\n\s*-\s*main\s*\n/';

    foreach (['tests.yml', 'lint.yml', 'e2e.yml'] as $file) {
        expect(file_get_contents(base_path(".github/workflows/{$file}")))->toMatch($pattern);
    }
});

test('it preserves the required status check job ids', function () {
    expect(file_get_contents(base_path('.github/workflows/tests.yml')))->toContain('ci:')
        ->and(file_get_contents(base_path('.github/workflows/e2e.yml')))->toContain('playwright:')
        ->and(file_get_contents(base_path('.github/workflows/lint.yml')))->toContain('quality:');
});
