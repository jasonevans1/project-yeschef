@props(['recipe'])

<a href="{{ route('recipes.show', $recipe) }}" class="block group hover:shadow-lg transition-shadow duration-200 rounded-lg overflow-hidden bg-white border border-gray-200">
    {{-- Recipe Image --}}
    <div class="h-16 bg-gray-100 overflow-hidden">
        @if ($recipe->image_url)
            <img
                src="{{ $recipe->image_url }}"
                alt="{{ $recipe->name }}"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
            >
        @else
            <div class="w-full h-full flex items-center justify-center text-gray-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        @endif
    </div>

    {{-- Recipe Content --}}
    <div class="p-4">
        {{-- Recipe Name --}}
        <h3 class="font-semibold text-lg mb-2 group-hover:text-blue-600 transition-colors line-clamp-2">
            {{ $recipe->name }}
        </h3>

        {{-- Recipe Description --}}
        @if ($recipe->description)
            <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                {{ $recipe->description }}
            </p>
        @endif

        {{-- Recipe Meta --}}
        <div class="flex flex-wrap gap-2 mb-3">
            @if ($recipe->meal_type)
                <flux:badge size="sm">{{ ucfirst($recipe->meal_type->value) }}</flux:badge>
            @endif

            @if ($recipe->user_id === null)
                <flux:badge size="sm" color="blue" icon="star">System</flux:badge>
            @elseif ($recipe->user_id === auth()->id())
                <flux:badge size="sm" color="green" icon="user">Mine</flux:badge>
            @endif
        </div>

        {{-- Dietary Tags --}}
        @if ($recipe->dietary_tags && count($recipe->dietary_tags) > 0)
            <div class="flex flex-wrap gap-1">
                @foreach (array_slice($recipe->dietary_tags, 0, 3) as $tag)
                    <span class="text-xs px-2 py-1 bg-green-50 text-green-700 rounded">
                        {{ ucfirst($tag) }}
                    </span>
                @endforeach
                @if (count($recipe->dietary_tags) > 3)
                    <span class="text-xs px-2 py-1 bg-gray-50 text-gray-600 rounded">
                        +{{ count($recipe->dietary_tags) - 3 }} more
                    </span>
                @endif
            </div>
        @endif

        {{-- Recipe Stats --}}
        <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-sm text-gray-500">
            <div class="flex items-center gap-3">
                @if ($recipe->prep_time || $recipe->cook_time)
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ ($recipe->prep_time ?? 0) + ($recipe->cook_time ?? 0) }}m
                    </span>
                @endif

                <span class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    {{ $recipe->servings }}
                </span>
            </div>

            @if ($recipe->difficulty)
                <span class="text-xs px-2 py-1 bg-gray-100 rounded">
                    {{ ucfirst($recipe->difficulty) }}
                </span>
            @endif
        </div>
    </div>
</a>
