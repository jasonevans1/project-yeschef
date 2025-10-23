@props(['mealPlan', 'assignments'])

@php
    use App\Enums\MealType;

    // Generate date range
    $dates = [];
    $current = $mealPlan->start_date->copy();
    while ($current->lte($mealPlan->end_date)) {
        $dates[] = $current->copy();
        $current->addDay();
    }

    $mealTypes = MealType::cases();

    // Group assignments by date and meal type
    $groupedAssignments = $assignments->groupBy(function ($assignment) {
        return $assignment->date->format('Y-m-d') . '_' . $assignment->meal_type->value;
    });
@endphp

<div class="overflow-x-auto">
    <table class="w-full border-collapse">
        <thead>
            <tr class="border-b-2 border-gray-200">
                <th class="p-3 text-left font-semibold text-gray-700 w-32">Date</th>
                @foreach($mealTypes as $mealType)
                    <th class="p-3 text-center font-semibold text-gray-700">
                        {{ ucfirst($mealType->value) }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($dates as $date)
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="p-3 font-medium text-gray-700">
                        <div>{{ $date->format('D') }}</div>
                        <div class="text-sm text-gray-500">{{ $date->format('M d') }}</div>
                    </td>
                    @foreach($mealTypes as $mealType)
                        @php
                            $key = $date->format('Y-m-d') . '_' . $mealType->value;
                            $assignment = $groupedAssignments->get($key)?->first();
                        @endphp
                        <td
                            class="p-2 text-center align-top border-l border-gray-100"
                            data-date="{{ $date->format('Y-m-d') }}"
                            data-meal-type="{{ $mealType->value }}"
                        >
                            @if($assignment)
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-left relative group">
                                    <div class="font-medium text-sm text-blue-900 mb-1">
                                        {{ $assignment->recipe->name }}
                                    </div>
                                    @if($assignment->serving_multiplier != 1.00)
                                        <div class="text-xs text-blue-700">
                                            {{ $assignment->recipe->servings * $assignment->serving_multiplier }} servings
                                        </div>
                                    @endif
                                    @if($assignment->notes)
                                        <div class="text-xs text-gray-600 mt-1">
                                            {{ Str::limit($assignment->notes, 50) }}
                                        </div>
                                    @endif
                                    <div class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button
                                            wire:click="removeAssignment({{ $assignment->id }})"
                                            class="text-red-600 hover:text-red-700 p-1 rounded-full hover:bg-red-100"
                                            title="Remove"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @else
                                <button
                                    wire:click="openRecipeSelector('{{ $date->format('Y-m-d') }}', '{{ $mealType->value }}')"
                                    class="w-full h-full min-h-[60px] border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-400 hover:bg-blue-50 transition-colors flex items-center justify-center text-gray-400 hover:text-blue-600"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
