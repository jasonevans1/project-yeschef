<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateUserItemTemplate implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public string $itemName,
        public ?string $category = null,
        public ?string $unit = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // TODO: Implement in Phase 4 (User Story 2)
        // Will use updateOrCreate to track user item templates
    }
}
