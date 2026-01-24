<?php

use App\Enums\MeasurementUnit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't use ENUM types - it stores as text, so no modification needed
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Get all enum values from the MeasurementUnit enum
        $enumValues = array_map(fn ($case) => "'{$case->value}'", MeasurementUnit::cases());
        $enumString = implode(',', $enumValues);

        DB::statement("ALTER TABLE grocery_items MODIFY COLUMN unit ENUM({$enumString}) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite doesn't use ENUM types - it stores as text, so no modification needed
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Restore original enum values (without container types)
        $originalValues = "'tsp','tbsp','fl_oz','cup','pint','quart','gallon','ml','liter','oz','lb','gram','kg','whole','clove','slice','piece','pinch','dash','to_taste'";

        DB::statement("ALTER TABLE grocery_items MODIFY COLUMN unit ENUM({$originalValues}) NULL");
    }
};
