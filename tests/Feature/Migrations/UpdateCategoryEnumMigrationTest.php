<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Bypass SQLite CHECK constraints so we can insert legacy 'pantry' values.
 */
function disableSqliteCheckConstraints(): void
{
    if (DB::getDriverName() === 'sqlite') {
        DB::statement('PRAGMA ignore_check_constraints = ON');
    }
}

/**
 * Re-enable SQLite CHECK constraints after inserting test data.
 */
function enableSqliteCheckConstraints(): void
{
    if (DB::getDriverName() === 'sqlite') {
        DB::statement('PRAGMA ignore_check_constraints = OFF');
    }
}

/**
 * Run only the data migration logic from the migration's up() method.
 * Schema ENUM changes are skipped since they are DB-driver-specific.
 */
function runDataMigrationUp(): void
{
    // Remap pantry -> soups-and-canned-goods in all four category columns
    foreach (['grocery_items', 'ingredients', 'common_item_templates', 'user_item_templates'] as $table) {
        DB::table($table)->where('category', 'pantry')
            ->update(['category' => 'soups-and-canned-goods']);
    }

    // Migrate JSON column grocery_lists.excluded_categories
    DB::table('grocery_lists')->whereNotNull('excluded_categories')
        ->chunkById(100, function ($rows): void {
            foreach ($rows as $row) {
                $cats = json_decode($row->excluded_categories, true);
                if (is_array($cats) && in_array('pantry', $cats)) {
                    $cats = array_values(array_map(
                        fn ($c) => $c === 'pantry' ? 'soups-and-canned-goods' : $c,
                        $cats
                    ));
                    DB::table('grocery_lists')->where('id', $row->id)
                        ->update(['excluded_categories' => json_encode($cats)]);
                }
            }
        });

    // Migrate JSON column users.grocery_category_exclusions
    DB::table('users')->whereNotNull('grocery_category_exclusions')
        ->chunkById(100, function ($rows): void {
            foreach ($rows as $row) {
                $cats = json_decode($row->grocery_category_exclusions, true);
                if (is_array($cats) && in_array('pantry', $cats)) {
                    $cats = array_values(array_map(
                        fn ($c) => $c === 'pantry' ? 'soups-and-canned-goods' : $c,
                        $cats
                    ));
                    DB::table('users')->where('id', $row->id)
                        ->update(['grocery_category_exclusions' => json_encode($cats)]);
                }
            }
        });
}

/**
 * Create a test user and return its ID.
 */
function createMigrationTestUser(string $email): int
{
    return DB::table('users')->insertGetId([
        'name' => 'Test User',
        'email' => $email,
        'password' => bcrypt('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

/**
 * Create a test grocery list and return its ID.
 */
function createMigrationTestGroceryList(int $userId, ?string $excludedCategories = null): int
{
    return DB::table('grocery_lists')->insertGetId([
        'user_id' => $userId,
        'name' => 'Test List',
        'excluded_categories' => $excludedCategories,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('remaps pantry grocery items to soups and canned goods', function () {
    $userId = createMigrationTestUser('grocery1@example.com');
    $listId = createMigrationTestGroceryList($userId);

    disableSqliteCheckConstraints();
    $itemId = DB::table('grocery_items')->insertGetId([
        'grocery_list_id' => $listId,
        'name' => 'Canned Tomatoes',
        'category' => 'pantry',
        'source_type' => 'manual',
        'purchased' => false,
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    runDataMigrationUp();
    enableSqliteCheckConstraints();

    expect(DB::table('grocery_items')->where('id', $itemId)->value('category'))
        ->toBe('soups-and-canned-goods');
});

it('remaps pantry ingredients to soups and canned goods', function () {
    disableSqliteCheckConstraints();
    $ingredientId = DB::table('ingredients')->insertGetId([
        'name' => 'Canned Soup',
        'category' => 'pantry',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    runDataMigrationUp();
    enableSqliteCheckConstraints();

    expect(DB::table('ingredients')->where('id', $ingredientId)->value('category'))
        ->toBe('soups-and-canned-goods');
});

it('remaps pantry common item templates to soups and canned goods', function () {
    disableSqliteCheckConstraints();
    $templateId = DB::table('common_item_templates')->insertGetId([
        'name' => 'Chicken Broth',
        'category' => 'pantry',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    runDataMigrationUp();
    enableSqliteCheckConstraints();

    expect(DB::table('common_item_templates')->where('id', $templateId)->value('category'))
        ->toBe('soups-and-canned-goods');
});

it('remaps pantry user item templates to soups and canned goods', function () {
    $userId = createMigrationTestUser('templates1@example.com');

    disableSqliteCheckConstraints();
    $templateId = DB::table('user_item_templates')->insertGetId([
        'user_id' => $userId,
        'name' => 'Tomato Paste',
        'category' => 'pantry',
        'last_used_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    runDataMigrationUp();
    enableSqliteCheckConstraints();

    expect(DB::table('user_item_templates')->where('id', $templateId)->value('category'))
        ->toBe('soups-and-canned-goods');
});

it('leaves all other grocery item categories unchanged', function () {
    $userId = createMigrationTestUser('grocery2@example.com');
    $listId = createMigrationTestGroceryList($userId);

    $produceId = DB::table('grocery_items')->insertGetId([
        'grocery_list_id' => $listId,
        'name' => 'Apples',
        'category' => 'produce',
        'source_type' => 'manual',
        'purchased' => false,
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $dairyId = DB::table('grocery_items')->insertGetId([
        'grocery_list_id' => $listId,
        'name' => 'Milk',
        'category' => 'dairy',
        'source_type' => 'manual',
        'purchased' => false,
        'sort_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    runDataMigrationUp();

    expect(DB::table('grocery_items')->where('id', $produceId)->value('category'))->toBe('produce')
        ->and(DB::table('grocery_items')->where('id', $dairyId)->value('category'))->toBe('dairy');
});

it('remaps pantry in grocery list excluded categories json', function () {
    $userId = createMigrationTestUser('grocery3@example.com');
    $listId = createMigrationTestGroceryList($userId, json_encode(['pantry', 'dairy']));

    runDataMigrationUp();

    $updated = json_decode(
        DB::table('grocery_lists')->where('id', $listId)->value('excluded_categories'),
        true
    );

    expect($updated)->toBe(['soups-and-canned-goods', 'dairy']);
});

it('remaps pantry in user grocery category exclusions json', function () {
    $userId = createMigrationTestUser('user1@example.com');
    DB::table('users')->where('id', $userId)->update([
        'grocery_category_exclusions' => json_encode(['pantry', 'frozen']),
    ]);

    runDataMigrationUp();

    $updated = json_decode(
        DB::table('users')->where('id', $userId)->value('grocery_category_exclusions'),
        true
    );

    expect($updated)->toBe(['soups-and-canned-goods', 'frozen']);
});

it('handles null excluded categories gracefully', function () {
    $userId = createMigrationTestUser('grocery4@example.com');
    $listId = createMigrationTestGroceryList($userId, null);

    runDataMigrationUp();

    expect(DB::table('grocery_lists')->where('id', $listId)->value('excluded_categories'))
        ->toBeNull();
});

it('handles null user grocery category exclusions gracefully', function () {
    $userId = createMigrationTestUser('user2@example.com');
    // grocery_category_exclusions is null by default

    runDataMigrationUp();

    expect(DB::table('users')->where('id', $userId)->value('grocery_category_exclusions'))
        ->toBeNull();
});

it('handles malformed json in excluded categories gracefully', function () {
    $userId = createMigrationTestUser('grocery5@example.com');
    $listId = createMigrationTestGroceryList($userId, 'not-valid-json');

    runDataMigrationUp();

    // Malformed JSON decodes to null (not an array), so the row is skipped
    expect(DB::table('grocery_lists')->where('id', $listId)->value('excluded_categories'))
        ->toBe('not-valid-json');
});

it('accepts all 19 new category values on grocery items', function () {
    $userId = createMigrationTestUser('grocery6@example.com');
    $listId = createMigrationTestGroceryList($userId);

    $newCategories = [
        'bakery', 'breakfast', 'beverages', 'condiments-and-dressings', 'cooking-and-baking',
        'dairy', 'deli', 'frozen', 'grains-and-pasta', 'health-and-personal-care',
        'household-and-cleaning', 'meat', 'other', 'pet-supplies', 'produce',
        'seafood', 'snacks', 'soups-and-canned-goods', 'wine-beer-and-spirits',
    ];

    $ids = [];
    foreach ($newCategories as $i => $category) {
        $ids[$category] = DB::table('grocery_items')->insertGetId([
            'grocery_list_id' => $listId,
            'name' => "Item {$i}",
            'category' => $category,
            'source_type' => 'manual',
            'purchased' => false,
            'sort_order' => $i,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    foreach ($newCategories as $category) {
        expect(DB::table('grocery_items')->where('id', $ids[$category])->value('category'))
            ->toBe($category);
    }
});
