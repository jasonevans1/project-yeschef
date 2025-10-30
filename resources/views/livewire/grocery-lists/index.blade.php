<div>
    <flux:heading size="xl" class="mb-6">Grocery Lists</flux:heading>

    {{-- TODO: Uncomment when grocery-lists.create route is implemented (US6) --}}
    {{-- <div class="mb-6">
        <a href="{{ route('grocery-lists.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create Standalone List
        </a>
    </div> --}}

    @if($groceryLists->isEmpty())
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <flux:heading size="lg" class="mt-4">No grocery lists yet</flux:heading>
                <flux:text class="mt-2">Generate grocery lists from your meal plans</flux:text>
                <flux:button href="{{ route('meal-plans.index') }}" variant="primary" class="mt-4">
                    View Meal Plans
                </flux:button>
            </div>
        </div>
    @else
        {{-- Meal Plan Linked Lists --}}
        @if($mealPlanLists->isNotEmpty())
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <flux:heading size="lg" class="mb-4">Meal Plan Lists</flux:heading>
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

        {{-- Standalone Lists --}}
        @if($standaloneLists->isNotEmpty())
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <flux:heading size="lg" class="mb-4">Standalone Lists</flux:heading>
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

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $groceryLists->links() }}
        </div>
    @endif
</div>
