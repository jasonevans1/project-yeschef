<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ServingSizeScaler
{
    /**
     * Scale a quantity by a multiplier
     *
     * @param  float  $quantity  The original quantity
     * @param  float  $multiplier  The scaling factor (e.g., 1.5 for 1.5x)
     * @return float The scaled quantity
     */
    public function scale(float $quantity, float $multiplier): float
    {
        return $quantity * $multiplier;
    }

    /**
     * Scale a collection of ingredients by a multiplier
     *
     * @param  Collection  $ingredients  Collection of ingredient arrays with 'quantity' key
     * @param  float  $multiplier  The scaling factor
     * @return Collection The scaled ingredients collection
     */
    public function scaleIngredients(Collection $ingredients, float $multiplier): Collection
    {
        return $ingredients->map(function ($ingredient) use ($multiplier) {
            if (isset($ingredient['quantity'])) {
                $ingredient['quantity'] = $this->scale($ingredient['quantity'], $multiplier);
            }

            return $ingredient;
        });
    }
}
