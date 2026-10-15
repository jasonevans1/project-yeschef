<?php

declare(strict_types=1);

test('it has no workflow that references the develop branch', function () {
    $workflows = glob(base_path('.github/workflows/*.yml'));

    foreach ($workflows as $workflow) {
        expect(file_get_contents($workflow))->not->toContain('develop');
    }
});

test('it triggers the factory ci workflow on pull requests and pushes to main', function () {
    $workflow = file_get_contents(base_path('.github/workflows/factory-ci.yml'));

    expect($workflow)->toMatch('/on:\s*\n\s*pull_request:\s*\n\s*push:\s*\n\s*branches:\s*\[main\]/');
});

test('it preserves the required status check job ids', function () {
    $workflow = file_get_contents(base_path('.github/workflows/factory-ci.yml'));

    expect($workflow)->toMatch('/^  ci:\n\s*uses: jasonevans1\/laravel-factory\/\.github\/workflows\/tests\.yml@/m')
        ->and($workflow)->toMatch('/^  lint:\n(.*\n)*?\s*uses: jasonevans1\/laravel-factory\/\.github\/workflows\/lint\.yml@/m');
});
