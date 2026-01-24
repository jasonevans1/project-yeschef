# Quickstart Implementation Guide

**Feature**: Multi-Recipe Meal Slots with Recipe Drawer
**Date**: 2025-12-14
**Branch**: `001-meal-plan-drawer`

This guide provides a step-by-step walkthrough for implementing the feature following test-first development principles.

---

## Prerequisites

- ✅ Branch created: `001-meal-plan-drawer`
- ✅ Specification complete: `spec.md`
- ✅ Implementation plan complete: `plan.md`
- ✅ Research complete: `research.md`
- ✅ Data model designed: `data-model.md`
- ✅ DDEV environment running: `ddev start`

---

## Implementation Workflow

### Step 1: Create Database Migration

**Goal**: Remove unique constraint to allow multiple recipes per meal slot

**Commands**:
```bash
# Create migration file
php artisan make:migration remove_unique_constraint_from_meal_assignments

# Edit the migration file at:
# database/migrations/YYYY_MM_DD_HHMMSS_remove_unique_constraint_from_meal_assignments.php
```

**Migration Code** (copy to generated file):
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_assignments', function (Blueprint $table) {
            $table->dropUnique(['meal_plan_id', 'date', 'meal_type']);
        });
    }

    public function down(): void
    {
        Schema::table('meal_assignments', function (Blueprint $table) {
            $table->unique(['meal_plan_id', 'date', 'meal_type']);
        });
    }
};
```

**Run Migration**:
```bash
# Run migration
php artisan migrate

# Verify in database
php artisan tinker
>>> Schema::getIndexes('meal_assignments')  # Should NOT show unique constraint
```

**Expected Output**: Migration runs successfully, unique constraint removed

---

### Step 2: Write Failing Tests (Test-First)

**Goal**: Create tests that FAIL before implementation, PASS after

#### 2.1 Update AssignRecipesTest.php

**File**: `tests/Feature/MealPlans/AssignRecipesTest.php`

**Changes**:
1. **Replace** "prevents duplicate assignments" test (lines 61-89)
2. **Remove** "reassigns recipe" test (lines 91-117)

**New Test** (replaces duplicate prevention):
```php
it('allows multiple recipes in same meal slot', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => today(),
        'end_date' => today()->addDays(7),
    ]);
    $recipe1 = Recipe::factory()->create(['name' => 'Pasta']);
    $recipe2 = Recipe::factory()->create(['name' => 'Salad']);

    $this->actingAs($user);

    // Assign first recipe to Monday lunch
    Livewire::test(Show::class, ['mealPlan' => $mealPlan])
        ->set('selectedDate', today())
        ->set('selectedMealType', MealType::LUNCH)
        ->set('servingMultiplier', 1.0)
        ->call('assignRecipe', $recipe1)
        ->assertHasNoErrors();

    // Assign second recipe to same slot (should succeed)
    Livewire::test(Show::class, ['mealPlan' => $mealPlan])
        ->set('selectedDate', today())
        ->set('selectedMealType', MealType::LUNCH)
        ->set('servingMultiplier', 1.5)
        ->call('assignRecipe', $recipe2)
        ->assertHasNoErrors();

    // Verify both assignments exist
    expect(MealAssignment::count())->toBe(2);

    $assignments = MealAssignment::where([
        'meal_plan_id' => $mealPlan->id,
        'date' => today(),
        'meal_type' => MealType::LUNCH,
    ])->get();

    expect($assignments)->toHaveCount(2);
    expect($assignments->pluck('recipe_id'))->toContain($recipe1->id, $recipe2->id);
});
```

#### 2.2 Add Tests to ViewMealPlanTest.php

**File**: `tests/Feature/MealPlans/ViewMealPlanTest.php`

**New Tests** (add to existing file):
```php
it('displays multiple recipes in same meal slot', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $recipe1 = Recipe::factory()->create(['name' => 'Pasta']);
    $recipe2 = Recipe::factory()->create(['name' => 'Salad']);

    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe1->id,
        'date' => today(),
        'meal_type' => MealType::LUNCH,
    ]);

    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe2->id,
        'date' => today(),
        'meal_type' => MealType::LUNCH,
    ]);

    Livewire::test(Show::class, ['mealPlan' => $mealPlan])
        ->assertSee('Pasta')
        ->assertSee('Salad');
});

it('displays recipes in chronological order by creation time', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $recipe1 = Recipe::factory()->create(['name' => 'First Recipe']);
    $recipe2 = Recipe::factory()->create(['name' => 'Second Recipe']);
    $recipe3 = Recipe::factory()->create(['name' => 'Third Recipe']);

    // Create assignments with slight time delays
    $assignment1 = MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe1->id,
        'date' => today(),
        'meal_type' => MealType::DINNER,
        'created_at' => now()->subMinutes(10),
    ]);

    $assignment2 = MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe2->id,
        'date' => today(),
        'meal_type' => MealType::DINNER,
        'created_at' => now()->subMinutes(5),
    ]);

    $assignment3 = MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe3->id,
        'date' => today(),
        'meal_type' => MealType::DINNER,
        'created_at' => now(),
    ]);

    $component = Livewire::test(Show::class, ['mealPlan' => $mealPlan]);

    // Get rendered order from component's grouped assignments
    $assignments = $component->viewData('assignments');
    $dinnerKey = today()->format('Y-m-d') . '_dinner';
    $dinnerRecipes = $assignments->get($dinnerKey);

    expect($dinnerRecipes)->toHaveCount(3);
    expect($dinnerRecipes->first()->id)->toBe($assignment1->id);
    expect($dinnerRecipes->skip(1)->first()->id)->toBe($assignment2->id);
    expect($dinnerRecipes->last()->id)->toBe($assignment3->id);
});

it('can open recipe drawer with correct state', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $recipe = Recipe::factory()->create();
    $assignment = MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
    ]);

    Livewire::test(Show::class, ['mealPlan' => $mealPlan])
        ->call('openRecipeDrawer', $assignment)
        ->assertSet('selectedAssignmentId', $assignment->id)
        ->assertSet('showRecipeDrawer', true);
});

it('can close recipe drawer', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $recipe = Recipe::factory()->create();
    $assignment = MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
    ]);

    Livewire::test(Show::class, ['mealPlan' => $mealPlan])
        ->call('openRecipeDrawer', $assignment)
        ->assertSet('showRecipeDrawer', true)
        ->call('closeRecipeDrawer')
        ->assertSet('showRecipeDrawer', false)
        ->assertSet('selectedAssignmentId', null);
});

it('calculates scaled ingredient quantities correctly', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $recipe = Recipe::factory()->create();
    $ingredient = Ingredient::factory()->create(['name' => 'Flour']);

    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 2.5,
        'unit' => MeasurementUnit::CUP,
    ]);

    $assignment = MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'serving_multiplier' => 2.0,
    ]);

    $component = Livewire::test(Show::class, ['mealPlan' => $mealPlan])
        ->call('openRecipeDrawer', $assignment);

    $scaledIngredients = $component->viewData('this')->scaledIngredients;

    expect($scaledIngredients)->toHaveCount(1);
    expect($scaledIngredients[0]['name'])->toBe('Flour');
    expect($scaledIngredients[0]['quantity'])->toBe('5');  // 2.5 * 2.0 = 5.0, formatted as "5"
    expect($scaledIngredients[0]['unit'])->toBe('cup');
});

it('formats scaled quantities without trailing zeros', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $recipe = Recipe::factory()->create();
    $ingredient = Ingredient::factory()->create(['name' => 'Sugar']);

    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 1.333,
        'unit' => MeasurementUnit::CUP,
    ]);

    $assignment = MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'serving_multiplier' => 1.5,
    ]);

    $component = Livewire::test(Show::class, ['mealPlan' => $mealPlan])
        ->call('openRecipeDrawer', $assignment);

    $scaledIngredients = $component->viewData('this')->scaledIngredients;

    // 1.333 * 1.5 = 1.9995 ≈ 2.000, formatted as "2"
    expect($scaledIngredients[0]['quantity'])->toBe('2');
});
```

**Run Tests** (should FAIL):
```bash
php artisan test --filter=MealPlans
```

**Expected Output**: Tests fail because methods don't exist yet

---

### Step 3: Implement Livewire Component Changes

**Goal**: Make tests pass by implementing Livewire component logic

**File**: `app/Livewire/MealPlans/Show.php`

#### 3.1 Add New Properties

**Add after existing properties** (around line 20):
```php
public ?int $selectedAssignmentId = null;
public bool $showRecipeDrawer = false;
```

#### 3.2 Add New Methods

**Add after existing methods** (around line 140):
```php
public function openRecipeDrawer(MealAssignment $assignment): void
{
    $this->authorize('view', $this->mealPlan);

    // Eager load recipe with all ingredients
    $assignment->load(['recipe.recipeIngredients.ingredient']);

    $this->selectedAssignmentId = $assignment->id;
    $this->showRecipeDrawer = true;
}

public function closeRecipeDrawer(): void
{
    $this->showRecipeDrawer = false;
    $this->selectedAssignmentId = null;
}
```

#### 3.3 Add Computed Properties

**Add after methods** (around line 160):
```php
public function getSelectedAssignmentProperty(): ?MealAssignment
{
    if (!$this->selectedAssignmentId) {
        return null;
    }

    return MealAssignment::with([
        'recipe.recipeIngredients.ingredient'
    ])->find($this->selectedAssignmentId);
}

public function getScaledIngredientsProperty(): array
{
    if (!$this->selectedAssignment) {
        return [];
    }

    return $this->selectedAssignment->recipe->recipeIngredients->map(function ($recipeIngredient) {
        $scaledQuantity = $recipeIngredient->quantity * $this->selectedAssignment->serving_multiplier;

        // Format using RecipeIngredient display pattern (3 decimals max, no trailing zeros)
        $formatted = number_format($scaledQuantity, 3, '.', '');
        $formatted = rtrim($formatted, '0');
        $formatted = rtrim($formatted, '.');

        return [
            'name' => $recipeIngredient->ingredient->name,
            'quantity' => $formatted,
            'unit' => $recipeIngredient->unit->value,
            'notes' => $recipeIngredient->notes,
        ];
    })->toArray();
}
```

#### 3.4 Modify assignRecipe() Method

**Find** `assignRecipe()` method (around line 54-100)

**Replace** the existing assignment logic:
```php
public function assignRecipe(Recipe $recipe): void
{
    $this->authorize('update', $this->mealPlan);

    $this->validate([
        'servingMultiplier' => 'required|numeric|min:0.25|max:10',
    ]);

    // Validate date is within meal plan range
    if ($this->selectedDate < $this->mealPlan->start_date || $this->selectedDate > $this->mealPlan->end_date) {
        $this->addError('selectedDate', 'Date must be within the meal plan date range.');
        return;
    }

    // Always create new assignment (allow multiple recipes per slot)
    MealAssignment::create([
        'meal_plan_id' => $this->mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => $this->selectedDate,
        'meal_type' => $this->selectedMealType,
        'serving_multiplier' => $this->servingMultiplier,
    ]);

    session()->flash('success', 'Recipe assigned to meal plan successfully.');

    $this->closeRecipeSelector();
}
```

#### 3.5 Modify render() Method

**Find** `render()` method (around line 142-164)

**Update** grouping logic:
```php
public function render()
{
    $mealPlan = $this->mealPlan->load(['mealAssignments.recipe']);

    // Group assignments by slot (date_mealType) and sort within groups by created_at
    $assignments = $mealPlan->mealAssignments
        ->groupBy(fn($a) => $a->date->format('Y-m-d').'_'.$a->meal_type->value)
        ->map(fn($group) => $group->sortBy('created_at'));

    return view('livewire.meal-plans.show', [
        'assignments' => $assignments,
        'recipes' => $this->recipes,
    ]);
}
```

**Run Tests** (should PASS for component logic):
```bash
php artisan test --filter=MealPlans
```

---

### Step 4: Update Blade View

**Goal**: Update UI to display multiple recipes and add drawer component

**File**: `resources/views/livewire/meal-plans/show.blade.php`

#### 4.1 Update Meal Slot Cells

**Find** meal slot rendering (around lines 74-130)

**Replace** with collection loop:
```blade
@php
    $key = $date->format('Y-m-d') . '_' . $mealType->value;
    $assignmentCollection = $assignments->get($key) ?? collect();
@endphp

<td class="border border-gray-200 dark:border-gray-700 p-2 align-top">
    <div class="min-h-[60px] space-y-2">
        @forelse($assignmentCollection as $assignment)
            <div wire:key="assignment-{{ $assignment->id }}"
                 class="group relative">
                <button
                    type="button"
                    wire:click="openRecipeDrawer({{ $assignment->id }})"
                    @keydown.enter="$wire.openRecipeDrawer({{ $assignment->id }})"
                    @keydown.space.prevent="$wire.openRecipeDrawer({{ $assignment->id }})"
                    class="w-full text-left p-2 rounded-lg border border-gray-200 dark:border-gray-700
                           bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700
                           focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                >
                    <div class="font-medium text-blue-600 dark:text-blue-400">
                        {{ $assignment->recipe->name }}
                    </div>

                    @if($assignment->serving_multiplier != 1.00)
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            {{ $assignment->recipe->servings }} servings × {{ $assignment->serving_multiplier }}
                        </div>
                    @endif

                    @if($assignment->notes)
                        <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            {{ Str::limit($assignment->notes, 50) }}
                        </div>
                    @endif
                </button>

                {{-- Remove button (shows on hover) --}}
                <button
                    type="button"
                    wire:click="removeAssignment({{ $assignment->id }})"
                    wire:confirm="Remove this recipe from the meal plan?"
                    class="absolute top-1 right-1 p-1 rounded-full bg-red-500 text-white
                           opacity-0 group-hover:opacity-100 transition-opacity
                           hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500"
                    aria-label="Remove recipe"
                >
                    <flux:icon.x-mark class="size-4" />
                </button>
            </div>
        @empty
            {{-- Empty slot --}}
        @endforelse

        {{-- Add Recipe button (always visible) --}}
        <button
            type="button"
            wire:click="openRecipeSelector('{{ $date->format('Y-m-d') }}', '{{ $mealType->value }}')"
            class="w-full p-2 border-2 border-dashed border-gray-300 dark:border-gray-600
                   rounded-lg text-gray-500 dark:text-gray-400 hover:border-blue-500
                   hover:text-blue-600 dark:hover:text-blue-400 transition-colors
                   focus:outline-none focus:ring-2 focus:ring-blue-500
                   @if($assignmentCollection->isEmpty()) min-h-[60px] @endif"
        >
            <flux:icon.plus class="size-5 mx-auto" />
            <span class="text-sm">
                {{ $assignmentCollection->isEmpty() ? 'Add Recipe' : 'Add Another' }}
            </span>
        </button>
    </div>
</td>
```

#### 4.2 Add Drawer Component

**Add after line 226** (after recipe selector modal):
```blade
{{-- Recipe Drawer --}}
<div
    x-data="{ show: @entangle('showRecipeDrawer') }"
    x-show="show"
    @keydown.escape.window="$wire.closeRecipeDrawer()"
    style="display: none;"
    class="fixed inset-0 z-50 overflow-hidden"
>
    {{-- Backdrop --}}
    <div x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/50"
         @click="$wire.closeRecipeDrawer()">
    </div>

    {{-- Drawer Panel --}}
    <div x-show="show"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         x-trap="show"
         role="dialog"
         aria-modal="true"
         aria-labelledby="drawer-title"
         class="fixed right-0 top-0 bottom-0 w-full max-w-md sm:max-w-lg
                bg-white dark:bg-gray-900 shadow-xl overflow-y-auto flex flex-col">

        @if($this->selectedAssignment)
            {{-- Sticky Header --}}
            <div class="sticky top-0 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 p-4 z-10">
                <div class="flex items-start justify-between">
                    <div class="flex-1 pr-4">
                        <h2 id="drawer-title" class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $this->selectedAssignment->recipe->name }}
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ $this->selectedAssignment->date->format('l, F j, Y') }}
                            • {{ Str::title($this->selectedAssignment->meal_type->value) }}
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="closeRecipeDrawer"
                        x-ref="closeBtn"
                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800
                               focus:outline-none focus:ring-2 focus:ring-blue-500"
                        aria-label="Close drawer"
                    >
                        <flux:icon.x-mark class="size-6" />
                    </button>
                </div>
            </div>

            {{-- Scrollable Content --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-6">
                {{-- Servings Info --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Servings</h3>
                    <p class="text-gray-700 dark:text-gray-300">
                        {{ $this->selectedAssignment->recipe->servings }} servings
                        @if($this->selectedAssignment->serving_multiplier != 1.00)
                            <span class="text-blue-600 dark:text-blue-400">
                                × {{ $this->selectedAssignment->serving_multiplier }}
                                = {{ $this->selectedAssignment->recipe->servings * $this->selectedAssignment->serving_multiplier }}
                            </span>
                        @endif
                    </p>
                </div>

                {{-- Time Info --}}
                @if($this->selectedAssignment->recipe->prep_time || $this->selectedAssignment->recipe->cook_time)
                    <div class="grid grid-cols-2 gap-4">
                        @if($this->selectedAssignment->recipe->prep_time)
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Prep Time</h3>
                                <p class="text-gray-700 dark:text-gray-300">{{ $this->selectedAssignment->recipe->prep_time }} min</p>
                            </div>
                        @endif
                        @if($this->selectedAssignment->recipe->cook_time)
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Cook Time</h3>
                                <p class="text-gray-700 dark:text-gray-300">{{ $this->selectedAssignment->recipe->cook_time }} min</p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Scaled Ingredients --}}
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Ingredients</h3>
                    @if(count($this->scaledIngredients) > 0)
                        <ul class="space-y-2">
                            @foreach($this->scaledIngredients as $ingredient)
                                <li class="flex items-start gap-2">
                                    <span class="text-gray-400 mt-1">•</span>
                                    <span class="flex-1 text-gray-700 dark:text-gray-300">
                                        <span class="font-medium">{{ $ingredient['quantity'] }} {{ $ingredient['unit'] }}</span>
                                        {{ $ingredient['name'] }}
                                        @if($ingredient['notes'])
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                ({{ $ingredient['notes'] }})
                                            </span>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 italic">No ingredients listed</p>
                    @endif
                </div>

                {{-- Instructions --}}
                @if($this->selectedAssignment->recipe->instructions)
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Instructions</h3>
                        <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                            {!! nl2br(e($this->selectedAssignment->recipe->instructions)) !!}
                        </div>
                    </div>
                @endif

                {{-- Notes --}}
                @if($this->selectedAssignment->notes)
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Meal Plan Notes</h3>
                        <p class="text-gray-700 dark:text-gray-300">{{ $this->selectedAssignment->notes }}</p>
                    </div>
                @endif
            </div>

            {{-- Sticky Footer --}}
            <div class="sticky bottom-0 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 p-4">
                <div class="flex gap-3">
                    <flux:button
                        href="{{ route('recipes.show', $this->selectedAssignment->recipe) }}"
                        variant="primary"
                        icon="arrow-top-right-on-square"
                        class="flex-1"
                    >
                        View Full Recipe
                    </flux:button>
                    <flux:button
                        wire:click="closeRecipeDrawer"
                        variant="ghost"
                    >
                        Close
                    </flux:button>
                </div>
            </div>
        @endif
    </div>
</div>
```

---

### Step 5: Code Quality & Testing

**Format Code**:
```bash
vendor/bin/pint
```

**Run All Tests**:
```bash
# Run MealPlans tests
php artisan test --filter=MealPlans

# Run full test suite
php artisan test
```

**Expected Output**: All tests PASS ✅

---

### Step 6: Manual QA Checklist

**Test in Browser**:
```bash
# Ensure dev server is running
composer dev

# Visit https://project-tabletop.ddev.site
```

**Manual Testing**:
- ✅ Add multiple recipes to same meal slot
- ✅ Click recipe card to open drawer
- ✅ Verify drawer slides in from right smoothly
- ✅ Check scaled ingredient quantities are correct
- ✅ Verify quantities format without trailing zeros (e.g., "2" not "2.000")
- ✅ Test "View Full Recipe" button navigates correctly
- ✅ Test drawer closes with backdrop click
- ✅ Test drawer closes with close button
- ✅ Test drawer closes with Escape key
- ✅ Test remove button on recipe cards
- ✅ Verify recipes display in chronological order
- ✅ Test dark mode (toggle in app)
- ✅ Test mobile view (< 640px width)
- ✅ Test keyboard navigation (Tab, Enter, Space, Escape)
- ✅ Test with screen reader (VoiceOver/NVDA)

---

## Troubleshooting

### Tests Failing?

**Issue**: "Method openRecipeDrawer does not exist"
- **Fix**: Ensure methods added to `Show.php`

**Issue**: "Property scaledIngredients does not exist"
- **Fix**: Ensure computed property `getScaledIngredientsProperty()` added

**Issue**: "Duplicate assignments created"
- **Fix**: Verify migration ran (`php artisan migrate:status`)

### Drawer Not Showing?

**Issue**: Drawer doesn't appear when clicking recipe card
- **Fix**: Check browser console for JavaScript errors
- **Fix**: Verify Alpine.js is loaded (part of Livewire 3)
- **Fix**: Ensure `@entangle('showRecipeDrawer')` syntax is correct

**Issue**: Drawer shows but no content
- **Fix**: Check `$this->selectedAssignment` is not null
- **Fix**: Verify eager loading: `->load(['recipe.recipeIngredients.ingredient'])`

### Styling Issues?

**Issue**: Drawer not full height or overlapping
- **Fix**: Ensure `fixed inset-0` classes on backdrop
- **Fix**: Verify `fixed right-0 top-0 bottom-0` on drawer panel

**Issue**: Dark mode not working
- **Fix**: Verify Tailwind dark mode enabled in config
- **Fix**: Ensure all `dark:` classes present on drawer elements

---

## Success Criteria

**Feature is complete when**:
- ✅ All Pest tests pass
- ✅ Multiple recipes can be assigned to same meal slot
- ✅ Drawer opens/closes smoothly within 300ms
- ✅ Ingredient quantities scale correctly with no errors
- ✅ Dark mode works consistently
- ✅ Mobile view (< 640px) is fully functional
- ✅ Keyboard navigation works (Tab, Enter, Escape)
- ✅ Code formatted with Pint (no style violations)

---

## Next Steps

After implementation complete:
1. Run `/speckit.tasks` to generate detailed task breakdown
2. Run `/speckit.analyze` to verify consistency across artifacts
3. Create pull request with specification and tests
4. Manual QA on staging environment
5. Deploy to production

**Estimated Time**: 3-4 hours (per original plan)
