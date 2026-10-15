<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\ItemAutoCompleteService;
use Database\Seeders\CommonItemTemplateSeeder;

// FR-012: Common defaults are seeded globally (CommonItemTemplateSeeder), not per-registration —
// a brand-new user with zero UserItemTemplate rows already sees them via queryCommonTemplates().
test('it surfaces seeded common item template defaults to a newly registered user with no personal templates', function () {
    $this->seed(CommonItemTemplateSeeder::class);

    $user = User::factory()->create();
    $service = new ItemAutoCompleteService;

    $results = $service->query($user->id, 'tomat');

    expect($results->pluck('name')->toArray())->toContain('tomato');
});
