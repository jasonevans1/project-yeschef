<?php

use App\Services\RecipeImporter\RecipeImportService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = app(RecipeImportService::class);
});

test('successfully fetches and parses recipe from URL', function () {
    $recipeHtml = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": ["Recipe"],
        "name": "Chocolate Chip Cookies",
        "description": "Delicious homemade cookies",
        "prepTime": "PT15M",
        "cookTime": "PT10M",
        "recipeYield": "24 cookies",
        "recipeIngredient": ["2 cups flour", "1 cup sugar"],
        "recipeInstructions": "Mix and bake.",
        "image": "https://example.com/cookies.jpg",
        "recipeCuisine": "American",
        "recipeCategory": "Dessert"
    }
    </script>
</head>
<body>Recipe content</body>
</html>
HTML;

    Http::fake([
        'example.com/*' => Http::response($recipeHtml, 200),
    ]);

    $result = $this->service->fetchAndParse('https://example.com/recipe');

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBe('Chocolate Chip Cookies')
        ->and($result['description'])->toBe('Delicious homemade cookies')
        ->and($result['prep_time'])->toBe(15)
        ->and($result['cook_time'])->toBe(10)
        ->and($result['servings'])->toBe(24)
        ->and($result['cuisine'])->toBe('American')
        ->and($result['meal_type'])->toBe('snack')
        ->and($result['instructions'])->toBe('Mix and bake.')
        ->and($result['image_url'])->toBe('https://example.com/cookies.jpg')
        ->and($result['recipeIngredient'])->toBeArray()
        ->and($result['recipeIngredient'])->toHaveCount(2);
});

test('throws exception when URL returns 404', function () {
    Http::fake([
        'example.com/*' => Http::response('Not Found', 404),
    ]);

    expect(fn () => $this->service->fetchAndParse('https://example.com/recipe'))
        ->toThrow(\App\Exceptions\InvalidHTTPStatusException::class);
});

test('throws exception when no recipe found in HTML', function () {
    $html = '<html><body>Just a blog post</body></html>';

    Http::fake([
        'example.com/*' => Http::response($html, 200),
    ]);

    expect(fn () => $this->service->fetchAndParse('https://example.com/article'))
        ->toThrow(\App\Exceptions\MissingRecipeDataException::class);
});

test('throws exception on network timeout', function () {
    Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
    });

    expect(fn () => $this->service->fetchAndParse('https://example.com/recipe'))
        ->toThrow(\App\Exceptions\NetworkTimeoutException::class);
});

test('parses ISO 8601 durations to minutes', function () {
    $recipeHtml = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@type": "Recipe",
        "name": "Test Recipe",
        "prepTime": "PT1H30M",
        "cookTime": "PT45M",
        "recipeIngredient": ["flour"],
        "recipeInstructions": "Cook"
    }
    </script>
</head>
</html>
HTML;

    Http::fake([
        'example.com/*' => Http::response($recipeHtml, 200),
    ]);

    $result = $this->service->fetchAndParse('https://example.com/recipe');

    expect($result['prep_time'])->toBe(90)
        ->and($result['cook_time'])->toBe(45);
});

test('parses recipe yield string with servings', function () {
    $recipeHtml = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@type": "Recipe",
        "name": "Test Recipe",
        "recipeYield": "4 servings",
        "recipeIngredient": ["flour"],
        "recipeInstructions": "Cook"
    }
    </script>
</head>
</html>
HTML;

    Http::fake([
        'example.com/*' => Http::response($recipeHtml, 200),
    ]);

    $result = $this->service->fetchAndParse('https://example.com/recipe');

    expect($result['servings'])->toBe(4);
});

test('parses recipe yield string with makes', function () {
    $recipeHtml = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@type": "Recipe",
        "name": "Test Recipe",
        "recipeYield": "Makes 12",
        "recipeIngredient": ["flour"],
        "recipeInstructions": "Cook"
    }
    </script>
</head>
</html>
HTML;

    Http::fake([
        'example.com/*' => Http::response($recipeHtml, 200),
    ]);

    $result = $this->service->fetchAndParse('https://example.com/recipe');

    expect($result['servings'])->toBe(12);
});

test('parses recipe yield as integer', function () {
    $recipeHtml = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@type": "Recipe",
        "name": "Test Recipe",
        "recipeYield": 6,
        "recipeIngredient": ["flour"],
        "recipeInstructions": "Cook"
    }
    </script>
</head>
</html>
HTML;

    Http::fake([
        'example.com/*' => Http::response($recipeHtml, 200),
    ]);

    $result = $this->service->fetchAndParse('https://example.com/recipe');

    expect($result['servings'])->toBe(6);
});

test('flattens array of instructions to text', function () {
    $recipeHtml = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@type": "Recipe",
        "name": "Test Recipe",
        "recipeIngredient": ["flour"],
        "recipeInstructions": [
            {"@type": "HowToStep", "text": "Preheat oven"},
            {"@type": "HowToStep", "text": "Mix ingredients"},
            {"@type": "HowToStep", "text": "Bake for 30 minutes"}
        ]
    }
    </script>
</head>
</html>
HTML;

    Http::fake([
        'example.com/*' => Http::response($recipeHtml, 200),
    ]);

    $result = $this->service->fetchAndParse('https://example.com/recipe');

    expect($result['instructions'])->toBeString()
        ->and($result['instructions'])->toContain('Preheat oven')
        ->and($result['instructions'])->toContain('Mix ingredients')
        ->and($result['instructions'])->toContain('Bake for 30 minutes');
});

test('maps breakfast category to meal_type', function () {
    $recipeHtml = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@type": "Recipe",
        "name": "Test Recipe",
        "recipeCategory": "Breakfast",
        "recipeIngredient": ["flour"],
        "recipeInstructions": "Cook"
    }
    </script>
</head>
</html>
HTML;

    Http::fake([
        'example.com/*' => Http::response($recipeHtml, 200),
    ]);

    $result = $this->service->fetchAndParse('https://example.com/recipe');

    expect($result['meal_type'])->toBe('breakfast');
});

test('maps lunch category to meal_type', function () {
    $recipeHtml = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@type": "Recipe",
        "name": "Test Recipe",
        "recipeCategory": "Lunch",
        "recipeIngredient": ["flour"],
        "recipeInstructions": "Cook"
    }
    </script>
</head>
</html>
HTML;

    Http::fake([
        'example.com/*' => Http::response($recipeHtml, 200),
    ]);

    $result = $this->service->fetchAndParse('https://example.com/recipe');

    expect($result['meal_type'])->toBe('lunch');
});

test('maps dinner category to meal_type', function () {
    $recipeHtml = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@type": "Recipe",
        "name": "Test Recipe",
        "recipeCategory": "Dinner",
        "recipeIngredient": ["flour"],
        "recipeInstructions": "Cook"
    }
    </script>
</head>
</html>
HTML;

    Http::fake([
        'example.com/*' => Http::response($recipeHtml, 200),
    ]);

    $result = $this->service->fetchAndParse('https://example.com/recipe');

    expect($result['meal_type'])->toBe('dinner');
});

test('maps dessert category to snack meal_type', function () {
    $recipeHtml = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@type": "Recipe",
        "name": "Test Recipe",
        "recipeCategory": "Dessert",
        "recipeIngredient": ["flour"],
        "recipeInstructions": "Cook"
    }
    </script>
</head>
</html>
HTML;

    Http::fake([
        'example.com/*' => Http::response($recipeHtml, 200),
    ]);

    $result = $this->service->fetchAndParse('https://example.com/recipe');

    expect($result['meal_type'])->toBe('snack');
});

test('handles missing optional fields gracefully', function () {
    $recipeHtml = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@type": "Recipe",
        "name": "Minimal Recipe",
        "recipeIngredient": ["flour"],
        "recipeInstructions": "Mix and bake"
    }
    </script>
</head>
</html>
HTML;

    Http::fake([
        'example.com/*' => Http::response($recipeHtml, 200),
    ]);

    $result = $this->service->fetchAndParse('https://example.com/recipe');

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBe('Minimal Recipe')
        ->and($result['prep_time'])->toBeNull()
        ->and($result['cook_time'])->toBeNull()
        ->and($result['cuisine'])->toBeNull()
        ->and($result['meal_type'])->toBeNull()
        ->and($result['image_url'])->toBeNull();
});

test('handles image array and takes first URL', function () {
    $recipeHtml = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@type": "Recipe",
        "name": "Test Recipe",
        "image": [
            "https://example.com/image1.jpg",
            "https://example.com/image2.jpg"
        ],
        "recipeIngredient": ["flour"],
        "recipeInstructions": "Cook"
    }
    </script>
</head>
</html>
HTML;

    Http::fake([
        'example.com/*' => Http::response($recipeHtml, 200),
    ]);

    $result = $this->service->fetchAndParse('https://example.com/recipe');

    expect($result['image_url'])->toBe('https://example.com/image1.jpg');
});

test('throws exception for malformed JSON', function () {
    $recipeHtml = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@type": "Recipe",
        "name": "Broken Recipe
    }
    </script>
</head>
</html>
HTML;

    Http::fake([
        'example.com/*' => Http::response($recipeHtml, 200),
    ]);

    expect(fn () => $this->service->fetchAndParse('https://example.com/recipe'))
        ->toThrow(\App\Exceptions\MalformedRecipeDataException::class);
});

test('preserves original ingredient array for later processing', function () {
    $recipeHtml = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@type": "Recipe",
        "name": "Test Recipe",
        "recipeIngredient": [
            "2 cups flour",
            "1 cup sugar",
            "1/2 tsp salt"
        ],
        "recipeInstructions": "Mix and bake"
    }
    </script>
</head>
</html>
HTML;

    Http::fake([
        'example.com/*' => Http::response($recipeHtml, 200),
    ]);

    $result = $this->service->fetchAndParse('https://example.com/recipe');

    expect($result['recipeIngredient'])->toBeArray()
        ->and($result['recipeIngredient'])->toHaveCount(3)
        ->and($result['recipeIngredient'][0])->toBe('2 cups flour')
        ->and($result['recipeIngredient'][1])->toBe('1 cup sugar')
        ->and($result['recipeIngredient'][2])->toBe('1/2 tsp salt');
});

test('handles recipeCategory as array', function () {
    $recipeHtml = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@type": "Recipe",
        "name": "Test Recipe",
        "recipeCategory": ["Dinner", "Lunch"],
        "recipeIngredient": ["flour"],
        "recipeInstructions": "Cook"
    }
    </script>
</head>
</html>
HTML;

    Http::fake([
        'example.com/*' => Http::response($recipeHtml, 200),
    ]);

    $result = $this->service->fetchAndParse('https://example.com/recipe');

    expect($result['meal_type'])->toBe('dinner');
});

test('handles recipeCuisine as array', function () {
    $recipeHtml = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@type": "Recipe",
        "name": "Test Recipe",
        "recipeCuisine": ["American", "Mexican"],
        "recipeIngredient": ["flour"],
        "recipeInstructions": "Cook"
    }
    </script>
</head>
</html>
HTML;

    Http::fake([
        'example.com/*' => Http::response($recipeHtml, 200),
    ]);

    $result = $this->service->fetchAndParse('https://example.com/recipe');

    expect($result['cuisine'])->toBe('American');
});

test('handles image as ImageObject', function () {
    $recipeHtml = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@type": "Recipe",
        "name": "Test Recipe",
        "image": {
            "@type": "ImageObject",
            "url": "https://example.com/image.jpg",
            "width": 960,
            "height": 960
        },
        "recipeIngredient": ["flour"],
        "recipeInstructions": "Cook"
    }
    </script>
</head>
</html>
HTML;

    Http::fake([
        'example.com/*' => Http::response($recipeHtml, 200),
    ]);

    $result = $this->service->fetchAndParse('https://example.com/recipe');

    expect($result['image_url'])->toBe('https://example.com/image.jpg');
});

test('handles top-level array JSON-LD structure', function () {
    $recipeHtml = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    [
        {
            "@type": ["Recipe"],
            "name": "Array Recipe",
            "recipeIngredient": ["flour", "sugar"],
            "recipeInstructions": "Mix and bake."
        }
    ]
    </script>
</head>
</html>
HTML;

    Http::fake([
        'example.com/*' => Http::response($recipeHtml, 200),
    ]);

    $result = $this->service->fetchAndParse('https://example.com/recipe');

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBe('Array Recipe')
        ->and($result['recipeIngredient'])->toHaveCount(2);
});

/**
 * @param  array<string, mixed>  $overrides
 */
function recipeJson(array $overrides = []): string
{
    return json_encode(array_merge([
        '@context' => 'https://schema.org',
        '@type' => 'Recipe',
        'name' => 'Pancakes',
        'description' => 'Fluffy',
        'prepTime' => 'PT15M',
        'cookTime' => 'PT1H30M',
        'recipeYield' => '6 servings',
        'recipeIngredient' => ['1 cup flour', '2 eggs'],
        'recipeInstructions' => 'Mix and fry.',
    ], $overrides));
}

it('parses schema.org JSON into the normalized recipe array', function () {
    $result = $this->service->parseJson(recipeJson());

    expect($result)->toMatchArray([
        'name' => 'Pancakes',
        'description' => 'Fluffy',
        'instructions' => 'Mix and fry.',
        'recipeIngredient' => ['1 cup flour', '2 eggs'],
    ]);
});

it('converts ISO durations and recipeYield when parsing JSON', function () {
    $result = $this->service->parseJson(recipeJson());

    expect($result['prep_time'])->toBe(15)
        ->and($result['cook_time'])->toBe(90)
        ->and($result['servings'])->toBe(6);
});

it('sets source_url from the recipe url field', function () {
    $result = $this->service->parseJson(recipeJson(['url' => 'https://example.com/pancakes']));

    expect($result['source_url'])->toBe('https://example.com/pancakes');
});

it('leaves source_url null when the url field is missing or not http(s)', function (array $extra) {
    expect($this->service->parseJson(recipeJson($extra))['source_url'])->toBeNull();
})->with([
    'missing' => [[]],
    'not a url' => [['url' => 'nope']],
    'ftp' => [['url' => 'ftp://example.com/x']],
    'javascript' => [['url' => 'javascript:alert(1)']],
    'array' => [['url' => ['https://example.com']]],
]);

it('throws MalformedRecipeDataException when JSON recipe lacks name, instructions, or ingredients', function (string $field) {
    $data = json_decode(recipeJson(), true);
    unset($data[$field]);

    expect(fn () => $this->service->parseJson(json_encode($data)))
        ->toThrow(\App\Exceptions\MalformedRecipeDataException::class);
})->with(['name', 'recipeInstructions', 'recipeIngredient']);

it('throws MalformedRecipeDataException when name is not a string or recipeIngredient is not an array', function (array $override) {
    expect(fn () => $this->service->parseJson(recipeJson($override)))
        ->toThrow(\App\Exceptions\MalformedRecipeDataException::class);
})->with([
    'numeric name' => [['name' => 123]],
    'array name' => [['name' => ['a']]],
    'string ingredients' => [['recipeIngredient' => 'flour, sugar']],
]);

it('drops non-string ingredient items and throws when none remain', function () {
    $result = $this->service->parseJson(recipeJson(['recipeIngredient' => ['flour', 5, '', ['x'], 'eggs']]));

    expect($result['recipeIngredient'])->toBe(['flour', 'eggs'])
        ->and(fn () => $this->service->parseJson(recipeJson(['recipeIngredient' => [5, '', null]])))
        ->toThrow(\App\Exceptions\MalformedRecipeDataException::class);
});

it('nulls a non-string description', function () {
    expect($this->service->parseJson(recipeJson(['description' => ['x']]))['description'])->toBeNull();
});

it('reports malformed file errors without mentioning a page', function () {
    try {
        $this->service->parseJson(recipeJson(['name' => 123]));
        $this->fail('Expected exception');
    } catch (\App\Exceptions\MalformedRecipeDataException $e) {
        expect($e->getMessage())->toContain('recipe file')->not->toContain('page');
    }

    try {
        $this->service->parseJson('{not json');
        $this->fail('Expected exception');
    } catch (\App\Exceptions\MalformedRecipeDataException $e) {
        expect($e->getMessage())->toContain('recipe file')->not->toContain('page');
    }
});
