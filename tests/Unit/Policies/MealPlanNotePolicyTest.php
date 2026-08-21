<?php

declare(strict_types=1);

use App\Models\MealPlan;
use App\Models\MealPlanNote;
use App\Models\User;
use App\Policies\MealPlanNotePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

// view/update/delete delegate to the parent meal plan's policy, which queries ContentShare
// for non-owners, so this needs a full Laravel app + database — Pest.php only auto-binds
// Tests\TestCase/RefreshDatabase for Feature/Browser, so bind them locally for this file.
uses(Tests\TestCase::class, RefreshDatabase::class);

// FR-011 (partial): note access is restricted to the plan owner, by delegation to MealPlanPolicy.
// create()/viewAny() are unconditional — MealPlanNotePolicy::create() receives no note instance,
// so there is no meal plan to check ownership against. Real gating for note creation happens one
// level up, in App\Livewire\MealPlans\Show::openNoteForm() and saveNote(), both via
// authorize('update', $this->mealPlan).
test('it restricts meal plan note view/update/delete access to the plan owner', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($owner)->create();
    $note = MealPlanNote::factory()->for($mealPlan)->create();
    $policy = new MealPlanNotePolicy;

    expect($policy->view($owner, $note))->toBeTrue()
        ->and($policy->view($stranger, $note))->toBeFalse()
        ->and($policy->update($owner, $note))->toBeTrue()
        ->and($policy->update($stranger, $note))->toBeFalse()
        ->and($policy->delete($owner, $note))->toBeTrue()
        ->and($policy->delete($stranger, $note))->toBeFalse()
        ->and($policy->create($stranger))->toBeTrue()
        ->and($policy->viewAny($stranger))->toBeTrue();
});
