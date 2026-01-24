<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            if (! Schema::hasColumn('recipes', 'source_url')) {
                $table->string('source_url', 2048)->nullable()->after('image_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            if (Schema::hasColumn('recipes', 'source_url')) {
                $table->dropColumn('source_url');
            }
        });
    }
};
