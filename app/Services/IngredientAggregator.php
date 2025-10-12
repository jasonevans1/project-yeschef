<?php

namespace App\Services;

use App\Enums\MeasurementUnit;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class IngredientAggregator
{
    public function __construct(
        private UnitConverter $unitConverter
    ) {}

    /**
     * Aggregate ingredients by combining identical items with compatible units
     *
     * @param  Collection  $items  Collection of ingredient arrays with keys: name, quantity, unit, category
     * @return Collection Aggregated collection with combined quantities
     */
    public function aggregate(Collection $items): Collection
    {
        if ($items->isEmpty()) {
            return collect([]);
        }

        // Group by ingredient name (case-insensitive)
        $grouped = $items->groupBy(function ($item) {
            return strtolower($item['name']);
        });

        $aggregated = collect();

        foreach ($grouped as $ingredientName => $group) {
            // Try to aggregate items with compatible units
            $result = $this->aggregateGroup($group);
            $aggregated = $aggregated->merge($result);
        }

        return $aggregated->values();
    }

    /**
     * Aggregate a group of items with the same ingredient name
     */
    private function aggregateGroup(Collection $group): Collection
    {
        if ($group->count() === 1) {
            return $group;
        }

        // Group by unit compatibility
        $byUnit = collect();

        foreach ($group as $item) {
            $unit = $item['unit'];
            $unitType = $this->getUnitType($unit);

            // Find existing group with compatible unit
            $compatibleGroup = null;

            foreach ($byUnit as $existingUnitType => $existingGroup) {
                if ($unitType === $existingUnitType) {
                    $compatibleGroup = $existingUnitType;
                    break;
                }
            }

            if ($compatibleGroup !== null) {
                $byUnit[$compatibleGroup]->push($item);
            } else {
                $byUnit[$unitType] = collect([$item]);
            }
        }

        // Aggregate each compatible group
        $result = collect();

        foreach ($byUnit as $unitType => $compatibleItems) {
            if ($compatibleItems->count() === 1) {
                $result->push($compatibleItems->first());
            } else {
                $result->push($this->aggregateCompatibleItems($compatibleItems));
            }
        }

        return $result;
    }

    /**
     * Aggregate items with compatible units
     */
    private function aggregateCompatibleItems(Collection $items): array
    {
        // Use the first item's unit as the base
        $baseItem = $items->first();
        $baseUnit = $baseItem['unit'];

        $totalQuantity = 0;

        foreach ($items as $item) {
            $quantity = $item['quantity'];
            $unit = $item['unit'];

            // If same unit, just add
            if ($unit === $baseUnit) {
                $totalQuantity += $quantity;
            } else {
                // Try to convert to base unit
                try {
                    $converted = $this->unitConverter->convert($quantity, $unit, $baseUnit);
                    $totalQuantity += $converted;
                } catch (InvalidArgumentException $e) {
                    // Units are incompatible, this shouldn't happen
                    // as we grouped by unit type, but handle gracefully
                    $totalQuantity += $quantity;
                }
            }
        }

        return [
            'name' => $baseItem['name'],
            'quantity' => $totalQuantity,
            'unit' => $baseUnit,
            'category' => $baseItem['category'],
        ];
    }

    /**
     * Determine the unit type for grouping compatible units
     */
    private function getUnitType(MeasurementUnit $unit): string
    {
        $value = $unit->value;

        // Volume units
        if (in_array($value, ['tsp', 'tbsp', 'fl_oz', 'cup', 'pint', 'quart', 'gallon', 'ml', 'liter'])) {
            return 'volume';
        }

        // Weight units
        if (in_array($value, ['oz', 'lb', 'gram', 'kg'])) {
            return 'weight';
        }

        // Count units
        if (in_array($value, ['whole', 'clove', 'slice', 'piece'])) {
            return 'count_'.$value; // Each count unit is its own group
        }

        // Non-standard units
        if (in_array($value, ['pinch', 'dash', 'to_taste'])) {
            return 'non_standard_'.$value; // Each non-standard unit is its own group
        }

        return 'unknown_'.$value;
    }
}
