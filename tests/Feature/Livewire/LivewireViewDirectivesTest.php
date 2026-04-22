<?php

declare(strict_types=1);

/**
 * Structural tests verifying Livewire 4 view directive compliance.
 *
 * These tests scan blade view files to ensure no deprecated Livewire 3
 * directive syntax remains after the Livewire 4 upgrade.
 */

/**
 * @return array<string> List of all blade view file paths
 */
function allBladeViewFiles(): array
{
    $viewsPath = base_path('resources/views');
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($viewsPath)
    );

    $files = [];
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php' && str_ends_with($file->getFilename(), '.blade.php')) {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

/**
 * @param  array<string>  $files
 * @return array<string> Files that match the pattern
 */
function filesContainingPattern(array $files, string $pattern): array
{
    $matches = [];
    foreach ($files as $file) {
        $content = file_get_contents($file);
        if ($content !== false && preg_match($pattern, $content)) {
            $matches[] = $file;
        }
    }

    return $matches;
}

it('has no wire:model.blur without .live prefix in blade views', function (): void {
    $files = allBladeViewFiles();

    // Match wire:model.blur that is NOT preceded by .live (e.g. wire:model.blur but not wire:model.live.blur)
    $violatingFiles = filesContainingPattern($files, '/wire:model(?!\.live)\.blur/');

    expect($violatingFiles)->toBe([])
        ->and($violatingFiles)->toBeEmpty('Found wire:model.blur without .live prefix in: '.implode(', ', $violatingFiles));
});

it('has no wire:model.change without .live prefix in blade views', function (): void {
    $files = allBladeViewFiles();

    // Match wire:model.change that is NOT preceded by .live (e.g. wire:model.change but not wire:model.live.change)
    $violatingFiles = filesContainingPattern($files, '/wire:model(?!\.live)\.change/');

    expect($violatingFiles)->toBe([])
        ->and($violatingFiles)->toBeEmpty('Found wire:model.change without .live prefix in: '.implode(', ', $violatingFiles));
});

it('has no unclosed livewire component tags', function (): void {
    $files = allBladeViewFiles();

    // Match <livewire:something> that does NOT end with /> (self-closing)
    // A valid closed tag looks like: <livewire:foo /> or <livewire:foo attr="val" />
    // An invalid unclosed tag looks like: <livewire:foo> (no /)
    $violatingFiles = filesContainingPattern($files, '/<livewire:[a-z][^>]*[^\/]>/');

    expect($violatingFiles)->toBe([])
        ->and($violatingFiles)->toBeEmpty('Found unclosed livewire component tags in: '.implode(', ', $violatingFiles));
});

it('has no wire:scroll directives (replaced by wire:navigate:scroll)', function (): void {
    $files = allBladeViewFiles();

    // Match wire:scroll but NOT wire:navigate:scroll
    $violatingFiles = filesContainingPattern($files, '/wire:scroll(?!ed)(?!ing)(?!able)\b/');

    expect($violatingFiles)->toBe([])
        ->and($violatingFiles)->toBeEmpty('Found wire:scroll directive (should be wire:navigate:scroll) in: '.implode(', ', $violatingFiles));
});
