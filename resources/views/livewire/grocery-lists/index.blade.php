<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Grocery Lists</flux:heading>

        <flux:button
            href="{{ route('grocery-lists.create') }}"
            variant="primary"
        >
            <flux:icon.plus class="size-4 mr-1" />
            Create Standalone List
        </flux:button>
    </div>

    @if($groceryLists->isEmpty())
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        {{-- Standalone Lists Section --}}
        @if($standaloneLists->isNotEmpty())
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <flux:icon.clipboard-document-list class="size-6 text-blue-600" />
                    <div>
                        <flux:heading size="lg">Standalone Lists</flux:heading>
                        <flux:text class="text-sm text-gray-600">Shopping lists not linked to meal plans</flux:text>
                    </div>
                </div>
                <div class="space-y-4">
                    @foreach($standaloneLists as $list)
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <div class="flex-1">
                                <flux:heading size="md">
                                    <a href="{{ route('grocery-lists.show', $list) }}" class="hover:text-blue-600">
                                        {{ $list->name }}
                                    </a>
                                </flux:heading>
                                <flux:text class="text-sm text-gray-600">
                                    Created {{ $list->created_at->diffForHumans() }}
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
                                    <flux:badge variant="ghost">Empty</flux:badge>
                                @endif
                                <flux:button href="{{ route('grocery-lists.show', $list) }}" variant="ghost" size="sm">
                                    View
                                </flux:button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Meal Plan Linked Lists --}}
        @if($mealPlanLists->isNotEmpty())
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex items-center gap-3 mb-4">
                    <flux:icon.calendar class="size-6 text-green-600" />
                    <div>
                        <flux:heading size="lg">Meal Plan Lists</flux:heading>
                        <flux:text class="text-sm text-gray-600">Lists generated from your meal plans</flux:text>
                    </div>
                </div>
                <div class="space-y-4">
                    @foreach($mealPlanLists as $list)
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <div class="flex-1">
                                <flux:heading size="md">
                                    <a href="{{ route('grocery-lists.show', $list) }}" class="hover:text-blue-600">
                                        {{ $list->name }}
                                    </a>
                                </flux:heading>
                                <flux:text class="text-sm text-gray-600">
                                    Generated {{ $list->generated_at->diffForHumans() }}
                                    @if($list->mealPlan)
                                        · From <a href="{{ route('meal-plans.show', $list->mealPlan) }}" class="text-blue-600 hover:underline">{{ $list->mealPlan->name }}</a>
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
                                    <flux:badge variant="ghost">Not Started</flux:badge>
                                @endif
                                <flux:button href="{{ route('grocery-lists.show', $list) }}" variant="ghost" size="sm">
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
            {{ $groceryLists->links() }}
        </div>
    @endif
</div>
