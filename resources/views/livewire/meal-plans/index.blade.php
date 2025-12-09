<div>
    <flux:heading size="xl" class="mb-6">Meal Plans</flux:heading>

    <div class="mb-6">
        <a href="{{ route('meal-plans.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create New Meal Plan
        </a>
    </div>

    @if($mealPlans->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <flux:heading size="lg" class="mt-4">No meal plans yet</flux:heading>
                <flux:text class="mt-2">Get started by creating your first meal plan</flux:text>
                <a href="{{ route('meal-plans.create') }}" class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                    Create Meal Plan
                </a>
            </div>
        </div>
    @else
        {{-- Active Plans --}}
        @if($activePlans->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <flux:heading size="lg" class="mb-4">Active Plans</flux:heading>
                <div class="space-y-4">
                    @foreach($activePlans as $plan)
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <div class="flex-1">
                                <flux:heading size="md">
                                    <a href="{{ route('meal-plans.show', $plan) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                        {{ $plan->name }}
                                    </a>
                                </flux:heading>
                                <flux:text class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $plan->start_date->format('M d, Y') }} - {{ $plan->end_date->format('M d, Y') }}
                                    · {{ $plan->duration_days }} {{ Str::plural('day', $plan->duration_days) }}
                                    · {{ $plan->meal_assignments_count }} {{ Str::plural('meal', $plan->meal_assignments_count) }} planned
                                </flux:text>
                            </div>
                            <div class="flex items-center gap-2">
                                <flux:badge variant="primary">Active</flux:badge>
                                <flux:button href="{{ route('meal-plans.show', $plan) }}" variant="ghost" size="sm">
                                    View
                                </flux:button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Future Plans --}}
        @if($futurePlans->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <flux:heading size="lg" class="mb-4">Upcoming Plans</flux:heading>
                <div class="space-y-4">
                    @foreach($futurePlans as $plan)
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <div class="flex-1">
                                <flux:heading size="md">
                                    <a href="{{ route('meal-plans.show', $plan) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                        {{ $plan->name }}
                                    </a>
                                </flux:heading>
                                <flux:text class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $plan->start_date->format('M d, Y') }} - {{ $plan->end_date->format('M d, Y') }}
                                    · {{ $plan->duration_days }} {{ Str::plural('day', $plan->duration_days) }}
                                    · {{ $plan->meal_assignments_count }} {{ Str::plural('meal', $plan->meal_assignments_count) }} planned
                                </flux:text>
                            </div>
                            <div class="flex items-center gap-2">
                                <flux:badge variant="ghost">Upcoming</flux:badge>
                                <flux:button href="{{ route('meal-plans.show', $plan) }}" variant="ghost" size="sm">
                                    View
                                </flux:button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Past Plans --}}
        @if($pastPlans->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <flux:heading size="lg" class="mb-4">Past Plans</flux:heading>
                <div class="space-y-4">
                    @foreach($pastPlans as $plan)
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition opacity-75">
                            <div class="flex-1">
                                <flux:heading size="md">
                                    <a href="{{ route('meal-plans.show', $plan) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                        {{ $plan->name }}
                                    </a>
                                </flux:heading>
                                <flux:text class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $plan->start_date->format('M d, Y') }} - {{ $plan->end_date->format('M d, Y') }}
                                    · {{ $plan->duration_days }} {{ Str::plural('day', $plan->duration_days) }}
                                    · {{ $plan->meal_assignments_count }} {{ Str::plural('meal', $plan->meal_assignments_count) }} planned
                                </flux:text>
                            </div>
                            <div class="flex items-center gap-2">
                                <flux:badge variant="ghost">Past</flux:badge>
                                <flux:button href="{{ route('meal-plans.show', $plan) }}" variant="ghost" size="sm">
                                    View
                                </flux:button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $mealPlans->links() }}
        </div>
    @endif
</div>
