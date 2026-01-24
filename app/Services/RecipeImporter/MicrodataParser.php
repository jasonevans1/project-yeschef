<?php

namespace App\Services\RecipeImporter;

use App\Exceptions\CloudflareBlockedException;
use App\Exceptions\MalformedRecipeDataException;
use App\Exceptions\MissingRecipeDataException;
use DOMDocument;
use JsonException;

class MicrodataParser
{
    /**
     * Parse HTML to extract Recipe microdata from JSON-LD.
     *
     * @param  string  $html  The HTML content to parse
     * @return array<string, mixed> The Recipe data
     *
     * @throws CloudflareBlockedException If Cloudflare challenge page detected
     * @throws MissingRecipeDataException If no recipe data found
     * @throws MalformedRecipeDataException If JSON-LD is malformed
     */
    public function parse(string $html): array
    {
        if (empty(trim($html))) {
            throw new MissingRecipeDataException;
        }

        // Detect Cloudflare challenge pages
        if ($this->isCloudflareChallenge($html)) {
            throw new CloudflareBlockedException;
        }

        $dom = new DOMDocument;
        @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

        $scriptTags = $dom->getElementsByTagName('script');
        $foundJsonLd = false;

        foreach ($scriptTags as $script) {
            if ($script->getAttribute('type') !== 'application/ld+json') {
                continue;
            }

            $foundJsonLd = true;
            $jsonContent = $script->textContent;

            // Remove comments (/* ... */)
            $jsonContent = preg_replace('#/\*.*?\*/#s', '', $jsonContent);

            // Remove newlines that can break JSON parsing
            $jsonContent = preg_replace("/\r|\n/", ' ', trim($jsonContent));

            // Decode JSON with error handling
            try {
                $data = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new MalformedRecipeDataException('Invalid JSON-LD format.');
            }

            // Handle top-level array (e.g., [{"@type": "Recipe", ...}])
            if (is_array($data) && isset($data[0]) && is_array($data[0])) {
                foreach ($data as $item) {
                    $recipe = $this->findRecipe($item);
                    if ($recipe !== null) {
                        return $this->normalizeRecipe($recipe);
                    }
                }

                continue;
            }

            // Check if this is a Recipe
            $recipe = $this->findRecipe($data);

            if ($recipe !== null) {
                return $this->normalizeRecipe($recipe);
            }
        }

        // No recipe found
        throw new MissingRecipeDataException;
    }

    /**
     * Check if the @type value contains "Recipe".
     *
     * @param  mixed  $type  The @type value (string or array)
     * @return bool True if the type is or contains "Recipe"
     */
    private function isRecipeType(mixed $type): bool
    {
        if (is_string($type)) {
            return $type === 'Recipe';
        }

        if (is_array($type)) {
            return in_array('Recipe', $type, true);
        }

        return false;
    }

    /**
     * Find a Recipe object in the JSON-LD data.
     *
     * @param  array<string, mixed>  $data  The decoded JSON-LD data
     * @return array<string, mixed>|null The Recipe data or null
     */
    private function findRecipe(array $data): ?array
    {
        // Direct Recipe object
        if (isset($data['@type']) && $this->isRecipeType($data['@type'])) {
            return $data;
        }

        // Recipe in @graph array
        if (isset($data['@graph']) && is_array($data['@graph'])) {
            foreach ($data['@graph'] as $item) {
                if (is_array($item) && isset($item['@type']) && $this->isRecipeType($item['@type'])) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * Normalize Recipe data by processing instructions and images.
     *
     * @param  array<string, mixed>  $recipe  The raw Recipe data
     * @return array<string, mixed> The normalized Recipe data
     */
    private function normalizeRecipe(array $recipe): array
    {
        // Normalize recipeInstructions if it's an array of HowToStep objects
        if (isset($recipe['recipeInstructions']) && is_array($recipe['recipeInstructions'])) {
            $instructions = [];
            foreach ($recipe['recipeInstructions'] as $step) {
                if (is_array($step) && isset($step['text'])) {
                    $instructions[] = $step;
                }
            }
            // Keep as array if it contains HowToStep objects
            if (! empty($instructions)) {
                $recipe['recipeInstructions'] = $instructions;
            }
        }

        return $recipe;
    }

    /**
     * Detect if the HTML is a Cloudflare challenge page.
     */
    private function isCloudflareChallenge(string $html): bool
    {
        // Check for common Cloudflare challenge indicators
        $indicators = [
            'Just a moment...',
            'Checking your browser',
            'cf-browser-verification',
            'cf_chl_opt',
            'challenge-platform',
        ];

        foreach ($indicators as $indicator) {
            if (str_contains($html, $indicator)) {
                return true;
            }
        }

        return false;
    }
}
