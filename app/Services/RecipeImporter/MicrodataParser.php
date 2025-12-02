<?php

namespace App\Services\RecipeImporter;

use DOMDocument;

class MicrodataParser
{
    /**
     * Parse HTML to extract Recipe microdata from JSON-LD.
     *
     * @param  string  $html  The HTML content to parse
     * @return array<string, mixed>|null The Recipe data or null if not found
     */
    public function parse(string $html): ?array
    {
        if (empty(trim($html))) {
            return null;
        }

        $dom = new DOMDocument;
        @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

        $scriptTags = $dom->getElementsByTagName('script');

        foreach ($scriptTags as $script) {
            if ($script->getAttribute('type') !== 'application/ld+json') {
                continue;
            }

            $jsonContent = $script->textContent;

            // Remove comments (/* ... */)
            $jsonContent = preg_replace('#/\*.*?\*/#s', '', $jsonContent);

            // Remove newlines that can break JSON parsing
            $jsonContent = preg_replace("/\r|\n/", ' ', trim($jsonContent));

            // Decode JSON
            $data = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }

            // Check if this is a Recipe
            $recipe = $this->findRecipe($data);

            if ($recipe !== null) {
                return $this->normalizeRecipe($recipe);
            }
        }

        return null;
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
        if (isset($data['@type']) && $data['@type'] === 'Recipe') {
            return $data;
        }

        // Recipe in @graph array
        if (isset($data['@graph']) && is_array($data['@graph'])) {
            foreach ($data['@graph'] as $item) {
                if (is_array($item) && isset($item['@type']) && $item['@type'] === 'Recipe') {
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
}
