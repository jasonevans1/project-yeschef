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

dataset('keyword categories', function () {
    $map = [
        'wine-beer-and-spirits' => ['wine', 'beer', 'spirits', 'liquor', 'whiskey', 'vodka', 'rum'],
        'beverages' => ['juice', 'soda', 'water', 'coffee', 'tea'],
        'produce' => ['lettuce', 'tomato', 'onion', 'garlic', 'pepper', 'carrot', 'celery', 'potato', 'spinach', 'broccoli', 'cucumber', 'apple', 'banana', 'orange', 'lemon', 'lime', 'herb', 'basil', 'parsley', 'cilantro'],
        'dairy' => ['milk', 'cheese', 'butter', 'cream', 'yogurt', 'ricotta', 'mozzarella', 'parmesan', 'cheddar'],
        'meat' => ['chicken', 'beef', 'pork', 'turkey', 'lamb', 'bacon', 'sausage'],
        'seafood' => ['fish', 'salmon', 'tuna', 'shrimp', 'crab', 'lobster', 'cod', 'tilapia'],
        'bakery' => ['bread', 'bun', 'roll', 'tortilla', 'pita', 'bagel'],
        'cooking-and-baking' => ['flour', 'sugar', 'salt', 'oil', 'vinegar', 'spice', 'cumin', 'paprika', 'oregano'],
        'grains-and-pasta' => ['rice', 'pasta', 'beans'],
        'soups-and-canned-goods' => ['sauce'],
        'frozen' => ['frozen'],
    ];

    foreach ($map as $category => $keywords) {
        foreach ($keywords as $keyword) {
            yield $keyword => [$keyword, $category];
        }
    }
});

it('categorizes by keyword', function (string $keyword, string $category) {
    $service = new IngredientCategorizationService;

    expect($service->resolve('Zz'.strtoupper($keyword), 0))->toBe(IngredientCategory::from($category));
})->with('keyword categories');

it('treats user id 1 as a real user and still skips lookup for zero and negative ids', function () {
    $user = User::factory()->create();
    UserItemTemplate::factory()->create([
        'user_id' => $user->id,
        'name' => 'zyzzyva extract',
        'category' => IngredientCategory::SNACKS,
    ]);

    $service = new IngredientCategorizationService;

    expect($service->resolve('zyzzyva extract', $user->id))->toBe(IngredientCategory::SNACKS)
        ->and($service->resolve('zyzzyva extract', -1))->toBe(IngredientCategory::OTHER)
        ->and($service->resolve('zyzzyva extract', 0))->toBe(IngredientCategory::OTHER);
});

it('looks up user templates for user id 1', function () {
    $user = User::factory()->create(['id' => 1]);
    UserItemTemplate::factory()->create([
        'user_id' => 1,
        'name' => 'zyzzyva extract',
        'category' => IngredientCategory::SNACKS,
    ]);

    expect((new IngredientCategorizationService)->resolve('zyzzyva extract', 1))->toBe(IngredientCategory::SNACKS);
});

it('does not use any user template when no user id is passed', function () {
    User::factory()->create(['id' => 1]);
    UserItemTemplate::factory()->create([
        'user_id' => 1,
        'name' => 'zyzzyva extract',
        'category' => IngredientCategory::SNACKS,
    ]);

    expect((new IngredientCategorizationService)->resolve('zyzzyva extract'))->toBe(IngredientCategory::OTHER);
});
