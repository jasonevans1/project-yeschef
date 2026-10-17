<?php

declare(strict_types=1);

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Enums\SharePermission;
use App\Livewire\GroceryLists\Show;
use App\Mail\ShareInvitation;
use App\Models\ContentShare;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\Ingredient;
use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use App\Models\UserItemTemplate;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->list = GroceryList::factory()->standalone()->for($this->user)->create();
    $this->actingAs($this->user);
});

/** @return array<int, string> */
function flashedKeys(): array
{
    return session('_flash.new', []);
}

function showComponent($test, ?GroceryList $list = null)
{
    return Livewire::test(Show::class, ['groceryList' => $list ?? $test->list]);
}

it('has closed and empty default state', function () {
    showComponent($this)
        ->assertSet('showAddItemForm', false)
        ->assertSet('itemName', '')
        ->assertSet('itemNotes', '')
        ->assertSet('searchQuery', '')
        ->assertSet('showRegenerateConfirm', false)
        ->assertSet('showShareDialog', false)
        ->assertSet('showShareModal', false)
        ->assertSet('shareEmail', '')
        ->assertSet('sharePermission', 'read')
        ->assertSet('showDeleteConfirm', false);
});

it('forbids viewing a list the user cannot access', function () {
    $other = GroceryList::factory()->standalone()->create();

    Livewire::test(Show::class, ['groceryList' => $other])->assertForbidden();
});

describe('togglePurchased', function () {
    it('marks an item purchased and flashes a message', function () {
        $item = GroceryItem::factory()->for($this->list)->create(['purchased' => false, 'purchased_at' => null]);

        showComponent($this)->call('togglePurchased', $item->id);

        $item->refresh();
        expect($item->purchased)->toBeTrue()
            ->and($item->purchased_at)->not->toBeNull()
            ->and(flashedKeys())->toContain('message');
    });

    it('unmarks a purchased item', function () {
        $item = GroceryItem::factory()->for($this->list)->create(['purchased' => true, 'purchased_at' => now()]);

        showComponent($this)->call('togglePurchased', $item->id);

        $item->refresh();
        expect($item->purchased)->toBeFalse()
            ->and($item->purchased_at)->toBeNull()
            ->and(flashedKeys())->toContain('message');
    });

    it('refreshes completed counts on the list', function () {
        $item = GroceryItem::factory()->for($this->list)->create(['purchased' => false]);

        $component = showComponent($this)->call('togglePurchased', $item->id);

        expect($component->get('groceryList')->completed_items)->toBe(1);
    });

    it('forbids toggling an item from another list', function () {
        $item = GroceryItem::factory()->create(['purchased' => false]);

        showComponent($this)->call('togglePurchased', $item->id)->assertForbidden();

        expect($item->fresh()->purchased)->toBeFalse();
    });
});

describe('add item form', function () {
    it('opens the form and resets previous input', function () {
        showComponent($this)
            ->set('itemName', 'Stale')
            ->set('itemNotes', 'old notes')
            ->set('searchQuery', 'stale')
            ->call('openAddItemForm')
            ->assertSet('showAddItemForm', true)
            ->assertSet('itemName', '')
            ->assertSet('itemNotes', '')
            ->assertSet('searchQuery', '');
    });

    it('cancels the form and resets state', function () {
        showComponent($this)
            ->call('openAddItemForm')
            ->set('itemName', 'Apple')
            ->set('itemNotes', 'crisp')
            ->set('itemQuantity', '3')
            ->set('itemUnit', 'whole')
            ->set('itemCategory', 'produce')
            ->set('searchQuery', 'app')
            ->call('cancelItemForm')
            ->assertSet('showAddItemForm', false)
            ->assertSet('editingItemId', null)
            ->assertSet('itemName', '')
            ->assertSet('itemNotes', '')
            ->assertSet('itemQuantity', null)
            ->assertSet('itemUnit', null)
            ->assertSet('itemCategory', null)
            ->assertSet('searchQuery', '');
    });

    it('forbids a read-only shared user from opening the form', function () {
        $owner = User::factory()->create();
        $list = GroceryList::factory()->standalone()->for($owner)->create();
        ContentShare::factory()->create([
            'owner_id' => $owner->id,
            'recipient_id' => $this->user->id,
            'recipient_email' => $this->user->email,
            'shareable_type' => GroceryList::class,
            'shareable_id' => $list->id,
            'permission' => SharePermission::Read,
        ]);

        Livewire::test(Show::class, ['groceryList' => $list])
            ->call('openAddItemForm')
            ->assertForbidden();
    });
});

describe('addManualItem', function () {
    it('creates a manual item with all fields and increments sort order', function () {
        GroceryItem::factory()->for($this->list)->create(['sort_order' => 4]);

        showComponent($this)
            ->call('openAddItemForm')
            ->set('itemName', 'Apple')
            ->set('itemQuantity', '3')
            ->set('itemUnit', 'whole')
            ->set('itemCategory', 'produce')
            ->set('itemNotes', 'crisp')
            ->call('addManualItem')
            ->assertHasNoErrors()
            ->assertSet('showAddItemForm', false)
            ->assertSet('itemName', '')
            ->assertSet('itemNotes', '');

        $item = GroceryItem::where('name', 'Apple')->first();
        expect($item->quantity)->toBe(3.0)
            ->and($item->unit)->toBe(MeasurementUnit::WHOLE)
            ->and($item->category)->toBe(IngredientCategory::PRODUCE)
            ->and($item->notes)->toBe('crisp')
            ->and($item->is_manual)->toBeTrue()
            ->and($item->sort_order)->toBe(5)
            ->and(flashedKeys())->toContain('message');
    });

    it('starts sort order at 1 for an empty list and defaults category to other', function () {
        showComponent($this)
            ->set('itemName', 'Thing')
            ->call('addManualItem')
            ->assertHasNoErrors();

        $item = GroceryItem::where('name', 'Thing')->first();
        expect($item->sort_order)->toBe(1)
            ->and($item->category)->toBe(IngredientCategory::OTHER);
    });

    it('prefers an explicit item name over the search query', function () {
        showComponent($this)
            ->set('itemName', 'Explicit')
            ->set('searchQuery', 'Query')
            ->call('addManualItem');

        expect(GroceryItem::where('name', 'Explicit')->exists())->toBeTrue()
            ->and(GroceryItem::where('name', 'Query')->exists())->toBeFalse();
    });

    it('requires a name when search query is empty', function () {
        showComponent($this)
            ->call('addManualItem')
            ->assertHasErrors(['itemName' => 'required']);

        expect(GroceryItem::count())->toBe(0);
    });

    it('does not use a search query when name is given and query empty', function () {
        showComponent($this)
            ->set('itemName', 'Solo')
            ->set('searchQuery', '')
            ->call('addManualItem')
            ->assertHasNoErrors();

        expect(GroceryItem::where('name', 'Solo')->count())->toBe(1);
    });

    it('validates item fields', function (string $field, mixed $value) {
        showComponent($this)
            ->set('itemName', 'Valid')
            ->set($field, $value)
            ->call('addManualItem')
            ->assertHasErrors([$field]);
    })->with([
        'negative quantity' => ['itemQuantity', '-1'],
        'non numeric quantity' => ['itemQuantity', 'abc'],
        'invalid unit' => ['itemUnit', 'bogus'],
        'invalid category' => ['itemCategory', 'bogus'],
        'long notes' => ['itemNotes', str_repeat('a', 501)],
    ]);

    it('rejects an over-long name', function () {
        showComponent($this)
            ->set('itemName', str_repeat('a', 256))
            ->call('addManualItem')
            ->assertHasErrors(['itemName' => 'max']);
    });

    it('accepts every valid unit and category', function () {
        foreach (MeasurementUnit::cases() as $unit) {
            showComponent($this)
                ->set('itemName', 'x')
                ->set('itemUnit', $unit->value)
                ->call('addManualItem')
                ->assertHasNoErrors();
        }
        foreach (IngredientCategory::cases() as $category) {
            showComponent($this)
                ->set('itemName', 'x')
                ->set('itemCategory', $category->value)
                ->call('addManualItem')
                ->assertHasNoErrors();
        }
    });

    it('forbids read-only shared users', function () {
        $owner = User::factory()->create();
        $list = GroceryList::factory()->standalone()->for($owner)->create();
        ContentShare::factory()->create([
            'owner_id' => $owner->id,
            'recipient_id' => $this->user->id,
            'recipient_email' => $this->user->email,
            'shareable_type' => GroceryList::class,
            'shareable_id' => $list->id,
            'permission' => SharePermission::Read,
        ]);

        Livewire::test(Show::class, ['groceryList' => $list])
            ->set('itemName', 'Nope')
            ->call('addManualItem')
            ->assertForbidden();

        expect(GroceryItem::count())->toBe(0);
    });
});

describe('editing', function () {
    it('loads an item into the form', function () {
        $item = GroceryItem::factory()->for($this->list)->create([
            'name' => 'Milk',
            'quantity' => 2,
            'unit' => 'cup',
            'category' => 'dairy',
            'notes' => 'whole',
        ]);

        showComponent($this)
            ->set('showAddItemForm', true)
            ->call('startEditing', $item->id)
            ->assertSet('editingItemId', $item->id)
            ->assertSet('itemName', 'Milk')
            ->assertSet('itemQuantity', '2')
            ->assertSet('itemUnit', 'cup')
            ->assertSet('itemCategory', 'dairy')
            ->assertSet('itemNotes', 'whole')
            ->assertSet('showAddItemForm', false);
    });

    it('loads null quantity, unit and notes as empty values', function () {
        $item = GroceryItem::factory()->for($this->list)->create([
            'quantity' => null,
            'unit' => null,
            'notes' => null,
        ]);

        showComponent($this)
            ->call('startEditing', $item->id)
            ->assertSet('itemQuantity', null)
            ->assertSet('itemUnit', null)
            ->assertSet('itemNotes', '');
    });

    it('forbids editing an item from another list', function () {
        $item = GroceryItem::factory()->create();

        showComponent($this)->call('startEditing', $item->id)->assertForbidden();
    });

    it('saves edits on a manual item without original values', function () {
        $item = GroceryItem::factory()->for($this->list)->create(['source_type' => 'manual']);

        showComponent($this)
            ->call('startEditing', $item->id)
            ->set('itemName', 'Renamed')
            ->set('itemQuantity', '7')
            ->set('itemUnit', 'lb')
            ->set('itemCategory', 'meat')
            ->set('itemNotes', 'fresh')
            ->call('saveEdit')
            ->assertHasNoErrors()
            ->assertSet('editingItemId', null)
            ->assertSet('itemName', '');

        $item->refresh();
        expect($item->name)->toBe('Renamed')
            ->and($item->quantity)->toBe(7.0)
            ->and($item->unit)->toBe(MeasurementUnit::LB)
            ->and($item->category)->toBe(IngredientCategory::MEAT)
            ->and($item->notes)->toBe('fresh')
            ->and($item->original_values)->toBeNull()
            ->and(flashedKeys())->toContain('message');
    });

    it('stores original values the first time a generated item is edited', function () {
        $item = GroceryItem::factory()->for($this->list)->create([
            'source_type' => 'generated',
            'name' => 'Flour',
            'quantity' => 2,
            'unit' => 'cup',
            'category' => 'cooking-and-baking',
            'notes' => 'sifted',
            'original_values' => null,
        ]);

        showComponent($this)
            ->call('startEditing', $item->id)
            ->set('itemName', 'Bread Flour')
            ->call('saveEdit');

        expect($item->fresh()->original_values)->toBe([
            'name' => 'Flour',
            'quantity' => 2,
            'unit' => 'cup',
            'category' => 'cooking-and-baking',
            'notes' => 'sifted',
        ]);
    });

    it('stores null unit in original values when generated item has no unit', function () {
        $item = GroceryItem::factory()->for($this->list)->create([
            'source_type' => 'generated',
            'unit' => null,
            'original_values' => null,
        ]);

        showComponent($this)
            ->call('startEditing', $item->id)
            ->set('itemName', 'Other')
            ->call('saveEdit');

        expect($item->fresh()->original_values['unit'])->toBeNull();
    });

    it('keeps existing original values on subsequent edits', function () {
        $original = ['name' => 'Orig', 'quantity' => 1, 'unit' => 'cup', 'category' => 'dairy', 'notes' => null];
        $item = GroceryItem::factory()->for($this->list)->create([
            'source_type' => 'generated',
            'original_values' => $original,
        ]);

        showComponent($this)
            ->call('startEditing', $item->id)
            ->set('itemName', 'Second edit')
            ->call('saveEdit');

        expect($item->fresh()->original_values)->toBe($original);
    });

    it('defaults category to other when saving with no category', function () {
        $item = GroceryItem::factory()->for($this->list)->create(['category' => 'dairy']);

        showComponent($this)
            ->call('startEditing', $item->id)
            ->set('itemCategory', null)
            ->call('saveEdit');

        expect($item->fresh()->category)->toBe(IngredientCategory::OTHER);
    });

    it('validates on save', function () {
        $item = GroceryItem::factory()->for($this->list)->create(['name' => 'Keep']);

        showComponent($this)
            ->call('startEditing', $item->id)
            ->set('itemName', '')
            ->call('saveEdit')
            ->assertHasErrors(['itemName']);

        expect($item->fresh()->name)->toBe('Keep');
    });

    it('refuses to save an item belonging to another list', function () {
        $foreign = GroceryItem::factory()->create(['name' => 'Foreign']);

        showComponent($this)
            ->set('editingItemId', $foreign->id)
            ->set('itemName', 'Hacked')
            ->call('saveEdit')
            ->assertForbidden();

        expect($foreign->fresh()->name)->toBe('Foreign');
    });
});

describe('deleteItem', function () {
    it('hard deletes manual items', function () {
        $item = GroceryItem::factory()->for($this->list)->create(['source_type' => 'manual']);

        showComponent($this)->call('deleteItem', $item->id);

        expect(GroceryItem::withTrashed()->find($item->id))->toBeNull()
            ->and(flashedKeys())->toContain('message');
    });

    it('soft deletes generated items', function () {
        $item = GroceryItem::factory()->for($this->list)->create(['source_type' => 'generated']);

        showComponent($this)->call('deleteItem', $item->id);

        expect(GroceryItem::find($item->id))->toBeNull()
            ->and(GroceryItem::withTrashed()->find($item->id))->not->toBeNull();
    });

    it('forbids deleting an item from another list', function () {
        $item = GroceryItem::factory()->create();

        showComponent($this)->call('deleteItem', $item->id)->assertForbidden();

        expect(GroceryItem::find($item->id))->not->toBeNull();
    });

    it('forbids read-only shared users', function () {
        $owner = User::factory()->create();
        $list = GroceryList::factory()->standalone()->for($owner)->create();
        $item = GroceryItem::factory()->for($list)->create();
        ContentShare::factory()->create([
            'owner_id' => $owner->id,
            'recipient_id' => $this->user->id,
            'recipient_email' => $this->user->email,
            'shareable_type' => GroceryList::class,
            'shareable_id' => $list->id,
            'permission' => SharePermission::Read,
        ]);

        Livewire::test(Show::class, ['groceryList' => $list])
            ->call('deleteItem', $item->id)
            ->assertForbidden();
    });
});

describe('regeneration', function () {
    beforeEach(function () {
        $this->mealPlan = MealPlan::factory()->for($this->user)->create();
        $this->mpList = GroceryList::factory()->for($this->user)->create([
            'meal_plan_id' => $this->mealPlan->id,
        ]);

        $recipe = Recipe::factory()->create();
        $milk = Ingredient::factory()->create(['name' => 'Milk', 'category' => IngredientCategory::DAIRY]);
        $eggs = Ingredient::factory()->create(['name' => 'Eggs', 'category' => IngredientCategory::DAIRY]);
        foreach ([$milk, $eggs] as $ingredient) {
            RecipeIngredient::create([
                'recipe_id' => $recipe->id,
                'ingredient_id' => $ingredient->id,
                'quantity' => 2.0,
                'unit' => MeasurementUnit::CUP,
            ]);
        }
        MealAssignment::create([
            'meal_plan_id' => $this->mealPlan->id,
            'recipe_id' => $recipe->id,
            'date' => now(),
            'meal_type' => 'dinner',
            'serving_multiplier' => 1.0,
        ]);
    });

    it('rejects confirmation for standalone lists', function () {
        showComponent($this)
            ->call('showRegenerateConfirmation')
            ->assertSet('showRegenerateConfirm', false);

        expect(flashedKeys())->toContain('error');
    });

    it('rejects regeneration for standalone lists', function () {
        showComponent($this)->call('regenerate');

        expect(flashedKeys())->toContain('error')->not->toContain('message');
    });

    it('calculates the diff and prepopulates exclusions', function () {
        $this->mpList->update([
            'excluded_categories' => ['produce'],
            'excluded_ingredients' => ['salt'],
        ]);
        // matches a fresh ingredient (case-insensitive), unmodified generated
        GroceryItem::factory()->for($this->mpList)->create(['name' => 'MILK', 'source_type' => 'generated', 'original_values' => null]);
        // unmodified generated, no longer in meal plan
        GroceryItem::factory()->for($this->mpList)->create(['name' => 'Gone', 'source_type' => 'generated', 'original_values' => null]);
        // edited generated
        GroceryItem::factory()->for($this->mpList)->create(['name' => 'Edited', 'source_type' => 'generated', 'original_values' => ['name' => 'x']]);
        // manual
        GroceryItem::factory()->for($this->mpList)->create(['name' => 'Manual', 'source_type' => 'manual']);
        GroceryItem::factory()->for($this->mpList)->create(['name' => 'Manual2', 'source_type' => 'manual']);

        showComponent($this, $this->mpList)
            ->call('showRegenerateConfirmation')
            ->assertSet('showRegenerateConfirm', true)
            ->assertSet('regenerateExcludedCategories', ['produce'])
            ->assertSet('regenerateExcludedIngredients', ['salt'])
            ->assertSet('regenerateDiff', [
                'added' => 1,
                'updated' => 2,
                'removed' => 1,
                'preserved_manual' => 2,
                'preserved_edited' => 1,
            ]);
    });

    it('defaults exclusions to empty arrays when none stored', function () {
        showComponent($this, $this->mpList)
            ->call('showRegenerateConfirmation')
            ->assertSet('regenerateExcludedCategories', [])
            ->assertSet('regenerateExcludedIngredients', [])
            ->assertSet('regenerateDiff.added', 2);
    });

    it('applies serving multipliers when collecting ingredients', function () {
        MealAssignment::query()->update(['serving_multiplier' => 2.0]);
        $generator = app(App\Services\GroceryListGenerator::class);
        $generator->generate($this->mealPlan);

        showComponent($this, $this->mpList)
            ->call('showRegenerateConfirmation')
            ->assertSet('regenerateDiff.added', 2);
    });

    it('cancels regeneration and clears state', function () {
        showComponent($this, $this->mpList)
            ->call('showRegenerateConfirmation')
            ->set('regenerateExcludedCategories', ['dairy'])
            ->set('regenerateExcludedIngredients', ['milk'])
            ->call('cancelRegenerate')
            ->assertSet('showRegenerateConfirm', false)
            ->assertSet('regenerateDiff', [])
            ->assertSet('regenerateExcludedCategories', [])
            ->assertSet('regenerateExcludedIngredients', []);
    });

    it('regenerates, applying exclusions, and resets state', function () {
        showComponent($this, $this->mpList)
            ->call('showRegenerateConfirmation')
            ->set('regenerateExcludedCategories', ['dairy', 'not-a-category'])
            ->set('regenerateExcludedIngredients', [])
            ->call('regenerate')
            ->assertSet('showRegenerateConfirm', false)
            ->assertSet('regenerateDiff', [])
            ->assertSet('regenerateExcludedCategories', [])
            ->assertSet('regenerateExcludedIngredients', []);

        expect(flashedKeys())->toContain('message');
    });

    it('regenerates generating items when nothing is excluded', function () {
        showComponent($this, $this->mpList)->call('regenerate');

        expect($this->mpList->groceryItems()->pluck('name')->sort()->values()->all())->toBe(['Eggs', 'Milk']);
    });

    it('regenerates excluding specific ingredients', function () {
        showComponent($this, $this->mpList)
            ->set('regenerateExcludedIngredients', ['Milk'])
            ->call('regenerate');

        expect($this->mpList->groceryItems()->pluck('name')->all())->toBe(['Eggs']);
    });

    it('forbids read-only shared users from regenerating', function () {
        $owner = User::factory()->create();
        $list = GroceryList::factory()->for($owner)->create(['meal_plan_id' => $this->mealPlan->id]);
        ContentShare::factory()->create([
            'owner_id' => $owner->id,
            'recipient_id' => $this->user->id,
            'recipient_email' => $this->user->email,
            'shareable_type' => GroceryList::class,
            'shareable_id' => $list->id,
            'permission' => SharePermission::Read,
        ]);

        Livewire::test(Show::class, ['groceryList' => $list])
            ->call('regenerate')
            ->assertForbidden();
        Livewire::test(Show::class, ['groceryList' => $list])
            ->call('showRegenerateConfirmation')
            ->assertForbidden();
    });
});

describe('link sharing', function () {
    it('generates a share link valid for seven days', function () {
        showComponent($this)->call('share');

        $list = $this->list->fresh();
        expect($list->share_token)->not->toBeNull()
            ->and(Str::isUuid($list->share_token))->toBeTrue()
            ->and($list->share_expires_at->isSameDay(now()->addDays(7)))->toBeTrue()
            ->and(flashedKeys())->toContain('message');
    });

    it('opens the share dialog generating a link when none exists', function () {
        showComponent($this)
            ->call('openShareDialog')
            ->assertSet('showShareDialog', true);

        expect($this->list->fresh()->share_token)->not->toBeNull();
    });

    it('keeps an existing token when opening the share dialog', function () {
        $this->list->update(['share_token' => 'existing-token', 'share_expires_at' => now()->addDay()]);

        showComponent($this)
            ->call('openShareDialog')
            ->assertSet('showShareDialog', true);

        expect($this->list->fresh()->share_token)->toBe('existing-token');
    });

    it('closes the share dialog', function () {
        showComponent($this)
            ->call('openShareDialog')
            ->call('closeShareDialog')
            ->assertSet('showShareDialog', false);
    });

    it('revokes sharing', function () {
        $this->list->update(['share_token' => 'tok', 'share_expires_at' => now()->addDay()]);

        showComponent($this)->call('revokeShare');

        $list = $this->list->fresh();
        expect($list->share_token)->toBeNull()
            ->and($list->share_expires_at)->toBeNull()
            ->and(flashedKeys())->toContain('message');
    });

    it('forbids non-writers from sharing links', function () {
        $other = GroceryList::factory()->standalone()->create();

        Livewire::test(Show::class, ['groceryList' => $other])->assertForbidden();
    });
});

describe('user sharing modal', function () {
    it('opens the modal with a clean state', function () {
        showComponent($this)
            ->set('shareEmail', 'old@example.com')
            ->set('sharePermission', 'write')
            ->call('openShareModal')
            ->assertSet('showShareModal', true)
            ->assertSet('shareEmail', '')
            ->assertSet('sharePermission', 'read');
    });

    it('clears validation errors when opening the modal', function () {
        showComponent($this)
            ->set('shareEmail', 'bad')
            ->call('shareWith')
            ->assertHasErrors(['shareEmail'])
            ->call('openShareModal')
            ->assertHasNoErrors();
    });

    it('closes the modal and resets', function () {
        showComponent($this)
            ->call('openShareModal')
            ->set('shareEmail', 'a@example.com')
            ->set('sharePermission', 'write')
            ->call('closeShareModal')
            ->assertSet('showShareModal', false)
            ->assertSet('shareEmail', '')
            ->assertSet('sharePermission', 'read');
    });

    it('clears validation errors when closing the modal', function () {
        showComponent($this)
            ->set('shareEmail', 'bad')
            ->call('shareWith')
            ->assertHasErrors(['shareEmail'])
            ->call('closeShareModal')
            ->assertHasNoErrors();
    });

    it('shares with an existing user and resets the modal', function () {
        Mail::fake();
        $recipient = User::factory()->create();

        showComponent($this)
            ->call('openShareModal')
            ->set('shareEmail', $recipient->email)
            ->set('sharePermission', 'write')
            ->call('shareWith')
            ->assertHasNoErrors()
            ->assertSet('showShareModal', false)
            ->assertSet('shareEmail', '')
            ->assertDispatched('close-modal');

        $share = ContentShare::first();
        expect($share->owner_id)->toBe($this->user->id)
            ->and($share->recipient_id)->toBe($recipient->id)
            ->and($share->recipient_email)->toBe($recipient->email)
            ->and($share->shareable_type)->toBe(GroceryList::class)
            ->and($share->shareable_id)->toBe($this->list->id)
            ->and($share->permission)->toBe(SharePermission::Write)
            ->and($share->share_all)->toBeFalse()
            ->and(flashedKeys())->toContain('success');

        Mail::assertNothingSent();
    });

    it('sends an invitation to an unregistered email only on first share', function () {
        Mail::fake();

        showComponent($this)
            ->set('shareEmail', 'newperson@example.com')
            ->call('shareWith');

        Mail::assertSent(ShareInvitation::class, function (ShareInvitation $mail) {
            return $mail->hasTo('newperson@example.com')
                && $mail->ownerName === $this->user->name
                && $mail->contentDescription === "a grocery list: \"{$this->list->name}\""
                && $mail->registerUrl === route('register');
        });

        showComponent($this)
            ->set('shareEmail', 'newperson@example.com')
            ->set('sharePermission', 'write')
            ->call('shareWith');

        Mail::assertSent(ShareInvitation::class, 1);
        expect(ContentShare::count())->toBe(1)
            ->and(ContentShare::first()->permission)->toBe(SharePermission::Write)
            ->and(ContentShare::first()->recipient_id)->toBeNull();
    });

    it('does not email a registered recipient even when the share is new', function () {
        Mail::fake();
        $recipient = User::factory()->create();

        showComponent($this)->set('shareEmail', $recipient->email)->call('shareWith');

        Mail::assertNothingSent();
    });

    it('prevents sharing with yourself', function () {
        showComponent($this)
            ->set('shareEmail', $this->user->email)
            ->call('shareWith')
            ->assertHasErrors(['shareEmail'])
            ->assertSet('showShareModal', false);

        expect(ContentShare::count())->toBe(0);
    });

    it('validates email and permission', function () {
        showComponent($this)
            ->set('shareEmail', '')
            ->call('shareWith')
            ->assertHasErrors(['shareEmail' => 'required']);

        showComponent($this)
            ->set('shareEmail', 'not-an-email')
            ->call('shareWith')
            ->assertHasErrors(['shareEmail' => 'email']);

        showComponent($this)
            ->set('shareEmail', 'a@example.com')
            ->set('sharePermission', 'admin')
            ->call('shareWith')
            ->assertHasErrors(['sharePermission' => 'in']);

        showComponent($this)
            ->set('shareEmail', str_repeat('a', 250).'@example.com')
            ->call('shareWith')
            ->assertHasErrors(['shareEmail' => 'max']);
    });

    it('forbids non-owners from sharing with users', function () {
        $owner = User::factory()->create();
        $list = GroceryList::factory()->standalone()->for($owner)->create();
        ContentShare::factory()->create([
            'owner_id' => $owner->id,
            'recipient_id' => $this->user->id,
            'recipient_email' => $this->user->email,
            'shareable_type' => GroceryList::class,
            'shareable_id' => $list->id,
            'permission' => SharePermission::Write,
        ]);

        Livewire::test(Show::class, ['groceryList' => $list])
            ->set('shareEmail', 'x@example.com')
            ->call('shareWith')
            ->assertForbidden();
    });
});

describe('delete list', function () {
    it('shows and hides the delete confirmation', function () {
        showComponent($this)
            ->call('confirmDelete')
            ->assertSet('showDeleteConfirm', true)
            ->call('cancelDelete')
            ->assertSet('showDeleteConfirm', false);
    });

    it('deletes the list and redirects', function () {
        showComponent($this)
            ->call('delete')
            ->assertRedirect(route('grocery-lists.index'));

        expect(GroceryList::find($this->list->id))->toBeNull()
            ->and(flashedKeys())->toContain('success');
    });

    it('forbids shared writers from deleting or confirming delete', function () {
        $owner = User::factory()->create();
        $list = GroceryList::factory()->standalone()->for($owner)->create();
        ContentShare::factory()->create([
            'owner_id' => $owner->id,
            'recipient_id' => $this->user->id,
            'recipient_email' => $this->user->email,
            'shareable_type' => GroceryList::class,
            'shareable_id' => $list->id,
            'permission' => SharePermission::Write,
        ]);

        Livewire::test(Show::class, ['groceryList' => $list])->call('confirmDelete')->assertForbidden();
        Livewire::test(Show::class, ['groceryList' => $list])->call('delete')->assertForbidden();

        expect(GroceryList::find($list->id))->not->toBeNull();
    });
});

describe('autocomplete', function () {
    it('clears suggestions for blank queries', function () {
        showComponent($this)
            ->set('suggestions', [['name' => 'x']])
            ->set('searchQuery', '   ')
            ->assertSet('suggestions', []);
    });

    it('populates formatted suggestions for the current user', function () {
        UserItemTemplate::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'banana',
            'category' => IngredientCategory::PRODUCE,
            'unit' => MeasurementUnit::WHOLE,
            'default_quantity' => 6,
        ]);
        UserItemTemplate::factory()->create([
            'user_id' => User::factory()->create()->id,
            'name' => 'banjo',
        ]);

        $component = showComponent($this)->set('searchQuery', 'ban');

        $suggestions = $component->get('suggestions');
        expect($suggestions)->toHaveCount(1)
            ->and($suggestions[0]['name'])->toBe('banana')
            ->and($suggestions[0]['category'])->toBe('produce')
            ->and($suggestions[0]['unit'])->toBe('whole')
            ->and($suggestions[0]['is_user_template'])->toBeTrue();
    });

    it('limits suggestions to ten', function () {
        UserItemTemplate::factory()->count(12)->sequence(
            fn ($s) => ['name' => 'item '.$s->index]
        )->create(['user_id' => $this->user->id]);

        $component = showComponent($this)->set('searchQuery', 'item');

        expect($component->get('suggestions'))->toHaveCount(10);
    });

    it('selects a suggestion with a default quantity', function () {
        showComponent($this)
            ->call('selectGroceryItem', [
                'name' => 'Rice',
                'category' => 'grains-and-pasta',
                'unit' => 'cup',
                'default_quantity' => 2,
            ])
            ->assertSet('itemName', 'Rice')
            ->assertSet('itemCategory', 'grains-and-pasta')
            ->assertSet('itemUnit', 'cup')
            ->assertSet('itemQuantity', '2')
            ->assertSet('searchQuery', 'Rice');
    });

    it('selects a suggestion without a default quantity', function () {
        showComponent($this)
            ->call('selectGroceryItem', [
                'name' => 'Salt',
                'category' => 'cooking-and-baking',
                'unit' => null,
                'default_quantity' => null,
            ])
            ->assertSet('itemQuantity', null);
    });
});

describe('rendering', function () {
    it('groups items by category in sort order', function () {
        GroceryItem::factory()->for($this->list)->create(['name' => 'Zeta', 'category' => 'dairy', 'sort_order' => 2]);
        GroceryItem::factory()->for($this->list)->create(['name' => 'Alpha', 'category' => 'dairy', 'sort_order' => 2]);
        GroceryItem::factory()->for($this->list)->create(['name' => 'First', 'category' => 'dairy', 'sort_order' => 1]);
        GroceryItem::factory()->for($this->list)->create(['name' => 'Bun', 'category' => 'bakery', 'sort_order' => 0]);

        $grouped = showComponent($this)->instance()->getItemsByCategoryProperty();

        expect($grouped->keys()->all())->toBe(['bakery', 'dairy'])
            ->and($grouped['dairy']->pluck('name')->all())->toBe(['First', 'Alpha', 'Zeta']);
    });

    it('only exposes viewable recipes linked from items', function () {
        $mine = Recipe::factory()->create(['user_id' => $this->user->id]);
        $foreign = Recipe::factory()->create(['user_id' => User::factory()->create()->id]);
        GroceryItem::factory()->for($this->list)->create(['recipe_ids' => [$mine->id, $foreign->id]]);
        GroceryItem::factory()->for($this->list)->create(['recipe_ids' => null]);

        $component = showComponent($this);
        $recipes = $component->viewData('recipesById');

        expect($recipes->keys()->all())->toBe([$mine->id])
            ->and($component->viewData('categories'))->toBe(IngredientCategory::cases())
            ->and($component->viewData('units'))->toBe(MeasurementUnit::cases());
    });

    it('renders with no recipes linked', function () {
        GroceryItem::factory()->for($this->list)->create(['recipe_ids' => null]);

        expect(showComponent($this)->viewData('recipesById'))->toHaveCount(0);
    });
});
