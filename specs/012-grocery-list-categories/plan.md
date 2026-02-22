# Implementation Plan: Grocery List Category Filtering and Auto-Categorization

**Branch**: `012-grocery-list-categories` | **Date**: 2026-02-22 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/012-grocery-list-categories/spec.md`

## Summary

Enhance the grocery list generation flow to (1) automatically improve ingredient categorization by checking `UserItemTemplate` then `CommonItemTemplate` when an ingredient's category is "Other," and (2) allow users to optionally exclude entire ingredient categories when generating or regenerating a grocery list from a meal plan. Excluded categories are surfaced as a dismissible notice on the list view. User preferences for default exclusions are stored on the users table and managed from the generation page.

## Technical Context

**Language/Version**: PHP 8.3, Laravel 12
**Primary Dependencies**: Livewire 3, Livewire Flux 2 (UI components), Pest 4 (tests)
**Storage**: SQLite (test), MariaDB (DDEV) — two new nullable JSON columns
**Testing**: Pest (feature + unit), Playwright (E2E for critical generation flow)
**Target Platform**: Web application (DDEV / nginx-fpm)
**Project Type**: Web application (Laravel + Livewire full-page components)
**Performance Goals**: Generation flow completes in standard web request time; category count preview computed at mount (no background jobs needed at this scale)
**Constraints**: Must not break any existing grocery list generation, regeneration, or test coverage
**Scale/Scope**: Personal-use app; single user per request; no concurrency concerns for preference updates

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|---|---|---|
| I. Livewire-First Architecture | ✅ Pass | All UI changes in `Generate` and `Show` Livewire components; no new controllers |
| II. Component-Driven Development | ✅ Pass | Using existing `flux:checkbox`, `flux:callout`, `flux:modal` Flux components; no custom UI |
| III. Test-First Development | ✅ Pass | New unit + feature tests for all new generator methods and component actions |
| IV. Full-Stack Integration Testing | ✅ Pass | Feature tests for complete generation flow with exclusions; E2E for critical path |
| V. Developer Experience | ✅ Pass | Standard Artisan migration commands; Pint run after all edits |

**Post-design re-check**: No violations introduced. Two JSON columns added to existing tables — no new tables required. `GroceryListGenerator` changes are backwards-compatible (default `[]` parameter). Existing `showRegenerateConfirmation` dead code in `Show.php` is repurposed rather than duplicated.

## Project Structure

### Documentation (this feature)

```text
specs/012-grocery-list-categories/
├── plan.md              # This file
├── spec.md              # Feature specification
├── research.md          # Phase 0 research decisions
├── data-model.md        # Phase 1 data model
├── quickstart.md        # Phase 1 quickstart guide
├── contracts/
│   └── livewire-contracts.md   # Phase 1 Livewire component contracts
└── tasks.md             # Phase 2 output (/speckit.tasks command)
```

### Source Code (repository root)

```text
app/
├── Enums/
│   └── IngredientCategory.php              (no changes — existing label() method sufficient)
├── Livewire/
│   └── GroceryLists/
│       ├── Generate.php                    (add exclusion properties, savePreferences, clearPreferences; update generate())
│       └── Show.php                        (activate showRegenerateConfirmation modal; add regenerateExcludedCategories property)
├── Models/
│   ├── GroceryList.php                     (add excluded_categories to fillable + cast)
│   └── User.php                            (add grocery_category_exclusions to fillable + cast)
└── Services/
    └── GroceryListGenerator.php            (add resolveIngredientCategory, getCategoryItemCounts; update generate, regenerate, collectIngredientsFromMealPlan)

database/migrations/
├── xxxx_add_excluded_categories_to_grocery_lists_table.php   (new)
└── xxxx_add_grocery_category_exclusions_to_users_table.php   (new)

resources/views/livewire/grocery-lists/
├── generate.blade.php                      (add category exclusion checkboxes + save/clear preference buttons)
└── show.blade.php                          (add excluded-categories callout; replace wire:confirm with Flux modal for regeneration)

tests/
├── Feature/GroceryLists/
│   ├── GenerateGroceryListTest.php         (extend: add exclusion + preference tests to existing file, or keep separate)
│   ├── CategoryExclusionTest.php           (new: Generate component with exclusions, preference save/clear, Show exclusion notice)
│   └── CategoryLookupTest.php             (new: template-based categorization — UserItemTemplate and CommonItemTemplate fallbacks)
└── Unit/
    └── GroceryListGeneratorTest.php        (extend: resolveIngredientCategory, getCategoryItemCounts, generate/regenerate with exclusions)
```

**Structure Decision**: Single web application. All changes are within the existing `app/`, `database/`, `resources/`, and `tests/` directories. No new top-level directories are created.

## Implementation Phases

---

### Phase 1: Database & Models

**Goal**: Add the two new JSON columns and update models to cast them correctly.

#### 1.1 Migration — `excluded_categories` on `grocery_lists`

```bash
php artisan make:migration add_excluded_categories_to_grocery_lists_table --no-interaction
```

Column: `$table->json('excluded_categories')->nullable()->after('regenerated_at');`

#### 1.2 Migration — `grocery_category_exclusions` on `users`

```bash
php artisan make:migration add_grocery_category_exclusions_to_users_table --no-interaction
```

Column: `$table->json('grocery_category_exclusions')->nullable()->after('two_factor_confirmed_at');`

#### 1.3 Update `GroceryList` Model

Add to `$fillable`: `'excluded_categories'`

Add to `casts()`:
```php
'excluded_categories' => 'array',
```

#### 1.4 Update `User` Model

Add to `$fillable`: `'grocery_category_exclusions'`

Add to `casts()`:
```php
'grocery_category_exclusions' => 'array',
```

**Tests after Phase 1**:
- Verify migrations run on SQLite (test environment): `php artisan migrate`
- Model cast test: create a `GroceryList` with `excluded_categories` array → reload → assert it's an array

---

### Phase 2: Service Layer — `GroceryListGenerator`

**Goal**: Add template-based category resolution and exclusion filtering to the generator.

#### 2.1 Add `resolveIngredientCategory()` (private)

```php
private function resolveIngredientCategory(
    string $name,
    IngredientCategory $currentCategory,
    int $userId
): IngredientCategory
```

Logic:
1. If `$currentCategory !== IngredientCategory::OTHER` → return `$currentCategory`.
2. Query `UserItemTemplate::where('user_id', $userId)->whereRaw('LOWER(name) = ?', [strtolower($name)])->first()`.
3. If found → return `$template->category`.
4. Query `CommonItemTemplate::whereRaw('LOWER(name) = ?', [strtolower($name)])->first()`.
5. If found → return `$template->category`.
6. Return `IngredientCategory::OTHER`.

#### 2.2 Update `collectIngredientsFromMealPlan()` (private → accepts `$userId`)

```php
private function collectIngredientsFromMealPlan(MealPlan $mealPlan, int $userId): Collection
```

After scaling each ingredient, apply `resolveIngredientCategory($item['name'], $item['category'], $userId)` and update the `category` key in the array.

#### 2.3 Add `getCategoryItemCounts()` (public)

```php
public function getCategoryItemCounts(MealPlan $mealPlan, int $userId): array
```

1. Call `collectIngredientsFromMealPlan($mealPlan, $userId)` → get flat ingredients collection.
2. Call `aggregateIngredients()` → deduplicated collection.
3. Group by `category->value`, count each group.
4. Return `['produce' => 4, 'pantry' => 6, ...]`.

#### 2.4 Update `generate()` Signature and Logic

```php
public function generate(MealPlan $mealPlan, array $excludedCategories = []): GroceryList
```

1. Call `collectIngredientsFromMealPlan($mealPlan, $mealPlan->user_id)` (pass user_id).
2. Aggregate ingredients.
3. Organize by category.
4. When creating `GroceryItem` records, skip items whose category is in `$excludedCategories`.
5. Set `excluded_categories` on the `GroceryList`:
   ```php
   $groceryList->update([
       'excluded_categories' => !empty($excludedCategories)
           ? array_map(fn($c) => $c->value, $excludedCategories)
           : null,
   ]);
   ```

#### 2.5 Update `regenerate()` Signature and Logic

```php
public function regenerate(GroceryList $groceryList, array $excludedCategories = []): GroceryList
```

Same as `generate()` — apply `resolveIngredientCategory()` via updated `collectIngredientsFromMealPlan()`, skip items in `$excludedCategories` when creating new `GroceryItem` records, and update `groceryList->excluded_categories`.

**Tests after Phase 2** (extend `GroceryListGeneratorTest.php`):
- `resolveIngredientCategory returns original category when not OTHER`
- `resolveIngredientCategory returns user template category for OTHER ingredient`
- `resolveIngredientCategory falls back to common template when no user template exists`
- `resolveIngredientCategory returns OTHER when no templates match`
- `getCategoryItemCounts returns correct counts per category`
- `generate excludes items in excluded categories`
- `generate stores excluded categories on grocery list`
- `generate with empty excluded categories stores null on grocery list`
- `regenerate excludes items in excluded categories`
- `regenerate preserves manual items even when their category is excluded`

---

### Phase 3: `Generate` Livewire Component

**Goal**: Add exclusion UI, preference save/clear, and pass exclusions to the generator.

#### 3.1 New Properties

```php
public array $excludedCategories = [];
public array $categoryItemCounts = [];
public array $savedPreferences = [];
```

#### 3.2 Update `mount()`

After existing logic:
```php
$this->savedPreferences = auth()->user()->grocery_category_exclusions ?? [];
$this->excludedCategories = $this->existingList?->excluded_categories
    ?? $this->savedPreferences;
if ($this->recipeCount > 0) {
    $this->categoryItemCounts = app(GroceryListGenerator::class)
        ->getCategoryItemCounts($mealPlan, auth()->id());
}
```

#### 3.3 New `savePreferences()` Action

```php
public function savePreferences(): void
{
    $this->validate(['excludedCategories.*' => 'in:' . implode(',', array_column(IngredientCategory::cases(), 'value'))]);
    auth()->user()->update(['grocery_category_exclusions' => $this->excludedCategories ?: null]);
    $this->savedPreferences = $this->excludedCategories;
    $this->dispatch('preferences-saved');
}
```

#### 3.4 New `clearPreferences()` Action

```php
public function clearPreferences(): void
{
    auth()->user()->update(['grocery_category_exclusions' => null]);
    $this->savedPreferences = [];
}
```

#### 3.5 Update `generate()` Action

Convert `$this->excludedCategories` (string values) to `IngredientCategory` enum cases before passing to generator:

```php
$excludedEnums = array_map(
    fn($v) => IngredientCategory::from($v),
    $this->excludedCategories
);
// Pass $excludedEnums to $generator->generate() / $generator->regenerate()
```

#### 3.6 Update `generate.blade.php`

Add a collapsible section between the meal plan details and action buttons:

```blade
{{-- Category Exclusion Panel --}}
@if ($recipeCount > 0 && count($categoryItemCounts) > 0)
<div class="...">
    <flux:heading size="sm">Exclude categories (optional)</flux:heading>
    <flux:text size="sm" class="...">Select categories to skip from this grocery list.</flux:text>
    <div class="grid grid-cols-2 gap-2 mt-3">
        @foreach (App\Enums\IngredientCategory::cases() as $category)
            @if (isset($categoryItemCounts[$category->value]) && $categoryItemCounts[$category->value] > 0)
            <flux:checkbox
                wire:model="excludedCategories"
                value="{{ $category->value }}"
                label="{{ $category->label() }} ({{ $categoryItemCounts[$category->value] }})"
            />
            @endif
        @endforeach
    </div>
    <div class="flex gap-3 mt-3">
        <flux:button size="sm" variant="ghost" wire:click="savePreferences">
            Save as default
        </flux:button>
        @if (!empty($savedPreferences))
        <flux:button size="sm" variant="ghost" wire:click="clearPreferences">
            Clear saved defaults
        </flux:button>
        @endif
    </div>
</div>
@endif
```

**Tests after Phase 3** (`CategoryExclusionTest.php`, Livewire feature tests):
- Generate component mounts with saved preferences pre-selected
- Generate component mounts with existing list's excluded categories pre-selected
- `savePreferences` stores selection on user model
- `clearPreferences` clears stored selection
- `savePreferences` validates categories are valid enum values
- Generate with exclusions passes correct enum array to generator (use generator mock or assert DB)
- Category counts are shown for categories present in the meal plan
- Categories with 0 items are not shown as checkboxes

---

### Phase 4: `Show` Livewire Component and View

**Goal**: Activate the existing dead `showRegenerateConfirmation` infrastructure as a Flux modal with exclusion checkboxes, and add the excluded categories notice.

#### 4.1 New Property

```php
public array $regenerateExcludedCategories = [];
```

#### 4.2 Update `showRegenerateConfirmation()`

After existing diff calculation:
```php
$this->regenerateExcludedCategories = $this->groceryList->excluded_categories ?? [];
$this->showRegenerateConfirm = true;
```

#### 4.3 Update `regenerate()`

```php
$excludedEnums = array_map(
    fn($v) => IngredientCategory::from($v),
    $this->regenerateExcludedCategories
);
app(GroceryListGenerator::class)->regenerate($this->groceryList, $excludedEnums);
```

#### 4.4 Update `show.blade.php`

**A. Replace `wire:confirm` on regenerate button with `wire:click="showRegenerateConfirmation"`.**

**B. Add Flux modal for regeneration** (wired to `$showRegenerateConfirm`):
```blade
<flux:modal wire:model="showRegenerateConfirm" name="regenerate-confirm">
    <flux:heading>Regenerate grocery list</flux:heading>
    {{-- diff stats (existing regenerateDiff data) --}}
    {{-- exclusion checkboxes --}}
    <div class="grid grid-cols-2 gap-2 mt-3">
        @foreach (App\Enums\IngredientCategory::cases() as $category)
        <flux:checkbox
            wire:model="regenerateExcludedCategories"
            value="{{ $category->value }}"
            label="{{ $category->label() }}"
        />
        @endforeach
    </div>
    <div class="flex gap-3 mt-4">
        <flux:button wire:click="cancelRegenerate" variant="ghost">Cancel</flux:button>
        <flux:button wire:click="regenerate" variant="primary">Regenerate</flux:button>
    </div>
</flux:modal>
```

**C. Add excluded categories notice** (above category sections):
```blade
@if (!empty($groceryList->excluded_categories))
<flux:callout variant="warning" icon="information-circle" class="mb-4">
    <flux:callout.heading>Some categories were excluded</flux:callout.heading>
    <flux:callout.text>
        {{ collect($groceryList->excluded_categories)->map(fn($v) => \App\Enums\IngredientCategory::from($v)->label())->join(', ') }}
        items were not added to this list.
        <flux:button size="sm" variant="ghost" wire:click="showRegenerateConfirmation">
            Regenerate with different categories
        </flux:button>
    </flux:callout.text>
</flux:callout>
@endif
```

**Tests after Phase 4** (`CategoryExclusionTest.php`):
- Show page displays exclusion notice when `excluded_categories` is set on the list
- Show page does not display exclusion notice when `excluded_categories` is null
- Notice names the excluded categories
- Regenerate modal is shown when `showRegenerateConfirmation` is called
- Regenerate modal pre-populates with list's existing excluded categories
- `regenerate()` passes selected exclusion enums to generator
- After regeneration, `excluded_categories` updated on the grocery list

---

### Phase 5: Run Pint and Full Test Suite

```bash
vendor/bin/pint --dirty
composer test
```

All existing tests must pass. New tests must pass.

---

## Test File Summary

| File | Type | Focus |
|---|---|---|
| `tests/Unit/GroceryListGeneratorTest.php` | Unit (extend) | `resolveIngredientCategory`, `getCategoryItemCounts`, `generate`/`regenerate` with exclusions |
| `tests/Feature/GroceryLists/GenerateGroceryListTest.php` | Feature (extend) | Existing service tests — verify no regression |
| `tests/Feature/GroceryLists/CategoryExclusionTest.php` | Feature (new) | Generate component exclusion UI, preferences, Show notice, regenerate modal |
| `tests/Feature/GroceryLists/CategoryLookupTest.php` | Feature (new) | Template-based categorization (UserItemTemplate → CommonItemTemplate fallback) |
| `tests/Feature/GroceryLists/RegenerateWithManualChangesTest.php` | Feature (extend) | Add: manual items not removed when excluded categories match their category |

## Complexity Tracking

> No constitutional violations. No complexity justification required.
