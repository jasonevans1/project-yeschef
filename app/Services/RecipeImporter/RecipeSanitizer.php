<?php

namespace App\Services\RecipeImporter;

class RecipeSanitizer
{
    /**
     * Sanitize recipe data by removing HTML tags, validating URLs, and enforcing field length limits.
     *
     * @param  array<string, mixed>  $data  The recipe data to sanitize
     * @return array<string, mixed> The sanitized recipe data
     */
    public function sanitize(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $sanitized = [];

        // Sanitize text fields
        foreach (['name', 'description', 'instructions', 'cuisine'] as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = $this->sanitizeText($data[$field]);
            }
        }

        // Apply field-specific length limits
        if (isset($sanitized['name'])) {
            $sanitized['name'] = $this->truncate($sanitized['name'], 255);
        }

        if (isset($sanitized['description'])) {
            $sanitized['description'] = $this->truncate($sanitized['description'], 5000);
        }

        if (isset($sanitized['cuisine'])) {
            $sanitized['cuisine'] = $this->truncate($sanitized['cuisine'], 100);
        }

        // Sanitize URLs
        foreach (['source_url', 'image_url'] as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = $this->sanitizeUrl($data[$field]);
            }
        }

        // Handle image field (can be string or array)
        if (isset($data['image']) && ! isset($sanitized['image_url'])) {
            $sanitized['image_url'] = $this->extractImageUrl($data['image']);
        }

        // Sanitize ingredient array
        if (isset($data['recipeIngredient']) && is_array($data['recipeIngredient'])) {
            $sanitized['recipeIngredient'] = array_map(
                fn ($ingredient) => $this->sanitizeText($ingredient),
                $data['recipeIngredient']
            );
        }

        // Preserve numeric and enum fields as-is
        foreach (['prep_time', 'cook_time', 'servings', 'meal_type'] as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = $data[$field];
            }
        }

        // Preserve other fields that may exist
        foreach ($data as $key => $value) {
            if (! array_key_exists($key, $sanitized) && ! in_array($key, ['image'])) {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Strip HTML tags from text and handle null values.
     */
    private function sanitizeText(mixed $text): ?string
    {
        if ($text === null) {
            return null;
        }

        if (! is_string($text)) {
            return (string) $text;
        }

        return strip_tags($text);
    }

    /**
     * Validate and sanitize a URL.
     */
    private function sanitizeUrl(mixed $url): ?string
    {
        if ($url === null || ! is_string($url)) {
            return null;
        }

        // Truncate to max length
        $url = $this->truncate($url, 2048);

        // Allow data URLs for inline images
        if (str_starts_with($url, 'data:image/')) {
            return $url;
        }

        // Block dangerous protocols before validation
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme && in_array(strtolower($scheme), ['javascript', 'vbscript', 'file'])) {
            return null;
        }

        // Validate URL format
        $validated = filter_var($url, FILTER_VALIDATE_URL);

        if ($validated === false) {
            return null;
        }

        return $validated;
    }

    /**
     * Extract first valid image URL from array or string.
     */
    private function extractImageUrl(mixed $image): ?string
    {
        if (is_string($image)) {
            return $this->sanitizeUrl($image);
        }

        if (is_array($image)) {
            foreach ($image as $url) {
                $sanitized = $this->sanitizeUrl($url);
                if ($sanitized !== null) {
                    return $sanitized;
                }
            }
        }

        return null;
    }

    /**
     * Truncate string to specified length.
     */
    private function truncate(?string $text, int $maxLength): ?string
    {
        if ($text === null) {
            return null;
        }

        if (strlen($text) <= $maxLength) {
            return $text;
        }

        return substr($text, 0, $maxLength);
    }
}
