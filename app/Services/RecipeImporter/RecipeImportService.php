<?php

namespace App\Services\RecipeImporter;

use App\Exceptions\MalformedRecipeDataException;

class RecipeImportService
{
    public function __construct(
        protected RecipeFetcher $fetcher,
        protected MicrodataParser $parser
    ) {}

    /**
     * Fetch HTML from URL, parse recipe microdata, and transform to normalized array.
     *
     * @return array<string, mixed> The transformed recipe data
     *
     * @throws \App\Exceptions\NetworkTimeoutException If request times out
     * @throws \App\Exceptions\InvalidHTTPStatusException If HTTP status is not 2xx
     * @throws \App\Exceptions\CloudflareBlockedException If Cloudflare challenge detected
     * @throws \App\Exceptions\MissingRecipeDataException If no recipe data found
     * @throws \App\Exceptions\MalformedRecipeDataException If recipe data is invalid or incomplete
     * @throws \Exception If connection fails
     */
    public function fetchAndParse(string $url): array
    {
        // Fetch HTML (throws exceptions on failure)
        $html = $this->fetcher->fetch($url);

        // Parse microdata (throws exceptions on failure)
        $recipeData = $this->parser->parse($html);

        // Transform to application schema
        $transformed = $this->transform($recipeData);

        // Validate required fields
        $this->validateRequiredFields($transformed);

        return $transformed;
    }

    /**
     * Transform schema.org Recipe data to application schema.
     *
     * @param  array<string, mixed>  $recipeData
     * @return array<string, mixed>
     */
    protected function transform(array $recipeData): array
    {
        $transformed = [];

        // Required fields
        $transformed['name'] = $recipeData['name'] ?? null;
        $transformed['instructions'] = $this->flattenInstructions($recipeData['recipeInstructions'] ?? '');

        // Optional text fields
        $transformed['description'] = $recipeData['description'] ?? null;
        $transformed['cuisine'] = $this->extractStringValue($recipeData['recipeCuisine'] ?? null);

        // Time fields - parse ISO 8601 durations
        $transformed['prep_time'] = $this->parseIsoDuration($recipeData['prepTime'] ?? null);
        $transformed['cook_time'] = $this->parseIsoDuration($recipeData['cookTime'] ?? null);

        // Servings - parse yield
        $transformed['servings'] = $this->parseServings($recipeData['recipeYield'] ?? 4);

        // Category - map to meal_type enum
        $transformed['meal_type'] = $this->mapCategory($recipeData['recipeCategory'] ?? null);

        // Image - extract URL from string or array
        $transformed['image_url'] = $this->extractImageUrl($recipeData['image'] ?? null);

        // Ingredients - preserve array for later processing
        $transformed['recipeIngredient'] = $recipeData['recipeIngredient'] ?? [];

        return $transformed;
    }

    /**
     * Parse ISO 8601 duration to minutes.
     */
    protected function parseIsoDuration(?string $duration): ?int
    {
        if (! $duration) {
            return null;
        }

        // Match patterns like PT1H30M, PT45M, PT2H
        if (! preg_match('/^PT(?:(\d+)H)?(?:(\d+)M)?$/i', $duration, $matches)) {
            return null;
        }

        $hours = isset($matches[1]) ? (int) $matches[1] : 0;
        $minutes = isset($matches[2]) ? (int) $matches[2] : 0;

        return ($hours * 60) + $minutes;
    }

    /**
     * Parse recipe yield to servings integer.
     */
    protected function parseServings(mixed $yield): int
    {
        if (is_int($yield)) {
            return $yield;
        }

        if (is_string($yield)) {
            // Extract first number found
            if (preg_match('/(\d+)/', $yield, $matches)) {
                return (int) $matches[1];
            }
        }

        if (is_array($yield) && ! empty($yield)) {
            // Try first element
            return $this->parseServings($yield[0]);
        }

        return 4; // Default fallback
    }

    /**
     * Flatten recipe instructions to text.
     */
    protected function flattenInstructions(mixed $instructions): string
    {
        if (is_string($instructions)) {
            return $instructions;
        }

        if (is_array($instructions)) {
            $steps = [];
            foreach ($instructions as $index => $step) {
                if (is_string($step)) {
                    $steps[] = ($index + 1).'. '.$step;
                } elseif (is_array($step) && isset($step['text'])) {
                    $steps[] = ($index + 1).'. '.$step['text'];
                }
            }

            return implode("\n", $steps);
        }

        return '';
    }

    /**
     * Extract string value from string or array.
     */
    protected function extractStringValue(string|array|null $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value) && ! empty($value) && is_string($value[0])) {
            return $value[0];
        }

        return null;
    }

    /**
     * Map recipe category to meal_type enum.
     */
    protected function mapCategory(string|array|null $category): ?string
    {
        if (! $category) {
            return null;
        }

        // Handle array - try first element
        if (is_array($category)) {
            if (empty($category)) {
                return null;
            }
            $category = $category[0];
        }

        if (! is_string($category)) {
            return null;
        }

        $category = strtolower($category);

        $mapping = [
            'breakfast' => 'breakfast',
            'brunch' => 'breakfast',
            'lunch' => 'lunch',
            'dinner' => 'dinner',
            'supper' => 'dinner',
            'snack' => 'snack',
            'appetizer' => 'snack',
            'dessert' => 'snack',
        ];

        return $mapping[$category] ?? null;
    }

    /**
     * Extract image URL from string, array, or ImageObject.
     */
    protected function extractImageUrl(mixed $image): ?string
    {
        if (is_string($image)) {
            return $image;
        }

        if (is_array($image)) {
            // Handle ImageObject (e.g., {"@type": "ImageObject", "url": "..."})
            if (isset($image['url']) && is_string($image['url'])) {
                return $image['url'];
            }

            // Handle array of URLs or ImageObjects
            if (! empty($image) && isset($image[0])) {
                return $this->extractImageUrl($image[0]);
            }
        }

        return null;
    }

    /**
     * Validate that required fields are present and not empty.
     *
     * @param  array<string, mixed>  $recipeData
     *
     * @throws MalformedRecipeDataException If required fields are missing or empty
     */
    protected function validateRequiredFields(array $recipeData): void
    {
        $required = ['name', 'instructions', 'recipeIngredient'];
        $missing = [];

        foreach ($required as $field) {
            if (empty($recipeData[$field])) {
                $missing[] = $field;
            }
        }

        if (! empty($missing)) {
            throw new MalformedRecipeDataException(
                'Missing required fields: '.implode(', ', $missing)
            );
        }
    }
}
