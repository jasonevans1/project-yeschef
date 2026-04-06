# Task 001: Responsive Wrapper Split + Mobile Day-Card Skeleton

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Split the existing calendar section into a desktop-only table view and a mobile-only day-card view. The desktop table is wrapped in `hidden md:block` so it disappears on mobile. A new `block md:hidden` container renders one card per day, each with a header and a scaffold for meal type sections. No recipe or note content yet — that comes in Task 002.

## Context
- Primary file: `resources/views/livewire/meal-plans/show.blade.php` (~670 lines)
- The existing outer wrapper div around the table has `bg-white dark:bg-zinc-900 rounded-lg shadow p-6`
- The `p-6` padding must move off the outer wrapper and onto each child (desktop gets `p-6`, mobile day divs get `p-4`) to avoid double-padding
- Add `data-mobile-calendar` attribute to the mobile container div — used by tests to confirm the mobile markup is present

**Existing structure to modify (find by class string, not line number):**
```blade
<div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
    <div class="overflow-x-auto">
        <table ...>
```

**Target structure after this task:**
```blade
<div class="bg-white dark:bg-zinc-900 rounded-lg shadow">

    {{-- Desktop: existing table, unchanged content --}}
    <div class="hidden md:block overflow-x-auto p-6">
        <table ...> (all existing table content, untouched) </table>
    </div>

    {{-- Mobile: day-card stack --}}
    <div class="block md:hidden divide-y divide-gray-100 dark:divide-zinc-700"
         data-mobile-calendar>
        @foreach($dates as $date)
            <div wire:key="mobile-day-{{ $date->format('Y-m-d') }}" class="p-4">
                {{-- Day header --}}
                <div class="font-semibold text-gray-900 dark:text-white mb-3">
                    {{ $date->format('l, M j') }}
                </div>
                {{-- Meal type sections: filled in Task 002 --}}
                @foreach($mealTypes as $mealType)
                    <div wire:key="mobile-{{ $date->format('Y-m-d') }}-{{ $mealType->value }}"
                         class="mb-3 last:mb-0">
                        {{-- Meal type header + content: fully built in Task 002 --}}
                        {{-- Task 002 will add: label row with Add button, recipe cards, note cards, empty state --}}
                        <div class="text-sm font-medium text-gray-500 dark:text-zinc-400
                                    uppercase tracking-wide mb-2">
                            {{ ucfirst($mealType->value) }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

</div>
```

## Requirements (Test Descriptions)
Tests go in: `tests/Feature/MealPlans/MobileLayoutTest.php` (new file)

- [ ] `it renders the mobile calendar container in the page HTML`
- [ ] `it renders a day header for each date in the meal plan on mobile` *(assert the `l, M j` formatted string, e.g., "Tuesday, Oct 14", not the Y-m-d format)*
- [ ] `it renders all four meal type section labels on mobile`
- [ ] `it does not show mobile container above md breakpoint` *(assertSee `data-mobile-calendar` -- CSS-only, trust Tailwind)*
- [ ] `it preserves the desktop table in the rendered output`

## Acceptance Criteria
- The `data-mobile-calendar` attribute is present in the rendered HTML
- All days in the plan produce a header with the day name and date
- All four meal types (Breakfast, Lunch, Dinner, Snack) appear as section labels per day
- The existing `<table>` element remains in the HTML (desktop layout preserved)
- All pre-existing meal plan tests still pass
- Dark mode classes match existing conventions in the file

## Important Notes
- The desktop table content is wrapped in `hidden md:block` but NOT frozen. Task 002 will modify the desktop table's delete button opacity classes. Do not add comments stating the desktop table is untouched.
- All `@foreach` loops in the mobile section must include `wire:key` attributes per project code standards.
- The day header test should assert the mobile-specific date format (`l, M j` e.g., "Tuesday, Oct 14"), NOT the `Y-m-d` format which comes from desktop `data-date` attributes.

## Implementation Notes
(Left blank -- filled in by implementer)
