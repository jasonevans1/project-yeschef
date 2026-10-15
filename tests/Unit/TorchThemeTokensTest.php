<?php

declare(strict_types=1);

it('sets the light mode accent tokens to the torch steel blue palette', function () {
    $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/app.css');

    expect($css)
        ->toContain('--color-accent: #06377e;')
        ->toContain('--color-accent-content: #06377e;')
        ->toContain('--color-accent-foreground: #ffffff;');
});

it('sets the dark mode accent tokens to the torch spark blue palette', function () {
    $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/app.css');

    expect($css)
        ->toContain('--color-accent: #2995de;')
        ->toContain('--color-accent-content: #2995de;')
        ->toContain('--color-accent-foreground: #05070c;');
});

it('retints the zinc scale with a steel blue hue bias', function () {
    $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/app.css');

    expect($css)
        ->toContain('--color-zinc-50: #f7f9fb;')
        ->toContain('--color-zinc-100: #eef2f6;')
        ->toContain('--color-zinc-200: #dde4ea;')
        ->toContain('--color-zinc-300: #c3ced9;')
        ->toContain('--color-zinc-400: #94a5b5;')
        ->toContain('--color-zinc-500: #6b7d8f;')
        ->toContain('--color-zinc-600: #4f6070;')
        ->toContain('--color-zinc-700: #384654;')
        ->toContain('--color-zinc-800: #232d38;')
        ->toContain('--color-zinc-900: #131a22;')
        ->toContain('--color-zinc-950: #0a0e13;');
});

it('retints the neutral scale to match the zinc scale', function () {
    $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/app.css');

    expect($css)
        ->toContain('--color-neutral-50: #f7f9fb;')
        ->toContain('--color-neutral-100: #eef2f6;')
        ->toContain('--color-neutral-200: #dde4ea;')
        ->toContain('--color-neutral-300: #c3ced9;')
        ->toContain('--color-neutral-400: #94a5b5;')
        ->toContain('--color-neutral-500: #6b7d8f;')
        ->toContain('--color-neutral-600: #4f6070;')
        ->toContain('--color-neutral-700: #384654;')
        ->toContain('--color-neutral-800: #232d38;')
        ->toContain('--color-neutral-900: #131a22;')
        ->toContain('--color-neutral-950: #0a0e13;');
});

it('defines a gold accent token for light and dark mode', function () {
    $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/app.css');

    expect($css)
        ->toContain('--color-gold: #a4842f;')
        ->toContain('--color-gold: #c9a54a;');
});

it('sets the pwa manifest theme color to the torch steel blue brand color', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__, 2).'/public/site.webmanifest'), true);

    expect($manifest['theme_color'])->toBe('#06377e');
});
