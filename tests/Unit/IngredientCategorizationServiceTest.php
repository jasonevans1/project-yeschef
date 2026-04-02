<?php

declare(strict_types=1);

use App\Enums\IngredientCategory;
use App\Models\CommonItemTemplate;
use App\Models\User;
use App\Models\UserItemTemplate;
use App\Services\IngredientCategorizationService;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns the category from the user item template when a match exists', function () {
    $user = User::factory()->create();
    UserItemTemplate::factory()->create([
        'user_id' => $user->id,
        'name' => 'Whole Milk',
        'category' => IngredientCategory::DAIRY,
    ]);

    $service = new IngredientCategorizationService;

    expect($service->resolve('whole milk', $user->id))->toBe(IngredientCategory::DAIRY);
});

it('skips user item template lookup when user id is zero (the no-user sentinel)', function () {
    $user = User::factory()->create();
    UserItemTemplate::factory()->create([
        'user_id' => $user->id,
        'name' => 'zyzzyva extract',
        'category' => IngredientCategory::SNACKS,
    ]);

    $service = new IngredientCategorizationService;

    // userId=0 skips user template; no common template; keyword miss → OTHER
    $result = $service->resolve('zyzzyva extract', 0);

    expect($result)->toBe(IngredientCategory::OTHER);
});

it('falls back to common item template when no user template match', function () {
    $user = User::factory()->create();
    CommonItemTemplate::create([
        'name' => 'greek yogurt',
        'category' => IngredientCategory::DAIRY,
        'unit' => null,
        'default_quantity' => null,
        'search_keywords' => null,
        'usage_count' => 0,
    ]);

    $service = new IngredientCategorizationService;

    $result = $service->resolve('greek yogurt', $user->id);

    expect($result)->toBe(IngredientCategory::DAIRY);
});

it('falls back to keyword matching when no template match', function () {
    $user = User::factory()->create();

    $service = new IngredientCategorizationService;

    expect($service->resolve('fresh salmon fillet', $user->id))->toBe(IngredientCategory::SEAFOOD);
});

it('matches produce keywords correctly', function () {
    $service = new IngredientCategorizationService;

    expect($service->resolve('roma tomato', 0))->toBe(IngredientCategory::PRODUCE);
    expect($service->resolve('fresh garlic cloves', 0))->toBe(IngredientCategory::PRODUCE);
    expect($service->resolve('baby spinach', 0))->toBe(IngredientCategory::PRODUCE);
});

it('matches dairy keywords correctly', function () {
    $service = new IngredientCategorizationService;

    expect($service->resolve('whole milk', 0))->toBe(IngredientCategory::DAIRY);
    expect($service->resolve('sharp cheddar cheese', 0))->toBe(IngredientCategory::DAIRY);
    expect($service->resolve('unsalted butter', 0))->toBe(IngredientCategory::DAIRY);
});

it('matches meat keywords correctly', function () {
    $service = new IngredientCategorizationService;

    expect($service->resolve('boneless chicken breast', 0))->toBe(IngredientCategory::MEAT);
    expect($service->resolve('ground beef', 0))->toBe(IngredientCategory::MEAT);
    expect($service->resolve('pork tenderloin', 0))->toBe(IngredientCategory::MEAT);
});

it('matches seafood keywords correctly', function () {
    $service = new IngredientCategorizationService;

    expect($service->resolve('atlantic salmon', 0))->toBe(IngredientCategory::SEAFOOD);
    expect($service->resolve('large shrimp', 0))->toBe(IngredientCategory::SEAFOOD);
    expect($service->resolve('albacore tuna', 0))->toBe(IngredientCategory::SEAFOOD);
});

it('matches cooking and baking keywords correctly', function () {
    $service = new IngredientCategorizationService;

    expect($service->resolve('all-purpose flour', 0))->toBe(IngredientCategory::COOKING_AND_BAKING);
    expect($service->resolve('granulated sugar', 0))->toBe(IngredientCategory::COOKING_AND_BAKING);
    expect($service->resolve('extra virgin olive oil', 0))->toBe(IngredientCategory::COOKING_AND_BAKING);
});

it('matches grains and pasta keywords correctly', function () {
    $service = new IngredientCategorizationService;

    expect($service->resolve('long grain rice', 0))->toBe(IngredientCategory::GRAINS_AND_PASTA);
    expect($service->resolve('penne pasta', 0))->toBe(IngredientCategory::GRAINS_AND_PASTA);
    expect($service->resolve('black beans', 0))->toBe(IngredientCategory::GRAINS_AND_PASTA);
});

it('matches bakery keywords correctly', function () {
    $service = new IngredientCategorizationService;

    expect($service->resolve('sourdough bread', 0))->toBe(IngredientCategory::BAKERY);
    expect($service->resolve('hamburger bun', 0))->toBe(IngredientCategory::BAKERY);
    expect($service->resolve('flour tortilla', 0))->toBe(IngredientCategory::BAKERY);
});

it('maps wine and beer to wine-beer-and-spirits not beverages', function () {
    $service = new IngredientCategorizationService;

    expect($service->resolve('red wine', 0))->toBe(IngredientCategory::WINE_BEER_AND_SPIRITS);
    expect($service->resolve('craft beer', 0))->toBe(IngredientCategory::WINE_BEER_AND_SPIRITS);
    expect($service->resolve('bourbon whiskey', 0))->toBe(IngredientCategory::WINE_BEER_AND_SPIRITS);
    expect($service->resolve('dark rum', 0))->toBe(IngredientCategory::WINE_BEER_AND_SPIRITS);
});

it('maps juice and soda to beverages', function () {
    $service = new IngredientCategorizationService;

    expect($service->resolve('orange juice', 0))->toBe(IngredientCategory::BEVERAGES);
    expect($service->resolve('club soda', 0))->toBe(IngredientCategory::BEVERAGES);
    expect($service->resolve('black coffee', 0))->toBe(IngredientCategory::BEVERAGES);
});

it('returns OTHER when no template or keyword match', function () {
    $service = new IngredientCategorizationService;

    expect($service->resolve('xylitol crystals', 0))->toBe(IngredientCategory::OTHER);
});

it('is case insensitive for name matching', function () {
    $user = User::factory()->create();
    UserItemTemplate::factory()->create([
        'user_id' => $user->id,
        'name' => 'Greek Yogurt',
        'category' => IngredientCategory::DAIRY,
    ]);

    $service = new IngredientCategorizationService;

    expect($service->resolve('GREEK YOGURT', $user->id))->toBe(IngredientCategory::DAIRY);
    expect($service->resolve('Greek Yogurt', $user->id))->toBe(IngredientCategory::DAIRY);
    expect($service->resolve('greek yogurt', $user->id))->toBe(IngredientCategory::DAIRY);
});
