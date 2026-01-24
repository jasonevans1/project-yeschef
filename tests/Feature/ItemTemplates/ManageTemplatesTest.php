<?php

declare(strict_types=1);

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Livewire\Settings\ItemTemplates;
use App\Livewire\Settings\ItemTemplatesEdit;
use App\Models\User;
use App\Models\UserItemTemplate;
use Livewire\Livewire;

// T051: Test viewing all user templates
test('user can view all their item templates', function () {
    $user = User::factory()->create();

    // Create some user templates
    UserItemTemplate::factory()->for($user)->create([
        'name' => 'banana',
        'category' => IngredientCategory::PRODUCE,
        'unit' => MeasurementUnit::WHOLE,
        'usage_count' => 5,
    ]);

    UserItemTemplate::factory()->for($user)->create([
        'name' => 'almond milk',
        'category' => IngredientCategory::BEVERAGES,
        'unit' => MeasurementUnit::GALLON,
        'usage_count' => 3,
    ]);

    UserItemTemplate::factory()->for($user)->create([
        'name' => 'sourdough bread',
        'category' => IngredientCategory::BAKERY,
        'unit' => MeasurementUnit::WHOLE,
        'usage_count' => 2,
    ]);

    Livewire::actingAs($user)
        ->test(ItemTemplates::class)
        ->assertSee('banana')
        ->assertSee('almond milk')
        ->assertSee('sourdough bread')
        ->assertSee('Produce')
        ->assertSee('Beverages')
        ->assertSee('Bakery');
});

// T052: Test editing template category
test('user can edit template category', function () {
    $user = User::factory()->create();

    $template = UserItemTemplate::factory()->for($user)->create([
        'name' => 'milk',
        'category' => IngredientCategory::DAIRY,
        'unit' => MeasurementUnit::GALLON,
    ]);

    Livewire::actingAs($user)
        ->test(ItemTemplatesEdit::class, ['template' => $template->id])
        ->set('name', 'milk')
        ->set('category', IngredientCategory::BEVERAGES->value)
        ->set('unit', MeasurementUnit::GALLON->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('settings.item-templates'));

    $template->refresh();

    expect($template->category)->toBe(IngredientCategory::BEVERAGES)
        ->and($template->name)->toBe('milk')
        ->and($template->unit)->toBe(MeasurementUnit::GALLON);
});

// T053: Test editing template updates autocomplete suggestions
test('editing template updates autocomplete suggestions', function () {
    $user = User::factory()->create();

    $template = UserItemTemplate::factory()->for($user)->create([
        'name' => 'milk',
        'category' => IngredientCategory::DAIRY,
        'unit' => MeasurementUnit::GALLON,
    ]);

    // Edit the template
    Livewire::actingAs($user)
        ->test(ItemTemplatesEdit::class, ['template' => $template->id])
        ->set('name', 'milk')
        ->set('category', IngredientCategory::BEVERAGES->value)
        ->set('unit', MeasurementUnit::GALLON->value)
        ->call('save');

    $template->refresh();

    // Verify autocomplete service returns the updated template
    $service = app(\App\Services\ItemAutoCompleteService::class);
    $results = $service->query($user->id, 'mil');
    $suggestions = $results->map(fn ($t) => $service->formatSuggestion($t));

    $milkSuggestion = $suggestions->firstWhere('name', 'milk');

    expect($milkSuggestion)->not->toBeNull()
        ->and($milkSuggestion['category'])->toBe(IngredientCategory::BEVERAGES->value);
});

// T054: Test manually creating template
test('user can manually create a new template', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ItemTemplatesEdit::class)
        ->set('name', 'organic honey')
        ->set('category', IngredientCategory::PANTRY->value)
        ->set('unit', MeasurementUnit::JAR->value)
        ->set('default_quantity', 1)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('settings.item-templates'));

    $template = UserItemTemplate::where('user_id', $user->id)
        ->where('name', 'organic honey')
        ->first();

    expect($template)->not->toBeNull()
        ->and($template->category)->toBe(IngredientCategory::PANTRY)
        ->and($template->unit)->toBe(MeasurementUnit::JAR)
        ->and($template->default_quantity)->toBe('1.000')
        ->and($template->usage_count)->toBe(0); // Manually created, not used yet
});

// T055: Test deleting template falls back to common defaults
test('deleting user template falls back to common defaults in autocomplete', function () {
    $user = User::factory()->create();

    // Create a common default for milk
    \App\Models\CommonItemTemplate::create([
        'name' => 'milk',
        'category' => IngredientCategory::DAIRY,
        'unit' => MeasurementUnit::GALLON,
        'default_quantity' => 1,
        'usage_count' => 100,
    ]);

    // Create a user template that overrides a common default
    $template = UserItemTemplate::factory()->for($user)->create([
        'name' => 'milk',
        'category' => IngredientCategory::BEVERAGES, // User prefers this
        'unit' => MeasurementUnit::GALLON,
    ]);

    // Verify user template appears in autocomplete
    $service = app(\App\Services\ItemAutoCompleteService::class);
    $results = $service->query($user->id, 'mil');
    $suggestions = $results->map(fn ($t) => $service->formatSuggestion($t));

    $milkSuggestion = $suggestions->firstWhere('name', 'milk');
    expect($milkSuggestion)->not->toBeNull()
        ->and($milkSuggestion['category'])->toBe(IngredientCategory::BEVERAGES->value);

    // Delete the user template
    Livewire::actingAs($user)
        ->test(ItemTemplates::class)
        ->call('delete', $template->id)
        ->assertHasNoErrors();

    // Verify template is deleted
    expect(UserItemTemplate::find($template->id))->toBeNull();

    // Autocomplete should now fall back to common default (dairy)
    $results = $service->query($user->id, 'mil');
    $suggestions = $results->map(fn ($t) => $service->formatSuggestion($t));

    $milkSuggestion = $suggestions->firstWhere('name', 'milk');

    // Should still find milk, but from common defaults (dairy category)
    expect($milkSuggestion)->not->toBeNull()
        ->and($milkSuggestion['category'])->toBe(IngredientCategory::DAIRY->value);
});

// T056: Test authorization (cannot view/edit other users' templates)
test('user cannot view other users templates', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $template1 = UserItemTemplate::factory()->for($user1)->create(['name' => 'user1 item']);
    $template2 = UserItemTemplate::factory()->for($user2)->create(['name' => 'user2 item']);

    // User1 should not see User2's templates
    Livewire::actingAs($user1)
        ->test(ItemTemplates::class)
        ->assertSee('user1 item')
        ->assertDontSee('user2 item');

    // User2 should not see User1's templates
    Livewire::actingAs($user2)
        ->test(ItemTemplates::class)
        ->assertSee('user2 item')
        ->assertDontSee('user1 item');
});

test('user cannot edit other users templates', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $template = UserItemTemplate::factory()->for($user2)->create([
        'name' => 'user2 template',
        'category' => IngredientCategory::DAIRY,
    ]);

    // User1 tries to edit User2's template
    Livewire::actingAs($user1)
        ->test(ItemTemplatesEdit::class, ['template' => $template->id])
        ->assertForbidden();
});

test('user cannot delete other users templates', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $template = UserItemTemplate::factory()->for($user2)->create([
        'name' => 'user2 template',
    ]);

    // User1 tries to delete User2's template
    Livewire::actingAs($user1)
        ->test(ItemTemplates::class)
        ->call('delete', $template->id)
        ->assertForbidden();

    // Verify template still exists
    expect(UserItemTemplate::find($template->id))->not->toBeNull();
});
