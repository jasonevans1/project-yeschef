<?php

declare(strict_types=1);

namespace App\Livewire\Recipes;

use App\Exceptions\BlockedUrlException;
use App\Exceptions\CloudflareBlockedException;
use App\Exceptions\InvalidHTTPStatusException;
use App\Exceptions\MalformedRecipeDataException;
use App\Exceptions\MissingRecipeDataException;
use App\Exceptions\NetworkTimeoutException;
use App\Services\RecipeImporter\RecipeImportService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Import extends Component
{
    #[Validate('required|url:http,https|max:2048')]
    public string $url = '';

    public function import(RecipeImportService $importService): void
    {
        $this->validate();

        $rateLimitKey = 'recipe-import:'.auth()->id();

        if (RateLimiter::tooManyAttempts($rateLimitKey, config('recipe-import.rate_limit.max_attempts'))) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $this->addError('url', __('Too many import attempts. Please try again in :seconds seconds.', ['seconds' => $seconds]));

            return;
        }

        RateLimiter::hit($rateLimitKey, config('recipe-import.rate_limit.decay_seconds'));

        try {
            $recipeData = $importService->fetchAndParse($this->url);

            $recipeData['source_url'] = $this->url;
            $cacheKey = 'recipe_import_preview:'.auth()->id();
            Cache::put($cacheKey, $recipeData, now()->addMinutes(10));

            $verified = Cache::get($cacheKey);
            if (empty($verified)) {
                logger()->error('Cache verification failed immediately after save', [
                    'url' => $this->url,
                    'cache_key' => $cacheKey,
                    'user_id' => auth()->id(),
                    'data_size_bytes' => strlen(json_encode($recipeData)),
                ]);
                $this->addError('url', 'Unable to store recipe data. Please try again.');

                return;
            }

            logger()->info('Recipe import data cached', [
                'url' => $this->url,
                'cache_key' => $cacheKey,
                'user_id' => auth()->id(),
                'data_size_bytes' => strlen(json_encode($recipeData)),
                'recipe_name' => $recipeData['name'] ?? null,
                'ingredient_count' => count($recipeData['recipeIngredient'] ?? []),
            ]);

            $this->redirect(route('recipes.import.preview'));
        } catch (BlockedUrlException $e) {
            $this->addError('url', $e->getMessage());
        } catch (NetworkTimeoutException $e) {
            $this->addError('url', $e->getMessage());
        } catch (InvalidHTTPStatusException $e) {
            $this->addError('url', $e->getMessage());
        } catch (CloudflareBlockedException $e) {
            $this->addError('url', $e->getMessage());
        } catch (MissingRecipeDataException $e) {
            $this->addError('url', $e->getMessage());
        } catch (MalformedRecipeDataException $e) {
            $this->addError('url', $e->getMessage());
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'Could not connect')) {
                $this->addError('url', $e->getMessage());

                return;
            }

            logger()->error('Unexpected import error', [
                'url' => $this->url,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addError('url', 'An unexpected error occurred. Please try again or contact support.');
        }
    }

    public function render(): View
    {
        return view('livewire.recipes.import');
    }
}
