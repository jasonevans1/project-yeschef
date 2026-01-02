<?php

namespace App\Services;

use App\Models\CommonItemTemplate;
use App\Models\UserItemTemplate;
use Illuminate\Support\Collection;

class ItemAutoCompleteService
{
    /**
     * Query autocomplete suggestions for a given search term.
     *
     * Returns up to 10 suggestions, prioritizing:
     * 1. User's personal templates (by usage_count DESC, last_used_at DESC)
     * 2. Common default templates (by usage_count DESC)
     *
     * Uses LIKE-based search with prefix matching first, then contains matching.
     */
    public function query(int $userId, string $searchTerm, int $limit = 10): Collection
    {
        if (empty(trim($searchTerm))) {
            return collect();
        }

        $searchTerm = strtolower(trim($searchTerm));

        // Query user templates first (personal history)
        $userTemplates = $this->queryUserTemplates($userId, $searchTerm, $limit);

        // If we have enough user templates, return them
        if ($userTemplates->count() >= $limit) {
            return $userTemplates->take($limit);
        }

        // Query common templates to fill remaining slots
        $remainingSlots = $limit - $userTemplates->count();
        $commonTemplates = $this->queryCommonTemplates($searchTerm, $remainingSlots);

        // Merge results, de-duplicating by name (user template wins)
        return $this->mergeAndDeduplicate($userTemplates, $commonTemplates, $limit);
    }

    /**
     * Query user's personal item templates.
     */
    protected function queryUserTemplates(int $userId, string $searchTerm, int $limit): Collection
    {
        // Prefix search first (faster, more relevant)
        $prefixResults = UserItemTemplate::where('user_id', $userId)
            ->where('name', 'like', $searchTerm.'%')
            ->orderByDesc('usage_count')
            ->orderByDesc('last_used_at')
            ->limit($limit)
            ->get();

        // If we have enough results, return them
        if ($prefixResults->count() >= $limit) {
            return $prefixResults;
        }

        // Otherwise, add contains search results (excluding prefix matches)
        $remainingSlots = $limit - $prefixResults->count();
        $containsResults = UserItemTemplate::where('user_id', $userId)
            ->where('name', 'like', '%'.$searchTerm.'%')
            ->where('name', 'not like', $searchTerm.'%') // Exclude prefix matches
            ->orderByDesc('usage_count')
            ->orderByDesc('last_used_at')
            ->limit($remainingSlots)
            ->get();

        return $prefixResults->merge($containsResults);
    }

    /**
     * Query common default item templates.
     */
    protected function queryCommonTemplates(string $searchTerm, int $limit): Collection
    {
        // Prefix search first (faster, more relevant)
        $prefixResults = CommonItemTemplate::where('name', 'like', $searchTerm.'%')
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get();

        // If we have enough results, return them
        if ($prefixResults->count() >= $limit) {
            return $prefixResults;
        }

        // Otherwise, add contains search results (excluding prefix matches)
        $remainingSlots = $limit - $prefixResults->count();
        $containsResults = CommonItemTemplate::where('name', 'like', '%'.$searchTerm.'%')
            ->where('name', 'not like', $searchTerm.'%') // Exclude prefix matches
            ->orderByDesc('usage_count')
            ->limit($remainingSlots)
            ->get();

        return $prefixResults->merge($containsResults);
    }

    /**
     * Merge user and common templates, removing duplicates.
     *
     * User templates take precedence over common templates with the same name.
     */
    protected function mergeAndDeduplicate(Collection $userTemplates, Collection $commonTemplates, int $limit): Collection
    {
        // Get user template names for de-duplication
        $userTemplateNames = $userTemplates->pluck('name')->map(fn ($name) => strtolower($name));

        // Filter common templates to exclude duplicates
        $filteredCommonTemplates = $commonTemplates->filter(function ($template) use ($userTemplateNames) {
            return ! $userTemplateNames->contains(strtolower($template->name));
        });

        // Merge and limit
        return $userTemplates->merge($filteredCommonTemplates)->take($limit);
    }

    /**
     * Format template for API response.
     */
    public function formatSuggestion($template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'category' => $template->category?->value,
            'unit' => $template->unit?->value,
            'default_quantity' => $template->default_quantity,
            'is_user_template' => $template instanceof UserItemTemplate,
        ];
    }
}
