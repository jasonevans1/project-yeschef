<?php

namespace App\Jobs;

use App\Models\UserItemTemplate;
use Illuminate\Support\Facades\Cache;

class UpdateUserItemTemplate
{
    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public string $itemName,
        public ?string $category = null,
        public ?string $unit = null,
        public ?float $defaultQuantity = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Find existing template or create new one
        $template = UserItemTemplate::where('user_id', $this->userId)
            ->where('name', $this->itemName)
            ->first();

        if ($template) {
            // Update existing template: increment usage_count
            $template->update([
                'category' => $this->category,
                'unit' => $this->unit,
                'default_quantity' => $this->defaultQuantity,
                'usage_count' => $template->usage_count + 1,
                'last_used_at' => now(),
            ]);
        } else {
            // Create new template: start with usage_count = 1
            UserItemTemplate::create([
                'user_id' => $this->userId,
                'name' => $this->itemName,
                'category' => $this->category,
                'unit' => $this->unit,
                'default_quantity' => $this->defaultQuantity,
                'usage_count' => 1,
                'last_used_at' => now(),
            ]);
        }

        // Invalidate user's autocomplete cache
        Cache::forget("suggestions.{$this->userId}");
    }
}
