<?php

use App\Services\RecipeImporter\RecipeSanitizer;

beforeEach(function () {
    $this->sanitizer = new RecipeSanitizer;
});

test('strips HTML tags from text fields', function () {
    $data = [
        'name' => 'Recipe <script>alert("XSS")</script>',
        'description' => '<b>Bold</b> and <i>italic</i> text',
        'instructions' => '<p>Step 1</p><p>Step 2</p>',
    ];

    $result = $this->sanitizer->sanitize($data);

    expect($result['name'])->toBe('Recipe alert("XSS")')
        ->and($result['description'])->toBe('Bold and italic text')
        ->and($result['instructions'])->toBe('Step 1Step 2');
});

test('validates and sanitizes URLs', function () {
    $data = [
        'source_url' => 'https://example.com/recipe',
        'image_url' => 'https://example.com/image.jpg',
    ];

    $result = $this->sanitizer->sanitize($data);

    expect($result['source_url'])->toBe('https://example.com/recipe')
        ->and($result['image_url'])->toBe('https://example.com/image.jpg');
});

test('removes invalid URLs', function () {
    $data = [
        'source_url' => 'javascript:alert("XSS")',
        'image_url' => 'not a url',
    ];

    $result = $this->sanitizer->sanitize($data);

    expect($result['source_url'])->toBeNull()
        ->and($result['image_url'])->toBeNull();
});

test('prevents XSS in recipe name', function () {
    $data = [
        'name' => '<img src=x onerror=alert("XSS")>Recipe Name',
    ];

    $result = $this->sanitizer->sanitize($data);

    expect($result['name'])->not->toContain('<img')
        ->and($result['name'])->not->toContain('onerror')
        ->and($result['name'])->toContain('Recipe Name');
});

test('prevents XSS in description', function () {
    $data = [
        'description' => 'Recipe <iframe src="evil.com"></iframe> description',
    ];

    $result = $this->sanitizer->sanitize($data);

    expect($result['description'])->not->toContain('<iframe')
        ->and($result['description'])->not->toContain('</iframe>')
        ->and($result['description'])->toContain('Recipe')
        ->and($result['description'])->toContain('description');
});

test('prevents XSS in instructions', function () {
    $data = [
        'instructions' => 'Step 1: <script>fetch("evil.com")</script>Mix ingredients',
    ];

    $result = $this->sanitizer->sanitize($data);

    expect($result['instructions'])->not->toContain('<script>')
        ->and($result['instructions'])->not->toContain('</script>')
        ->and($result['instructions'])->toContain('Mix ingredients');
});

test('truncates name to 255 characters', function () {
    $longName = str_repeat('a', 300);
    $data = ['name' => $longName];

    $result = $this->sanitizer->sanitize($data);

    expect(strlen($result['name']))->toBe(255);
});

test('truncates description to 5000 characters', function () {
    $longDescription = str_repeat('a', 6000);
    $data = ['description' => $longDescription];

    $result = $this->sanitizer->sanitize($data);

    expect(strlen($result['description']))->toBe(5000);
});

test('truncates cuisine to 100 characters', function () {
    $longCuisine = str_repeat('a', 150);
    $data = ['cuisine' => $longCuisine];

    $result = $this->sanitizer->sanitize($data);

    expect(strlen($result['cuisine']))->toBe(100);
});

test('truncates URLs to 2048 characters', function () {
    $longUrl = 'https://example.com/'.str_repeat('a', 3000);
    $data = [
        'source_url' => $longUrl,
        'image_url' => $longUrl,
    ];

    $result = $this->sanitizer->sanitize($data);

    expect(strlen($result['source_url']))->toBeLessThanOrEqual(2048)
        ->and(strlen($result['image_url']))->toBeLessThanOrEqual(2048);
});

test('sanitizes array of ingredients', function () {
    $data = [
        'recipeIngredient' => [
            '2 cups <b>flour</b>',
            '1 <script>alert("XSS")</script> egg',
            '1/2 cup sugar',
        ],
    ];

    $result = $this->sanitizer->sanitize($data);

    expect($result['recipeIngredient'])->toBeArray()
        ->and($result['recipeIngredient'])->toHaveCount(3)
        ->and($result['recipeIngredient'][0])->toBe('2 cups flour')
        ->and($result['recipeIngredient'][1])->not->toContain('<script>')
        ->and($result['recipeIngredient'][2])->toBe('1/2 cup sugar');
});

test('handles empty strings', function () {
    $data = [
        'name' => '',
        'description' => '',
    ];

    $result = $this->sanitizer->sanitize($data);

    expect($result['name'])->toBe('')
        ->and($result['description'])->toBe('');
});

test('handles null values', function () {
    $data = [
        'name' => 'Recipe Name',
        'description' => null,
        'cuisine' => null,
    ];

    $result = $this->sanitizer->sanitize($data);

    expect($result['name'])->toBe('Recipe Name')
        ->and($result['description'])->toBeNull()
        ->and($result['cuisine'])->toBeNull();
});

test('preserves numeric values', function () {
    $data = [
        'prep_time' => 30,
        'cook_time' => 45,
        'servings' => 4,
    ];

    $result = $this->sanitizer->sanitize($data);

    expect($result['prep_time'])->toBe(30)
        ->and($result['cook_time'])->toBe(45)
        ->and($result['servings'])->toBe(4);
});

test('validates and preserves meal_type enum', function () {
    $data = ['meal_type' => 'dinner'];

    $result = $this->sanitizer->sanitize($data);

    expect($result['meal_type'])->toBe('dinner');
});

test('allows data URLs for small images', function () {
    $dataUrl = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    $data = ['image_url' => $dataUrl];

    $result = $this->sanitizer->sanitize($data);

    expect($result['image_url'])->toBe($dataUrl);
});

test('removes javascript protocol from URLs', function () {
    $data = [
        'source_url' => 'javascript:void(0)',
        'image_url' => 'javascript:alert("XSS")',
    ];

    $result = $this->sanitizer->sanitize($data);

    expect($result['source_url'])->toBeNull()
        ->and($result['image_url'])->toBeNull();
});

test('handles image as array and takes first valid URL', function () {
    $data = [
        'image' => [
            'javascript:alert("XSS")',
            'https://example.com/image1.jpg',
            'https://example.com/image2.jpg',
        ],
    ];

    $result = $this->sanitizer->sanitize($data);

    expect($result['image_url'])->toBe('https://example.com/image1.jpg');
});

test('handles image as single URL string', function () {
    $data = ['image' => 'https://example.com/image.jpg'];

    $result = $this->sanitizer->sanitize($data);

    expect($result['image_url'])->toBe('https://example.com/image.jpg');
});

test('handles empty data array', function () {
    $result = $this->sanitizer->sanitize([]);

    expect($result)->toBeArray()
        ->and($result)->toBeEmpty();
});
