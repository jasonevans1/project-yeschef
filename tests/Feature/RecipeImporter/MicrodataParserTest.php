<?php

use App\Exceptions\CloudflareBlockedException;
use App\Services\RecipeImporter\MicrodataParser;

beforeEach(function () {
    $this->parser = new MicrodataParser;
});

test('successfully parses JSON-LD Recipe from HTML', function () {
    $html = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Recipe",
        "name": "Chocolate Chip Cookies",
        "description": "Delicious homemade cookies",
        "prepTime": "PT15M",
        "cookTime": "PT10M",
        "totalTime": "PT25M",
        "recipeYield": "24 cookies",
        "recipeIngredient": ["2 cups flour", "1 cup sugar", "1/2 cup butter"],
        "recipeInstructions": "Mix ingredients and bake.",
        "image": "https://example.com/cookies.jpg",
        "recipeCuisine": "American",
        "recipeCategory": "Dessert"
    }
    </script>
</head>
<body>Recipe content</body>
</html>
HTML;

    $result = $this->parser->parse($html);

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBe('Chocolate Chip Cookies')
        ->and($result['description'])->toBe('Delicious homemade cookies')
        ->and($result['prepTime'])->toBe('PT15M')
        ->and($result['cookTime'])->toBe('PT10M')
        ->and($result['recipeYield'])->toBe('24 cookies')
        ->and($result['recipeIngredient'])->toBeArray()
        ->and($result['recipeIngredient'])->toHaveCount(3)
        ->and($result['recipeInstructions'])->toBe('Mix ingredients and bake.')
        ->and($result['image'])->toBe('https://example.com/cookies.jpg');
});

test('returns null when no Recipe found in HTML', function () {
    $html = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Article",
        "name": "How to Cook"
    }
    </script>
</head>
<body>Article content</body>
</html>
HTML;

    $result = $this->parser->parse($html);

    expect($result)->toBeNull();
});

test('returns null when HTML has no JSON-LD script tags', function () {
    $html = '<html><body>Plain HTML with no structured data</body></html>';

    $result = $this->parser->parse($html);

    expect($result)->toBeNull();
});

test('handles malformed JSON gracefully', function () {
    $html = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Recipe",
        "name": "Invalid JSON
    }
    </script>
</head>
<body>Content</body>
</html>
HTML;

    $result = $this->parser->parse($html);

    expect($result)->toBeNull();
});

test('parses Recipe from @graph array', function () {
    $html = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@graph": [
            {
                "@type": "WebSite",
                "name": "Recipe Site"
            },
            {
                "@type": "Recipe",
                "name": "Pasta Carbonara",
                "recipeIngredient": ["pasta", "eggs", "bacon"],
                "recipeInstructions": "Cook pasta, mix with eggs and bacon."
            }
        ]
    }
    </script>
</head>
<body>Content</body>
</html>
HTML;

    $result = $this->parser->parse($html);

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBe('Pasta Carbonara')
        ->and($result['recipeIngredient'])->toHaveCount(3);
});

test('handles JSON-LD with comments', function () {
    $html = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    /* This is a comment */
    {
        "@context": "https://schema.org",
        "@type": "Recipe",
        "name": "Test Recipe"
    }
    /* Another comment */
    </script>
</head>
<body>Content</body>
</html>
HTML;

    $result = $this->parser->parse($html);

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBe('Test Recipe');
});

test('handles newlines in JSON-LD', function () {
    $html = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Recipe",
        "name": "Recipe with
        newlines in
        data"
    }
    </script>
</head>
<body>Content</body>
</html>
HTML;

    $result = $this->parser->parse($html);

    expect($result)->not->toBeNull();
});

test('finds Recipe when multiple script tags exist', function () {
    $html = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Food Blog"
    }
    </script>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Recipe",
        "name": "Found Recipe"
    }
    </script>
</head>
<body>Content</body>
</html>
HTML;

    $result = $this->parser->parse($html);

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBe('Found Recipe');
});

test('handles Recipe with array of instructions', function () {
    $html = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Recipe",
        "name": "Step by Step Recipe",
        "recipeInstructions": [
            {"@type": "HowToStep", "text": "Preheat oven"},
            {"@type": "HowToStep", "text": "Mix ingredients"},
            {"@type": "HowToStep", "text": "Bake for 30 minutes"}
        ]
    }
    </script>
</head>
<body>Content</body>
</html>
HTML;

    $result = $this->parser->parse($html);

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBe('Step by Step Recipe')
        ->and($result['recipeInstructions'])->toBeArray()
        ->and($result['recipeInstructions'])->toHaveCount(3);
});

test('handles Recipe with image array', function () {
    $html = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Recipe",
        "name": "Multi Image Recipe",
        "image": [
            "https://example.com/image1.jpg",
            "https://example.com/image2.jpg"
        ]
    }
    </script>
</head>
<body>Content</body>
</html>
HTML;

    $result = $this->parser->parse($html);

    expect($result)->not->toBeNull()
        ->and($result['image'])->toBeArray()
        ->and($result['image'])->toHaveCount(2);
});

test('handles empty HTML', function () {
    $result = $this->parser->parse('');

    expect($result)->toBeNull();
});

test('handles HTML with only whitespace', function () {
    $result = $this->parser->parse('   ');

    expect($result)->toBeNull();
});

test('handles top-level array with Recipe object', function () {
    $html = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    [
        {
            "@context": "https://schema.org",
            "@type": ["Recipe"],
            "name": "Array Recipe",
            "recipeIngredient": ["flour", "sugar"],
            "recipeInstructions": "Mix and bake."
        }
    ]
    </script>
</head>
<body>Content</body>
</html>
HTML;

    $result = $this->parser->parse($html);

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBe('Array Recipe')
        ->and($result['recipeIngredient'])->toHaveCount(2);
});

test('handles Recipe with @type as array', function () {
    $html = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": ["Recipe", "Article"],
        "name": "Multi-type Recipe",
        "recipeIngredient": ["flour"],
        "recipeInstructions": "Cook"
    }
    </script>
</head>
<body>Content</body>
</html>
HTML;

    $result = $this->parser->parse($html);

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBe('Multi-type Recipe');
});

test('throws CloudflareBlockedException when "Just a moment..." detected', function () {
    $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Just a moment...</title>
</head>
<body>
    <h1>Checking your browser</h1>
</body>
</html>
HTML;

    expect(fn () => $this->parser->parse($html))
        ->toThrow(CloudflareBlockedException::class);
});

test('throws CloudflareBlockedException when cf-browser-verification detected', function () {
    $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Security Check</title>
</head>
<body>
    <div id="cf-browser-verification"></div>
</body>
</html>
HTML;

    expect(fn () => $this->parser->parse($html))
        ->toThrow(CloudflareBlockedException::class);
});

test('throws CloudflareBlockedException when challenge-platform detected', function () {
    $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Verify you are human</title>
</head>
<body>
    <div class="challenge-platform"></div>
</body>
</html>
HTML;

    expect(fn () => $this->parser->parse($html))
        ->toThrow(CloudflareBlockedException::class);
});

test('does not throw exception for normal recipe page', function () {
    $html = <<<'HTML'
<html>
<head>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Recipe",
        "name": "Normal Recipe"
    }
    </script>
</head>
<body>Just a delicious recipe...</body>
</html>
HTML;

    $result = $this->parser->parse($html);

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBe('Normal Recipe');
});
