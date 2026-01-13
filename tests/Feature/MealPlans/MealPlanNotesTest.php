<?php

use App\Enums\MealType;
use App\Livewire\MealPlans\Show;
use App\Models\MealPlan;
use App\Models\MealPlanNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// T008: User can add note to meal plan with title and details
it('allows user to add note to meal plan with title and details', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['mealPlan' => $mealPlan])
        ->call('openNoteForm', '2025-10-15', 'dinner')
        ->set('noteTitle', 'Eating out at Mom\'s house')
        ->set('noteDetails', 'Birthday dinner celebration')
        ->call('saveNote')
        ->assertHasNoErrors();

    expect(MealPlanNote::count())->toBe(1);

    $note = MealPlanNote::first();
    expect($note->meal_plan_id)->toBe($mealPlan->id);
    expect($note->date->format('Y-m-d'))->toBe('2025-10-15');
    expect($note->meal_type)->toBe(MealType::DINNER);
    expect($note->title)->toBe('Eating out at Mom\'s house');
    expect($note->details)->toBe('Birthday dinner celebration');
});

// T009: Title validation - required and max 255 characters
it('requires title when adding a note', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['mealPlan' => $mealPlan])
        ->call('openNoteForm', '2025-10-15', 'dinner')
        ->set('noteTitle', '')
        ->call('saveNote')
        ->assertHasErrors(['noteTitle' => 'required']);

    expect(MealPlanNote::count())->toBe(0);
});

it('enforces max 255 character limit on title', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $longTitle = str_repeat('a', 256);

    Livewire::actingAs($user)
        ->test(Show::class, ['mealPlan' => $mealPlan])
        ->call('openNoteForm', '2025-10-15', 'dinner')
        ->set('noteTitle', $longTitle)
        ->call('saveNote')
        ->assertHasErrors(['noteTitle' => 'max']);

    expect(MealPlanNote::count())->toBe(0);
});

// T010: Details validation - nullable and max 2000 characters
it('allows note without details', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['mealPlan' => $mealPlan])
        ->call('openNoteForm', '2025-10-15', 'lunch')
        ->set('noteTitle', 'Fasting day')
        ->set('noteDetails', '')
        ->call('saveNote')
        ->assertHasNoErrors();

    expect(MealPlanNote::count())->toBe(1);

    $note = MealPlanNote::first();
    expect($note->title)->toBe('Fasting day');
    expect($note->details)->toBeNull();
});

it('enforces max 2000 character limit on details', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $longDetails = str_repeat('a', 2001);

    Livewire::actingAs($user)
        ->test(Show::class, ['mealPlan' => $mealPlan])
        ->call('openNoteForm', '2025-10-15', 'dinner')
        ->set('noteTitle', 'Test Note')
        ->set('noteDetails', $longDetails)
        ->call('saveNote')
        ->assertHasErrors(['noteDetails' => 'max']);

    expect(MealPlanNote::count())->toBe(0);
});

// T011: Notes are displayed in meal plan grouped by date and meal type
it('displays notes grouped by date and meal type in meal plan view', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    // Create notes for different dates and meal types
    MealPlanNote::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::BREAKFAST,
        'title' => 'Breakfast note',
    ]);

    MealPlanNote::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
        'title' => 'Dinner note',
    ]);

    MealPlanNote::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'date' => '2025-10-16',
        'meal_type' => MealType::LUNCH,
        'title' => 'Lunch note',
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['mealPlan' => $mealPlan])
        ->assertSee('Breakfast note')
        ->assertSee('Dinner note')
        ->assertSee('Lunch note');
});

// T012: User cannot add note to another user's meal plan (authorization)
it('prevents user from adding note to another users meal plan', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($owner)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    Livewire::actingAs($otherUser)
        ->test(Show::class, ['mealPlan' => $mealPlan])
        ->assertForbidden();
});

// T022: User can edit existing note title and details
it('allows user to edit existing note title and details', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $note = MealPlanNote::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
        'title' => 'Original Title',
        'details' => 'Original details',
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['mealPlan' => $mealPlan])
        ->call('editNote', $note)
        ->assertSet('noteTitle', 'Original Title')
        ->assertSet('noteDetails', 'Original details')
        ->assertSet('editingNoteId', $note->id)
        ->set('noteTitle', 'Updated Title')
        ->set('noteDetails', 'Updated details')
        ->call('saveNote')
        ->assertHasNoErrors();

    $note->refresh();
    expect($note->title)->toBe('Updated Title');
    expect($note->details)->toBe('Updated details');
});

// T023: Edit validation prevents empty title
it('prevents editing note with empty title', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $note = MealPlanNote::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
        'title' => 'Original Title',
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['mealPlan' => $mealPlan])
        ->call('editNote', $note)
        ->set('noteTitle', '')
        ->call('saveNote')
        ->assertHasErrors(['noteTitle' => 'required']);

    $note->refresh();
    expect($note->title)->toBe('Original Title');
});

// T033: User can delete note from meal plan
it('allows user to delete note from meal plan', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $note = MealPlanNote::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
        'title' => 'Note to delete',
    ]);

    expect(MealPlanNote::count())->toBe(1);

    Livewire::actingAs($user)
        ->test(Show::class, ['mealPlan' => $mealPlan])
        ->call('deleteNote', $note)
        ->assertHasNoErrors();

    expect(MealPlanNote::count())->toBe(0);
});

// T034: Deleted note no longer appears in calendar
it('no longer displays deleted note in calendar', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $note = MealPlanNote::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
        'title' => 'Visible note',
    ]);

    // Note is visible initially
    Livewire::actingAs($user)
        ->test(Show::class, ['mealPlan' => $mealPlan])
        ->assertSee('Visible note')
        ->call('deleteNote', $note)
        ->assertDontSee('Visible note');
});

// T039: Grocery list generation excludes notes entirely
it('excludes notes from grocery list generation', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    // Create a recipe with ingredients
    $recipe = \App\Models\Recipe::factory()->create(['servings' => 4]);
    $ingredient = \App\Models\Ingredient::factory()->create([
        'name' => 'chicken breast',
        'category' => \App\Enums\IngredientCategory::MEAT,
    ]);

    $recipe->recipeIngredients()->create([
        'ingredient_id' => $ingredient->id,
        'quantity' => 1,
        'unit' => \App\Enums\MeasurementUnit::LB,
    ]);

    // Assign recipe to meal plan
    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    // Add a note to the same slot (should NOT contribute to grocery list)
    MealPlanNote::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
        'title' => 'Eating out at Mom\'s house',
        'details' => 'Birthday dinner - no cooking needed',
    ]);

    // Generate grocery list
    $groceryList = app(\App\Services\GroceryListGenerator::class)->generate($mealPlan);

    // Should only have ingredients from the recipe, not from notes
    expect($groceryList->groceryItems()->count())->toBe(1);
    expect($groceryList->groceryItems->first()->name)->toBe('Chicken breast');
});

// T040: Meal plan with only notes generates empty grocery list
it('generates empty grocery list when meal plan has only notes', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    // Add notes but no recipes
    MealPlanNote::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::BREAKFAST,
        'title' => 'Fasting day',
    ]);

    MealPlanNote::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'date' => '2025-10-16',
        'meal_type' => MealType::DINNER,
        'title' => 'Eating out',
    ]);

    // Generate grocery list
    $groceryList = app(\App\Services\GroceryListGenerator::class)->generate($mealPlan);

    // Should be empty since notes don't contribute ingredients
    expect($groceryList)->toBeInstanceOf(\App\Models\GroceryList::class);
    expect($groceryList->groceryItems()->count())->toBe(0);
});
