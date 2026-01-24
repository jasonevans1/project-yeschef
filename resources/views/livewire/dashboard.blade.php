<div class="p-6 space-y-6">
    {{-- Welcome Section --}}
    <div>
        <flux:heading size="xl">Welcome back, {{ auth()->user()->name }}!</flux:heading>
        <flux:subheading>Here's what's happening with your meal planning</flux:subheading>
    </div>

    {{-- Quick Actions --}}
    <div class="p-6 bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
        <flux:heading size="lg" class="mb-4">Quick Actions</flux:heading>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <flux:button variant="primary" :href="route('meal-plans.create')" wire:navigate icon="calendar">
                Create Meal Plan
            </flux:button>
            <flux:button variant="primary" :href="route('recipes.index')" wire:navigate icon="book-open">
                Browse Recipes
            </flux:button>
            <flux:button variant="primary" :href="route('grocery-lists.create')" wire:navigate icon="shopping-cart">
                Create Shopping List
            </flux:button>
        </div>
    </div>

    {{-- Upcoming Meal Plans --}}
    <div class="p-6 bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
        <flux:heading size="lg" class="mb-4">Upcoming Meal Plans (Next 7 Days)</flux:heading>

        @if ($this->upcomingMealPlans->isEmpty())
            <div class="text-center py-12">
                <flux:icon.calendar class="mx-auto h-12 w-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">No upcoming meal plans</flux:heading>
                <flux:subheading class="mt-2">Create a meal plan to get started with organized meal planning</flux:subheading>
                <flux:button variant="primary" :href="route('meal-plans.create')" wire:navigate class="mt-4">
                    Create Your First Meal Plan
                </flux:button>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($this->upcomingMealPlans as $mealPlan)
                    <a
                        href="{{ route('meal-plans.show', $mealPlan) }}"
                        wire:navigate
                        class="block p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:border-zinc-300 dark:hover:border-zinc-600 transition"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <flux:heading size="md">{{ $mealPlan->name }}</flux:heading>
                                    @if ($mealPlan->is_active)
                                        <flux:badge color="lime" size="sm">Active</flux:badge>
                                    @elseif ($mealPlan->is_future)
                                        <flux:badge color="sky" size="sm">Upcoming</flux:badge>
                                    @endif
                                </div>
                                <flux:subheading class="mt-1">
                                    {{ $mealPlan->start_date->format('M j') }} - {{ $mealPlan->end_date->format('M j, Y') }}
                                    ({{ $mealPlan->duration_days }} {{ Str::plural('day', $mealPlan->duration_days) }})
                                </flux:subheading>
                                @if ($mealPlan->description)
                                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $mealPlan->description }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $mealPlan->assignment_count }} {{ Str::plural('meal', $mealPlan->assignment_count) }}
                                </p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-4 text-center">
                <flux:button variant="ghost" :href="route('meal-plans.index')" wire:navigate>
                    View All Meal Plans
                </flux:button>
            </div>
        @endif
    </div>

    {{-- Recent Grocery Lists --}}
    <div class="p-6 bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
        <flux:heading size="lg" class="mb-4">Recent Grocery Lists</flux:heading>

        @if ($this->recentGroceryLists->isEmpty())
            <div class="text-center py-12">
                <flux:icon.shopping-cart class="mx-auto h-12 w-12 text-zinc-400" />
                <flux:heading size="lg" class="mt-4">No grocery lists yet</flux:heading>
                <flux:subheading class="mt-2">Create a grocery list to start shopping efficiently</flux:subheading>
                <flux:button variant="primary" :href="route('grocery-lists.create')" wire:navigate class="mt-4">
                    Create Your First List
                </flux:button>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($this->recentGroceryLists as $groceryList)
                    <a
                        href="{{ route('grocery-lists.show', $groceryList) }}"
                        wire:navigate
                        class="block p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:border-zinc-300 dark:hover:border-zinc-600 transition"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <flux:heading size="md">{{ $groceryList->name }}</flux:heading>
                                    @if ($groceryList->is_meal_plan_linked)
                                        <flux:badge color="purple" size="sm">From Meal Plan</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">Standalone</flux:badge>
                                    @endif
                                </div>
                                <flux:subheading class="mt-1">
                                    Updated {{ $groceryList->updated_at->diffForHumans() }}
                                </flux:subheading>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $groceryList->completed_items }}/{{ $groceryList->total_items }} items
                                </p>
                                @if ($groceryList->total_items > 0)
                                    <div class="mt-1 w-24">
                                        <div class="h-2 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                                            <div
                                                class="h-full bg-lime-500"
                                                style="width: {{ $groceryList->completion_percentage }}%"
                                            ></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-4 text-center">
                <flux:button variant="ghost" :href="route('grocery-lists.index')" wire:navigate>
                    View All Grocery Lists
                </flux:button>
            </div>
        @endif
    </div>
</div>
