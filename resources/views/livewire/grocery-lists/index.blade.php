<div>
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <flux:heading size="xl">Grocery Lists</flux:heading>

        <flux:button
            href="{{ route('grocery-lists.create') }}"
            variant="primary"
            class="w-full sm:w-auto"
        >
            <flux:icon.plus class="size-4 mr-1" />
            Create Standalone List
        </flux:button>
    </div>

    @if($groceryLists->isEmpty())
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <flux:heading size="lg" class="mt-4">No grocery lists yet</flux:heading>
                <flux:text class="mt-2">Create a standalone list or generate one from your meal plans</flux:text>
                <div class="flex items-center justify-center gap-3 mt-4">
                    <flux:button href="{{ route('grocery-lists.create') }}" variant="primary">
                        Create Standalone List
                    </flux:button>
                    <flux:button href="{{ route('meal-plans.index') }}" variant="ghost">
                        View Meal Plans
                    </flux:button>
                </div>
            </div>
        </div>
    @else
        {{-- All Grocery Lists --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6 mb-6">
            <div class="space-y-4">
                @foreach($groceryLists as $list)
                    <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-zinc-700 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800 transition">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <flux:heading size="md">
                                    <a href="{{ route('grocery-lists.show', $list) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                        {{ $list->name }}
                                    </a>
                                </flux:heading>
                                @if($list->meal_plan_id)
                                    <flux:badge class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        <flux:icon.calendar class="size-3 mr-1" />
                                        Meal Plan
                                    </flux:badge>
                                @else
                                    <flux:badge class="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        <flux:icon.clipboard-document-list class="size-3 mr-1" />
                                        Standalone
                                    </flux:badge>
                                @endif
                                @if($list->user_id !== auth()->id() && $list->user)
                                    <flux:badge size="sm" color="purple" icon="share">Shared by {{ $list->user->name }}</flux:badge>
                                @endif
                            </div>
                            <flux:text class="text-sm text-gray-600 dark:text-zinc-400">
                                @if($list->meal_plan_id)
                                    Generated {{ $list->generated_at->diffForHumans() }}
                                    @if($list->mealPlan)
                                        · From <a href="{{ route('meal-plans.show', $list->mealPlan) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $list->mealPlan->name }}</a>
                                    @endif
                                @else
                                    Created {{ $list->created_at->diffForHumans() }}
                                @endif
                                @if($list->total_items > 0)
                                    · {{ $list->total_items }} {{ Str::plural('item', $list->total_items) }}
                                    · {{ $list->completion_percentage }}% complete
                                @endif
                            </flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($list->completion_percentage === 100)
                                <flux:badge variant="success">Complete</flux:badge>
                            @elseif($list->completion_percentage > 0)
                                <flux:badge variant="primary">{{ round($list->completion_percentage) }}%</flux:badge>
                            @else
                                <flux:badge variant="ghost">{{ $list->meal_plan_id ? 'Not Started' : 'Empty' }}</flux:badge>
                            @endif
                            <flux:button href="{{ route('grocery-lists.show', $list) }}" variant="ghost" size="sm">
                                View
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $groceryLists->links() }}
        </div>
    @endif
</div>
