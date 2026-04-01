<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The new 19-value category set.
     *
     * @var array<string>
     */
    private array $newCategories = [
        'bakery',
        'breakfast',
        'beverages',
        'condiments-and-dressings',
        'cooking-and-baking',
        'dairy',
        'deli',
        'frozen',
        'grains-and-pasta',
        'health-and-personal-care',
        'household-and-cleaning',
        'meat',
        'other',
        'pet-supplies',
        'produce',
        'seafood',
        'snacks',
        'soups-and-canned-goods',
        'wine-beer-and-spirits',
    ];

    /**
     * The original 10-value category set.
     *
     * @var array<string>
     */
    private array $oldCategories = [
        'produce',
        'dairy',
        'meat',
        'seafood',
        'pantry',
        'frozen',
        'bakery',
        'deli',
        'beverages',
        'other',
    ];

    /**
     * Union of old and new categories used as a transitional ENUM during migration.
     * Allows both 'pantry' and 'soups-and-canned-goods' to coexist so the data
     * UPDATE can succeed before the final ENUM is applied.
     *
     * @var array<string>
     */
    private array $transitionCategories = [
        'bakery',
        'breakfast',
        'beverages',
        'condiments-and-dressings',
        'cooking-and-baking',
        'dairy',
        'deli',
        'frozen',
        'grains-and-pasta',
        'health-and-personal-care',
        'household-and-cleaning',
        'meat',
        'other',
        'pantry',
        'pet-supplies',
        'produce',
        'seafood',
        'snacks',
        'soups-and-canned-goods',
        'wine-beer-and-spirits',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        // Step 1: Expand ENUM to the transition set (old + new) so that both
        // 'pantry' (existing rows) and 'soups-and-canned-goods' (target) are valid.
        $this->alterCategoryEnum($driver, $this->transitionCategories);

        // Step 2: Remap data — now safe because both values are in the ENUM.
        foreach (['grocery_items', 'ingredients', 'common_item_templates', 'user_item_templates'] as $table) {
            DB::table($table)->where('category', 'pantry')
                ->update(['category' => 'soups-and-canned-goods']);
        }

        // Step 3: Shrink ENUM to the final 19-value set (removes 'pantry').
        $this->alterCategoryEnum($driver, $this->newCategories);

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
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        // New-only categories that have no equivalent in the old set.
        $newOnlyCategories = [
            'breakfast',
            'condiments-and-dressings',
            'cooking-and-baking',
            'grains-and-pasta',
            'health-and-personal-care',
            'household-and-cleaning',
            'pet-supplies',
            'snacks',
            'wine-beer-and-spirits',
        ];

        // Step 1: Expand ENUM to the transition set so 'pantry' becomes valid again.
        $this->alterCategoryEnum($driver, $this->transitionCategories);

        // Step 2: Remap data — both values are valid in the transition ENUM.
        foreach (['grocery_items', 'ingredients', 'common_item_templates', 'user_item_templates'] as $table) {
            DB::table($table)->whereIn('category', $newOnlyCategories)
                ->update(['category' => 'other']);
            DB::table($table)->where('category', 'soups-and-canned-goods')
                ->update(['category' => 'pantry']);
        }

        // Step 3: Shrink ENUM back to the original 10-value set.
        $this->alterCategoryEnum($driver, $this->oldCategories);

        // Revert JSON column grocery_lists.excluded_categories
        DB::table('grocery_lists')->whereNotNull('excluded_categories')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $cats = json_decode($row->excluded_categories, true);
                    if (is_array($cats) && in_array('soups-and-canned-goods', $cats)) {
                        $cats = array_values(array_map(
                            fn ($c) => $c === 'soups-and-canned-goods' ? 'pantry' : $c,
                            $cats
                        ));
                        DB::table('grocery_lists')->where('id', $row->id)
                            ->update(['excluded_categories' => json_encode($cats)]);
                    }
                }
            });

        // Revert JSON column users.grocery_category_exclusions
        DB::table('users')->whereNotNull('grocery_category_exclusions')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $cats = json_decode($row->grocery_category_exclusions, true);
                    if (is_array($cats) && in_array('soups-and-canned-goods', $cats)) {
                        $cats = array_values(array_map(
                            fn ($c) => $c === 'soups-and-canned-goods' ? 'pantry' : $c,
                            $cats
                        ));
                        DB::table('users')->where('id', $row->id)
                            ->update(['grocery_category_exclusions' => json_encode($cats)]);
                    }
                }
            });
    }

    /**
     * Alter the category ENUM column on all four tables to the given value set.
     *
     * @param  array<string>  $categories
     */
    private function alterCategoryEnum(string $driver, array $categories): void
    {
        if ($driver !== 'sqlite') {
            $enumList = implode("','", $categories);

            DB::statement("ALTER TABLE grocery_items MODIFY COLUMN category ENUM('{$enumList}') NOT NULL DEFAULT 'other'");
            DB::statement("ALTER TABLE ingredients MODIFY COLUMN category ENUM('{$enumList}') NOT NULL DEFAULT 'other'");
            DB::statement("ALTER TABLE common_item_templates MODIFY COLUMN category ENUM('{$enumList}') NOT NULL");
            DB::statement("ALTER TABLE user_item_templates MODIFY COLUMN category ENUM('{$enumList}') NOT NULL");
        } else {
            Schema::table('grocery_items', function (Blueprint $table) use ($categories) {
                $table->enum('category', $categories)->default('other')->change();
            });
            Schema::table('ingredients', function (Blueprint $table) use ($categories) {
                $table->enum('category', $categories)->default('other')->change();
            });
            Schema::table('common_item_templates', function (Blueprint $table) use ($categories) {
                $table->enum('category', $categories)->change();
            });
            Schema::table('user_item_templates', function (Blueprint $table) use ($categories) {
                $table->enum('category', $categories)->change();
            });
        }
    }
};
