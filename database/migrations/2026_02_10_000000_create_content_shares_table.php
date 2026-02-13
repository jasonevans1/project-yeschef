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
        Schema::create('content_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_email');
            $table->string('shareable_type');
            $table->unsignedBigInteger('shareable_id')->nullable();
            $table->string('permission', 10)->default('read');
            $table->boolean('share_all')->default(false);
            $table->timestamps();

            $table->index('owner_id');
            $table->index('recipient_id');
            $table->index('recipient_email');
            $table->index(['shareable_type', 'shareable_id']);
            $table->unique(['owner_id', 'recipient_email', 'shareable_type', 'shareable_id'], 'content_shares_unique_share');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_shares');
    }
};
