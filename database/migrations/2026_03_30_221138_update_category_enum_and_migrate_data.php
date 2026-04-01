<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The new full set of category values.
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
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        // Remap pantry -> soups-and-canned-goods BEFORE altering the ENUM so MariaDB
        // does not reject rows with values absent from the new constraint.
        foreach (['grocery_items', 'ingredients', 'common_item_templates', 'user_item_templates'] as $table) {
            DB::table($table)->where('category', 'pantry')
                ->update(['category' => 'soups-and-canned-goods']);
        }

        // Now safe to alter the ENUM — no rows contain 'pantry' any more.
        if ($driver !== 'sqlite') {
            // MariaDB/MySQL: use raw ALTER TABLE MODIFY COLUMN for ENUM changes.
            // Note: briefly locks each table.
            $enumList = implode("','", $this->newCategories);

            DB::statement("ALTER TABLE grocery_items MODIFY COLUMN category ENUM('{$enumList}') NOT NULL DEFAULT 'other'");
            DB::statement("ALTER TABLE ingredients MODIFY COLUMN category ENUM('{$enumList}') NOT NULL DEFAULT 'other'");
            DB::statement("ALTER TABLE common_item_templates MODIFY COLUMN category ENUM('{$enumList}') NOT NULL");
            DB::statement("ALTER TABLE user_item_templates MODIFY COLUMN category ENUM('{$enumList}') NOT NULL");
        } else {
            // SQLite: use Schema Blueprint to rebuild tables with new CHECK constraints.
            Schema::table('grocery_items', function (Blueprint $table) {
                $table->enum('category', $this->newCategories)->default('other')->change();
            });

            Schema::table('ingredients', function (Blueprint $table) {
                $table->enum('category', $this->newCategories)->default('other')->change();
            });

            Schema::table('common_item_templates', function (Blueprint $table) {
                $table->enum('category', $this->newCategories)->change();
            });

            Schema::table('user_item_templates', function (Blueprint $table) {
                $table->enum('category', $this->newCategories)->change();
            });
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
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        // Map new-only categories to 'other' (valid in both ENUM sets).
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

        foreach (['grocery_items', 'ingredients', 'common_item_templates', 'user_item_templates'] as $table) {
            DB::table($table)->whereIn('category', $newOnlyCategories)
                ->update(['category' => 'other']);
        }

        // Remap soups-and-canned-goods -> pantry BEFORE reverting the ENUM so MariaDB
        // does not reject rows with values absent from the old constraint.
        foreach (['grocery_items', 'ingredients', 'common_item_templates', 'user_item_templates'] as $table) {
            DB::table($table)->where('category', 'soups-and-canned-goods')
                ->update(['category' => 'pantry']);
        }

        // Now safe to revert the ENUM — no rows contain new-only values any more.
        if ($driver !== 'sqlite') {
            $enumList = implode("','", $this->oldCategories);

            DB::statement("ALTER TABLE grocery_items MODIFY COLUMN category ENUM('{$enumList}') NOT NULL DEFAULT 'other'");
            DB::statement("ALTER TABLE ingredients MODIFY COLUMN category ENUM('{$enumList}') NOT NULL DEFAULT 'other'");
            DB::statement("ALTER TABLE common_item_templates MODIFY COLUMN category ENUM('{$enumList}') NOT NULL");
            DB::statement("ALTER TABLE user_item_templates MODIFY COLUMN category ENUM('{$enumList}') NOT NULL");
        } else {
            Schema::table('grocery_items', function (Blueprint $table) {
                $table->enum('category', $this->oldCategories)->default('other')->change();
            });

            Schema::table('ingredients', function (Blueprint $table) {
                $table->enum('category', $this->oldCategories)->default('other')->change();
            });

            Schema::table('common_item_templates', function (Blueprint $table) {
                $table->enum('category', $this->oldCategories)->change();
            });

            Schema::table('user_item_templates', function (Blueprint $table) {
                $table->enum('category', $this->oldCategories)->change();
            });
        }

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
};
