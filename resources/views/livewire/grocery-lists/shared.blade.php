<div>
    {{-- Shared View Header --}}
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
            </svg>
            <div class="flex-1">
                <flux:text class="text-blue-900 font-medium">
                    Shared by {{ $groceryList->user->name }}
                </flux:text>
                @if($groceryList->share_expires_at)
                    <flux:text class="text-blue-700 text-sm mt-1">
                        This link expires {{ $groceryList->share_expires_at->diffForHumans() }}
                        ({{ $groceryList->share_expires_at->format('F j, Y \a\t g:i A') }})
                    </flux:text>
                @endif
            </div>
        </div>
    </div>

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <flux:heading size="xl" level="1" class="mb-2">{{ $groceryList->name }}</flux:heading>

                {{-- Source Information --}}
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    @if($groceryList->is_meal_plan_linked && $groceryList->mealPlan)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>From <span class="font-medium">{{ $groceryList->mealPlan->name }}</span></span>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span class="font-medium">Standalone List</span>
                    @endif

                    <span class="text-gray-400">•</span>

                    @if($groceryList->generated_at)
                        <span>Generated {{ $groceryList->generated_at->diffForHumans() }}</span>
                    @else
                        <span>Created {{ $groceryList->created_at->diffForHumans() }}</span>
                    @endif

                    @if($groceryList->regenerated_at)
                        <span class="text-gray-400">•</span>
                        <span>Last updated {{ $groceryList->regenerated_at->diffForHumans() }}</span>
                    @endif
                </div>

                {{-- Completion Progress Bar --}}
                @if($groceryList->total_items > 0)
                    <div class="mt-4">
                        <div class="flex items-center justify-between mb-2">
                            <flux:text class="text-sm font-medium text-gray-700">
                                {{ $groceryList->completed_items }} of {{ $groceryList->total_items }} items completed
                            </flux:text>
                            <flux:text class="text-sm font-semibold text-gray-900">
                                {{ round($groceryList->completion_percentage) }}%
                            </flux:text>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
                                 style="width: {{ $groceryList->completion_percentage }}%">
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Back Button (No Action Buttons in Read-Only Mode) --}}
            <div class="flex items-center gap-2 ml-4">
                <a href="{{ route('grocery-lists.index') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Lists
                </a>
            </div>
        </div>
    </div>

    {{-- Empty State --}}
    @if($groceryList->total_items === 0)
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <flux:heading size="lg" class="mt-4">No items in this list</flux:heading>
                <flux:text class="mt-2 text-gray-600">
                    This grocery list doesn't have any items yet.
                </flux:text>
            </div>
        </div>
    @else
        {{-- Grocery Items by Category (Read-Only) --}}
        <div class="space-y-6">
            @foreach($itemsByCategory as $categoryValue => $items)
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    {{-- Category Header --}}
                    <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <flux:heading size="lg">
                                {{ ucfirst(str_replace('_', ' ', $categoryValue)) }}
                            </flux:heading>
                            <flux:badge variant="outline">
                                {{ $items->count() }} {{ $items->count() === 1 ? 'item' : 'items' }}
                            </flux:badge>
                        </div>
                    </div>

                    {{-- Category Items --}}
                    <div class="divide-y divide-gray-100">
                        @foreach($items as $item)
                            <div class="px-6 py-4 flex items-center gap-4 hover:bg-gray-50 transition-colors">
                                {{-- Checkbox (Display Only - Not Interactive) --}}
                                <div class="flex-shrink-0">
                                    @if($item->purchased)
                                        <div class="w-5 h-5 rounded border-2 border-green-500 bg-green-500 flex items-center justify-center">
                                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-5 h-5 rounded border-2 border-gray-300 bg-white"></div>
                                    @endif
                                </div>

                                {{-- Item Details --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-baseline gap-2">
                                        <span class="font-medium text-gray-900 {{ $item->purchased ? 'line-through text-gray-500' : '' }}">
                                            {{ $item->name }}
                                        </span>

                                        @if($item->quantity || $item->unit)
                                            <span class="text-sm text-gray-600 {{ $item->purchased ? 'line-through' : '' }}">
                                                @if($item->quantity)
                                                    {{ $item->quantity }}{{ $item->unit ? ' ' . $item->unit->value : '' }}
                                                @elseif($item->unit)
                                                    {{ $item->unit->value }}
                                                @endif
                                            </span>
                                        @endif
                                    </div>

                                    @if($item->notes)
                                        <p class="text-sm text-gray-600 italic mt-1 {{ $item->purchased ? 'line-through' : '' }}">
                                            {{ $item->notes }}
                                        </p>
                                    @endif

                                    {{-- Source Badge --}}
                                    @if($item->is_manual)
                                        <div class="mt-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                                Manual
                                            </span>
                                        </div>
                                    @elseif($item->is_edited)
                                        <div class="mt-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                Edited
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                {{-- No Action Buttons in Read-Only Mode --}}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Read-Only Notice --}}
    <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <flux:text class="text-yellow-800 text-sm">
                This is a read-only view. You cannot edit or modify items in this shared grocery list.
            </flux:text>
        </div>
    </div>
</div>
