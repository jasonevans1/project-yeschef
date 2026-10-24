<?php

use App\Enums\MealType;
use App\Enums\SharePermission;
use App\Livewire\MealPlans\Show;
use App\Mail\ShareInvitation;
use App\Models\ContentShare;
use App\Models\Ingredient;
use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\MealPlanNote;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $GLOBALS['flashed'] = [];
    \Livewire\on('dehydrate', function () {
        foreach (session('_flash.new', []) as $key) {
            $GLOBALS['flashed'][$key] = session($key);
        }
    });
    $this->user = User::factory()->create();
    $this->plan = MealPlan::factory()->for($this->user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-16',
    ]);
});

function flashed(string $key): mixed
{
    return $GLOBALS['flashed'][$key] ?? null;
}

function showFor($test, ?User $user = null)
{
    return Livewire::actingAs($user ?? $test->user)->test(Show::class, ['mealPlan' => $test->plan]);
}

it('has expected default state', function () {
    showFor($this)
        ->assertSet('selectedDate', null)
        ->assertSet('selectedMealType', null)
        ->assertSet('showRecipeSelector', false)
        ->assertSet('recipeSearch', '')
        ->assertSet('servingMultiplier', 1.0)
        ->assertSet('selectedAssignmentId', null)
        ->assertSet('showRecipeDrawer', false)
        ->assertSet('showShareModal', false)
        ->assertSet('shareEmail', '')
        ->assertSet('sharePermission', 'read')
        ->assertSet('showNoteForm', false)
        ->assertSet('noteTitle', '')
        ->assertSet('noteDetails', '')
        ->assertSet('editingNoteId', null)
        ->assertSet('showNoteDrawer', false)
        ->assertSet('selectedNoteId', null);
});

it('forbids viewing an unrelated meal plan', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(Show::class, ['mealPlan' => $this->plan])
        ->assertForbidden();
});

it('opens and closes the recipe selector resetting state', function () {
    showFor($this)
        ->set('recipeSearch', 'abc')
        ->set('servingMultiplier', 3.0)
        ->call('openRecipeSelector', '2025-10-15', 'lunch')
        ->assertSet('selectedDate', '2025-10-15')
        ->assertSet('selectedMealType', 'lunch')
        ->assertSet('showRecipeSelector', true)
        ->assertSet('recipeSearch', '')
        ->assertSet('servingMultiplier', 1.0)
        ->set('recipeSearch', 'abc')
        ->set('servingMultiplier', 2.5)
        ->call('closeRecipeSelector')
        ->assertSet('showRecipeSelector', false)
        ->assertSet('selectedDate', null)
        ->assertSet('selectedMealType', null)
        ->assertSet('recipeSearch', '')
        ->assertSet('servingMultiplier', 1.0);
});

it('assigns a recipe, flashes success and resets the selector', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->user->id]);

    showFor($this)
        ->call('openRecipeSelector', '2025-10-15', 'dinner')
        ->set('servingMultiplier', 2.5)
        ->call('assignRecipe', $recipe)
        ->assertHasNoErrors()
        ->assertSet('showRecipeSelector', false)
        ->assertSet('selectedDate', null)
        ->tap(fn () => expect(flashed('success'))->toBe('Recipe assigned successfully!'));

    $a = MealAssignment::first();
    expect($a->meal_plan_id)->toBe($this->plan->id)
        ->and($a->recipe_id)->toBe($recipe->id)
        ->and($a->date->format('Y-m-d'))->toBe('2025-10-15')
        ->and($a->meal_type)->toBe(MealType::DINNER)
        ->and((float) $a->serving_multiplier)->toBe(2.5);
});

it('allows multiple recipes in the same slot', function () {
    $recipes = Recipe::factory()->count(2)->create(['user_id' => $this->user->id]);
    $c = showFor($this);
    foreach ($recipes as $r) {
        $c->call('openRecipeSelector', '2025-10-15', 'dinner')->call('assignRecipe', $r);
    }
    expect(MealAssignment::count())->toBe(2);
});

it('does nothing when no slot is selected', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->user->id]);

    showFor($this)->call('assignRecipe', $recipe)->assertSessionMissing('success');
    expect(MealAssignment::count())->toBe(0);

    showFor($this)->set('selectedDate', '2025-10-15')->call('assignRecipe', $recipe);
    showFor($this)->set('selectedMealType', 'lunch')->call('assignRecipe', $recipe);
    expect(MealAssignment::count())->toBe(0);
});

it('rejects dates outside the plan range but accepts boundary dates', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->user->id]);

    foreach (['2025-10-13', '2025-10-17'] as $date) {
        showFor($this)
            ->call('openRecipeSelector', $date, 'lunch')
            ->call('assignRecipe', $recipe)
            ->tap(fn () => expect(flashed('error'))->toBe('Selected date is outside the meal plan range.'));
    }
    expect(MealAssignment::count())->toBe(0);

    foreach (['2025-10-14', '2025-10-16'] as $date) {
        showFor($this)->call('openRecipeSelector', $date, 'lunch')->call('assignRecipe', $recipe);
    }
    expect(MealAssignment::count())->toBe(2);
});

it('validates the serving multiplier bounds', function (float $value, bool $valid) {
    $recipe = Recipe::factory()->create(['user_id' => $this->user->id]);
    $c = showFor($this)
        ->call('openRecipeSelector', '2025-10-15', 'lunch')
        ->set('servingMultiplier', $value)
        ->call('assignRecipe', $recipe);
    $valid ? $c->assertHasNoErrors() : $c->assertHasErrors('servingMultiplier');
    expect(MealAssignment::count())->toBe($valid ? 1 : 0);
})->with([
    'too low' => [0.2, false],
    'min' => [0.25, true],
    'max' => [10.0, true],
    'too high' => [10.5, false],
]);

it('forbids assigning for read-only viewers', function () {
    $viewer = User::factory()->create();
    ContentShare::factory()->forMealPlan($this->plan)->create([
        'owner_id' => $this->user->id, 'recipient_id' => $viewer->id,
        'recipient_email' => $viewer->email, 'permission' => SharePermission::Read,
    ]);
    $recipe = Recipe::factory()->create(['user_id' => $this->user->id]);

    showFor($this, $viewer)
        ->call('openRecipeSelector', '2025-10-15', 'lunch')
        ->call('assignRecipe', $recipe)
        ->assertForbidden();
    expect(MealAssignment::count())->toBe(0);
});

it('removes an assignment', function () {
    $a = MealAssignment::factory()->create(['meal_plan_id' => $this->plan->id, 'date' => '2025-10-15']);

    showFor($this)->call('removeAssignment', $a)
        ->tap(fn () => expect(flashed('success'))->toBe('Recipe removed from meal plan.'));
    expect(MealAssignment::count())->toBe(0);
});

it('forbids removing an assignment without update access', function () {
    $a = MealAssignment::factory()->create(['meal_plan_id' => $this->plan->id, 'date' => '2025-10-15']);

    showFor($this, User::factory()->create())->assertForbidden();
    $viewer = User::factory()->create();
    ContentShare::factory()->forMealPlan($this->plan)->create([
        'owner_id' => $this->user->id, 'recipient_id' => $viewer->id,
        'recipient_email' => $viewer->email, 'permission' => SharePermission::Read,
    ]);
    showFor($this, $viewer)->call('removeAssignment', $a)->assertForbidden();
    expect(MealAssignment::count())->toBe(1);
});

it('opens and closes the recipe drawer', function () {
    $a = MealAssignment::factory()->create(['meal_plan_id' => $this->plan->id, 'date' => '2025-10-15']);

    showFor($this)->call('openRecipeDrawer', $a)
        ->assertSet('selectedAssignmentId', $a->id)
        ->assertSet('showRecipeDrawer', true)
        ->call('closeRecipeDrawer')
        ->assertSet('selectedAssignmentId', null)
        ->assertSet('showRecipeDrawer', false);
});

it('opens the note form with a clean state', function () {
    $note = MealPlanNote::factory()->create(['meal_plan_id' => $this->plan->id, 'date' => '2025-10-15']);

    showFor($this)
        ->set('noteTitle', 'x')->set('noteDetails', 'y')->set('editingNoteId', $note->id)
        ->call('openNoteForm', '2025-10-15', 'snack')
        ->assertSet('selectedDate', '2025-10-15')
        ->assertSet('selectedMealType', 'snack')
        ->assertSet('noteTitle', '')
        ->assertSet('noteDetails', '')
        ->assertSet('editingNoteId', null)
        ->assertSet('showNoteForm', true);
});

it('forbids opening the note form for read-only viewers', function () {
    $viewer = User::factory()->create();
    ContentShare::factory()->forMealPlan($this->plan)->create([
        'owner_id' => $this->user->id, 'recipient_id' => $viewer->id,
        'recipient_email' => $viewer->email, 'permission' => SharePermission::Read,
    ]);
    showFor($this, $viewer)->call('openNoteForm', '2025-10-15', 'snack')->assertForbidden();
});

it('closes the note form and clears validation', function () {
    showFor($this)
        ->call('openNoteForm', '2025-10-15', 'snack')
        ->call('saveNote')
        ->assertHasErrors('noteTitle')
        ->set('noteTitle', 't')->set('noteDetails', 'd')
        ->call('closeNoteForm')
        ->assertHasNoErrors()
        ->assertSet('showNoteForm', false)
        ->assertSet('selectedDate', null)
        ->assertSet('selectedMealType', null)
        ->assertSet('noteTitle', '')
        ->assertSet('noteDetails', '')
        ->assertSet('editingNoteId', null);
});

it('clears details validation errors when closing the note form', function () {
    showFor($this)
        ->call('openNoteForm', '2025-10-15', 'snack')
        ->set('noteTitle', 't')
        ->set('noteDetails', str_repeat('a', 2001))
        ->call('saveNote')
        ->assertHasErrors('noteDetails')
        ->call('closeNoteForm')
        ->assertHasNoErrors();
});

it('saves a note with flash and closes the form, storing null for empty details', function () {
    showFor($this)
        ->call('openNoteForm', '2025-10-15', 'snack')
        ->set('noteTitle', 'Title')
        ->call('saveNote')
        ->tap(fn () => expect(flashed('success'))->toBe('Note added successfully!'))
        ->assertSet('showNoteForm', false);

    $n = MealPlanNote::first();
    expect($n->details)->toBeNull()
        ->and($n->title)->toBe('Title')
        ->and($n->meal_type)->toBe(MealType::SNACK);
});

it('validates note field lengths at the boundary', function () {
    showFor($this)->call('openNoteForm', '2025-10-15', 'snack')
        ->set('noteTitle', str_repeat('a', 255))
        ->set('noteDetails', str_repeat('b', 2000))
        ->call('saveNote')->assertHasNoErrors();
    showFor($this)->call('openNoteForm', '2025-10-15', 'snack')
        ->set('noteTitle', str_repeat('a', 256))->call('saveNote')->assertHasErrors('noteTitle');
    showFor($this)->call('openNoteForm', '2025-10-15', 'snack')
        ->set('noteTitle', 'ok')->set('noteDetails', str_repeat('b', 2001))
        ->call('saveNote')->assertHasErrors('noteDetails');
    expect(MealPlanNote::count())->toBe(1);
});

it('does not save a note without a selected slot', function () {
    showFor($this)->set('noteTitle', 'x')->call('saveNote')->assertSessionMissing('success');
    showFor($this)->set('noteTitle', 'x')->set('selectedDate', '2025-10-15')->call('saveNote');
    showFor($this)->set('noteTitle', 'x')->set('selectedMealType', 'lunch')->call('saveNote');
    expect(MealPlanNote::count())->toBe(0);
});

it('forbids saving notes for read-only viewers', function () {
    $viewer = User::factory()->create();
    ContentShare::factory()->forMealPlan($this->plan)->create([
        'owner_id' => $this->user->id, 'recipient_id' => $viewer->id,
        'recipient_email' => $viewer->email, 'permission' => SharePermission::Read,
    ]);
    showFor($this, $viewer)->set('noteTitle', 'x')->set('selectedDate', '2025-10-15')
        ->set('selectedMealType', 'lunch')->call('saveNote')->assertForbidden();
    expect(MealPlanNote::count())->toBe(0);
});

it('edits an existing note', function () {
    $note = MealPlanNote::factory()->create([
        'meal_plan_id' => $this->plan->id, 'date' => '2025-10-15',
        'meal_type' => MealType::LUNCH, 'title' => 'Old', 'details' => 'Old details',
    ]);

    showFor($this)
        ->set('showNoteDrawer', true)
        ->call('editNote', $note)
        ->assertSet('selectedDate', '2025-10-15')
        ->assertSet('selectedMealType', 'lunch')
        ->assertSet('noteTitle', 'Old')
        ->assertSet('noteDetails', 'Old details')
        ->assertSet('editingNoteId', $note->id)
        ->assertSet('showNoteDrawer', false)
        ->assertSet('showNoteForm', true)
        ->set('noteTitle', 'New')
        ->call('saveNote')
        ->tap(fn () => expect(flashed('success'))->toBe('Note updated successfully!'));

    expect(MealPlanNote::count())->toBe(1)
        ->and($note->fresh()->title)->toBe('New');
});

it('edits a note with null details as an empty string', function () {
    $note = MealPlanNote::factory()->create([
        'meal_plan_id' => $this->plan->id, 'date' => '2025-10-15', 'details' => null,
    ]);
    showFor($this)->call('editNote', $note)->assertSet('noteDetails', '');
});

it('forbids editing and deleting notes for read-only viewers', function () {
    $viewer = User::factory()->create();
    ContentShare::factory()->forMealPlan($this->plan)->create([
        'owner_id' => $this->user->id, 'recipient_id' => $viewer->id,
        'recipient_email' => $viewer->email, 'permission' => SharePermission::Read,
    ]);
    $note = MealPlanNote::factory()->create(['meal_plan_id' => $this->plan->id, 'date' => '2025-10-15']);

    showFor($this, $viewer)->call('editNote', $note)->assertForbidden();
    showFor($this, $viewer)->call('deleteNote', $note)->assertForbidden();
    expect(MealPlanNote::count())->toBe(1);
});

it('opens and closes the note drawer and exposes the selected note', function () {
    $note = MealPlanNote::factory()->create(['meal_plan_id' => $this->plan->id, 'date' => '2025-10-15']);

    $c = showFor($this)->call('openNoteDrawer', $note)
        ->assertSet('selectedNoteId', $note->id)
        ->assertSet('showNoteDrawer', true);
    expect($c->instance()->selectedNote->id)->toBe($note->id);

    $c->call('closeNoteDrawer')
        ->assertSet('selectedNoteId', null)
        ->assertSet('showNoteDrawer', false);
    expect($c->instance()->selectedNote)->toBeNull();
});

it('deletes a note and closes the drawer', function () {
    $note = MealPlanNote::factory()->create(['meal_plan_id' => $this->plan->id, 'date' => '2025-10-15']);

    showFor($this)->call('openNoteDrawer', $note)->call('deleteNote', $note)
        ->assertSet('showNoteDrawer', false)
        ->assertSet('selectedNoteId', null)
        ->tap(fn () => expect(flashed('success'))->toBe('Note deleted successfully.'));
    expect(MealPlanNote::count())->toBe(0);
});

it('opens and closes the share modal', function () {
    showFor($this)
        ->set('shareEmail', 'a@b.com')->set('sharePermission', 'write')
        ->call('openShareModal')
        ->assertSet('showShareModal', true)
        ->assertSet('shareEmail', '')
        ->assertSet('sharePermission', 'read')
        ->set('shareEmail', 'a@b.com')->set('sharePermission', 'write')
        ->call('closeShareModal')
        ->assertSet('showShareModal', false)
        ->assertSet('shareEmail', '')
        ->assertSet('sharePermission', 'read');
});

it('clears share validation errors on open and close', function () {
    showFor($this)->call('openShareModal')->call('shareWith')
        ->assertHasErrors('shareEmail')
        ->call('closeShareModal')->assertHasNoErrors();
    showFor($this)->call('openShareModal')->set('shareEmail', 'a@b.com')
        ->set('sharePermission', 'bad')->call('shareWith')
        ->assertHasErrors('sharePermission')
        ->call('openShareModal')->assertHasNoErrors();
    showFor($this)->call('openShareModal')->set('shareEmail', 'a@b.com')
        ->set('sharePermission', 'bad')->call('shareWith')
        ->call('closeShareModal')->assertHasNoErrors();
});

it('validates share input', function () {
    showFor($this)->set('shareEmail', '')->call('shareWith')->assertHasErrors(['shareEmail' => 'required']);
    showFor($this)->set('shareEmail', 'nope')->call('shareWith')->assertHasErrors(['shareEmail' => 'email']);
    showFor($this)->set('shareEmail', str_repeat('a', 250).'@b.com')->call('shareWith')
        ->assertHasErrors(['shareEmail' => 'max']);
    showFor($this)->set('shareEmail', 'a@b.com')->set('sharePermission', 'admin')->call('shareWith')
        ->assertHasErrors(['sharePermission' => 'in']);
    showFor($this)->set('shareEmail', 'a@b.com')->set('sharePermission', '')->call('shareWith')
        ->assertHasErrors(['sharePermission' => 'required']);
    expect(ContentShare::count())->toBe(0);
});

it('prevents sharing with yourself', function () {
    showFor($this)->set('shareEmail', $this->user->email)->call('shareWith')
        ->assertHasErrors(['shareEmail']);
    expect(ContentShare::count())->toBe(0);
});

it('shares with an existing user without sending an invitation', function () {
    Mail::fake();
    $other = User::factory()->create();

    showFor($this)->set('shareEmail', $other->email)->set('sharePermission', 'write')
        ->call('shareWith')
        ->assertHasNoErrors()
        ->tap(fn () => expect(flashed('success'))->toBe("Shared with {$other->email}"))
        ->assertDispatched('close-modal')
        ->assertSet('showShareModal', false);

    $share = ContentShare::first();
    expect($share->owner_id)->toBe($this->user->id)
        ->and($share->recipient_id)->toBe($other->id)
        ->and($share->permission)->toBe(SharePermission::Write)
        ->and($share->shareable_type)->toBe(MealPlan::class)
        ->and($share->shareable_id)->toBe($this->plan->id)
        ->and($share->share_all)->toBeFalse();
    Mail::assertNothingSent();
});

it('sends an invitation once to unregistered recipients', function () {
    Mail::fake();

    $c = showFor($this)->set('shareEmail', 'new@example.com')->call('shareWith');
    $c->set('shareEmail', 'new@example.com')->call('shareWith');

    expect(ContentShare::count())->toBe(1)
        ->and(ContentShare::first()->recipient_id)->toBeNull();
    Mail::assertSent(ShareInvitation::class, 1);
    Mail::assertSent(ShareInvitation::class, function ($mail) {
        return $mail->hasTo('new@example.com')
            && $mail->ownerName === $this->user->name
            && $mail->contentDescription === "a meal plan: \"{$this->plan->name}\""
            && $mail->registerUrl === route('register');
    });
});

it('updates the permission of an existing share', function () {
    Mail::fake();
    $other = User::factory()->create();

    $c = showFor($this)->set('shareEmail', $other->email)->call('shareWith');
    $c->set('shareEmail', $other->email)->set('sharePermission', 'write')->call('shareWith');

    expect(ContentShare::count())->toBe(1)
        ->and(ContentShare::first()->permission)->toBe(SharePermission::Write);
});

it('forbids sharing without share access', function () {
    showFor($this, User::factory()->create())->assertForbidden();
    $viewer = User::factory()->create();
    ContentShare::factory()->forMealPlan($this->plan)->create([
        'owner_id' => $this->user->id, 'recipient_id' => $viewer->id,
        'recipient_email' => $viewer->email, 'permission' => SharePermission::Write,
    ]);
    showFor($this, $viewer)->set('shareEmail', 'a@b.com')->call('shareWith')->assertForbidden();
});

it('deletes the meal plan and redirects', function () {
    showFor($this)->call('delete')
        ->assertRedirect(route('meal-plans.index'));
    expect(MealPlan::count())->toBe(0);
});

it('forbids deleting for non-owners', function () {
    $viewer = User::factory()->create();
    ContentShare::factory()->forMealPlan($this->plan)->create([
        'owner_id' => $this->user->id, 'recipient_id' => $viewer->id,
        'recipient_email' => $viewer->email, 'permission' => SharePermission::Write,
    ]);
    showFor($this, $viewer)->call('delete')->assertForbidden();
    expect(MealPlan::count())->toBe(1);
});

it('lists system and own recipes only, filtered by name or description, capped at 20', function () {
    $mine = Recipe::factory()->create(['user_id' => $this->user->id, 'name' => 'Mine pasta', 'description' => 'x']);
    $system = Recipe::factory()->create(['user_id' => null, 'name' => 'System soup', 'description' => 'has pasta inside']);
    $other = Recipe::factory()->create(['name' => 'Other pasta']);

    $c = showFor($this);
    $ids = $c->instance()->recipes->pluck('id')->all();
    expect($ids)->toContain($mine->id, $system->id)->not->toContain($other->id);

    $c->set('recipeSearch', 'pasta');
    expect($c->instance()->recipes->pluck('id')->all())->toEqualCanonicalizing([$mine->id, $system->id]);

    $c->set('recipeSearch', 'soup');
    expect($c->instance()->recipes->pluck('id')->all())->toBe([$system->id]);

    $c->set('recipeSearch', 'zzz');
    expect($c->instance()->recipes)->toHaveCount(0);

    Recipe::factory()->count(25)->create(['user_id' => $this->user->id]);
    $c->set('recipeSearch', '');
    expect($c->instance()->recipes)->toHaveCount(20);
});

it('returns the selected assignment or null', function () {
    $a = MealAssignment::factory()->create(['meal_plan_id' => $this->plan->id, 'date' => '2025-10-15']);

    $c = showFor($this);
    expect($c->instance()->selectedAssignment)->toBeNull();
    $c->call('openRecipeDrawer', $a);
    expect($c->instance()->selectedAssignment->id)->toBe($a->id);
});

it('scales ingredients by the serving multiplier', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->user->id]);
    $ing = Ingredient::factory()->create(['name' => 'Flour']);
    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe->id, 'ingredient_id' => $ing->id, 'quantity' => 2, 'unit' => 'cup',
    ]);
    $noQty = Ingredient::factory()->create(['name' => 'Salt']);
    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe->id, 'ingredient_id' => $noQty->id, 'quantity' => null, 'unit' => null,
    ]);
    $a = MealAssignment::factory()->create([
        'meal_plan_id' => $this->plan->id, 'recipe_id' => $recipe->id,
        'date' => '2025-10-15', 'serving_multiplier' => 1.5,
    ]);

    $c = showFor($this);
    expect($c->instance()->scaledIngredients)->toBe([]);

    $c->call('openRecipeDrawer', $a);
    $rows = collect($c->instance()->scaledIngredients)->keyBy('name');
    expect($rows['Flour']['quantity'])->toBe('3')
        ->and($rows['Flour']['unit'])->toBe('cup')
        ->and($rows['Salt']['quantity'])->toBeNull()
        ->and($rows['Salt']['unit'])->toBe('');
});

it('renders assignments and notes grouped in the right slots', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->user->id, 'name' => 'Zesty Tacos']);
    MealAssignment::factory()->create([
        'meal_plan_id' => $this->plan->id, 'recipe_id' => $recipe->id,
        'date' => '2025-10-15', 'meal_type' => 'lunch',
    ]);
    MealPlanNote::factory()->create([
        'meal_plan_id' => $this->plan->id, 'date' => '2025-10-16',
        'meal_type' => MealType::DINNER, 'title' => 'Quirky Note',
    ]);

    $c = showFor($this)->assertSee('Zesty Tacos')->assertSee('Quirky Note');
    $view = $c->viewData('assignments');
    expect($view->keys()->all())->toBe(['2025-10-15_lunch'])
        ->and($c->viewData('notes')->keys()->all())->toBe(['2025-10-16_dinner'])
        ->and($c->viewData('dates'))->toHaveCount(3)
        ->and($c->viewData('dates')[2]->format('Y-m-d'))->toBe('2025-10-16')
        ->and($c->viewData('mealTypes'))->toBe(MealType::cases());
});
