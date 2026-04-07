# Devil's Advocate Review: meal-plan-mobile-layout

## Critical (Must fix before building)

### C1. Task 001 day-card skeleton includes the meal type label, but Task 002 replaces it with a flex row (label + Add button)
Task 001 scaffolds a standalone `<div>` for the meal type label. Task 002's context block shows a completely different structure: a `<div class="flex items-center justify-between mb-2">` wrapping both the label AND the `@can` add-button dropdown. The implementer of Task 002 will need to **replace** the label div from Task 001, not just fill a "content slot" below it. The plan describes this as "filling" a placeholder, but the label itself must be restructured.

**Affected tasks**: 001, 002
**Fix**: Task 001 should not include the meal type label at all -- just leave a `{{-- Meal type section: filled in Task 002 --}}` comment. Or, Task 002 must explicitly state it is replacing the label div, not appending below it.

### C2. Task 002 test "it does not show add buttons for read-only shared users" requires ContentShare setup but provides no guidance
The test requires creating a ContentShare with read permission for a meal plan, then verifying the mobile view omits add buttons. The existing test files in `tests/Feature/MealPlans/` never set up ContentShare objects. Without explicit guidance, the implementer may not know about the `ContentShareFactory` or its `forMealPlan()` and `withWritePermission()` states.

**Affected tasks**: 002
**Fix**: Add context about ContentShareFactory usage for the read-only test.

## Important (Should fix before building)

### I1. Missing `wire:key` on mobile `@foreach` loops
Code standards (`code-standards.md` line 49) require `wire:key` in all loops. Task 001 scaffolds two `@foreach` loops (`$dates` and `$mealTypes`) without `wire:key`. Task 002 adds `@foreach` loops for `$assignmentCollection` and `$noteCollection`. The acceptance criteria in Task 002 mention `wire:key` but the example code blocks in both tasks omit them. The desktop table's existing loops also lack `wire:key` (verified via grep), but the plan should at least ensure the new mobile loops have them.

**Affected tasks**: 001, 002
**Fix**: Add explicit `wire:key` attributes to all example code in both tasks.

### I2. Task 002 tests use `assertSee` for recipe names, but those names also appear in the desktop table HTML
The plan's _plan.md line 86 acknowledges this ("assertSee duplicate content... will pass (intentional)") but this means tests cannot distinguish whether content renders in the mobile section vs desktop section. If the mobile rendering is completely broken but the desktop table is present, all `assertSee`-based tests will still pass.

**Affected tasks**: 002
**Fix**: For at least one key test (e.g., recipe names in mobile slots), use `assertSeeHtml` with a distinctive attribute or test that `data-mobile-calendar` contains the expected content. Alternatively, use Livewire's `assertSeeHtmlInOrder` or check for a string that only appears in the mobile markup (e.g., the "Nothing planned" text in an empty slot, or the mobile-specific date format "Monday, Oct 14").

### I3. The mobile date format `'l, M j'` may conflict with existing `assertSee` tests
The existing `ViewMealPlanTest` on line 37-39 asserts `assertSee('2025-10-14')` etc. Those pass because the desktop `data-date` attribute renders `Y-m-d`. The mobile view adds a new date format like "Tuesday, Oct 14". This is fine and won't break anything, but Task 001's test "it renders a day header for each date in the meal plan on mobile" should assert the mobile-specific format (e.g., `'l, M j'`) not the `Y-m-d` format, to actually verify the mobile header renders.

**Affected tasks**: 001
**Fix**: Clarify that the day header test should assert the `l, M j` formatted date string.

### I4. Task 002 mobile recipe cards reference `$assignment->recipe->servings` but no eager loading guarantee
The `render()` method in `Show.php` loads `mealAssignments.recipe` which includes `servings`. This works. However, the mobile card code in Task 002's example block references `$assignment->recipe->servings * $assignment->serving_multiplier` -- this is fine for data access, but the plan should note that the `recipe` relationship is already eager-loaded so no N+1 risk exists.

**Affected tasks**: 002
**Fix**: Add a note confirming eager loading covers this. (Minor -- no code change needed, just a note.)

### I5. Task 001 references "approximate lines 68-70" for the existing structure but actual lines are 69-71
The outer wrapper `<div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">` is at line 69, and `<div class="overflow-x-auto">` is at line 70. The table starts at line 71. This is close enough but the plan should use the actual class strings as anchors rather than line numbers since line numbers shift.

**Affected tasks**: 001
**Fix**: Already uses class strings as anchors -- just remove the misleading line number reference.

### I6. Desktop delete button opacity fix is in Task 002, but Task 001 doesn't mention it
Task 002 modifies the desktop table section (changing `opacity-0 group-hover:opacity-100` to `md:opacity-0 md:group-hover:opacity-100`). This is a modification to the desktop table that Task 001 otherwise wraps and leaves "untouched." The dependency is fine (002 depends on 001), but Task 001 should note that the desktop table internals will be modified in Task 002.

**Affected tasks**: 001
**Fix**: Add a note to Task 001 that the desktop table content will be modified in Task 002 (delete button opacity fix), so the implementer of 001 doesn't add any "do not modify" caveats.

### I7. The `serving_multiplier` comparison `!= 1.00` uses loose comparison with a decimal-cast string
In the existing desktop view (line 113): `@if($assignment->serving_multiplier != 1.00)`. The model casts `serving_multiplier` as `'decimal:2'`, so it returns a string like `"1.00"`. The loose `!=` comparison with `1.00` (float) works due to PHP type juggling, and the mobile card should use the same pattern. Task 002's example code doesn't show this conditional, but the acceptance criteria mention "serving multiplier on mobile recipe card when not 1x." The implementer should copy the exact desktop pattern.

**Affected tasks**: 002
**Fix**: Add explicit note to copy the desktop `@if($assignment->serving_multiplier != 1.00)` conditional exactly.

## Minor (Nice to address)

### M1. No accessibility considerations for the mobile day-card structure
The desktop uses a `<table>` which has implicit accessibility semantics. The mobile cards use plain `<div>` elements. Adding `role="list"` on the day container and `role="listitem"` on each day card, or using an `<article>` element for each day, would improve screen reader experience. Not blocking but worth considering.

### M2. The 14-day scroll concern could be mitigated with `scroll-margin-top` or date anchors
The plan acknowledges 14-day plans create lots of scroll but deems accordions out of scope. A lightweight improvement would be adding `id="day-{{ $date->format('Y-m-d') }}"` anchors so users could link/jump to specific days. Not blocking.

### M3. No loading states specified for mobile add/delete actions
The desktop has `wire:loading` states on some actions (e.g., the delete button in the header). The mobile cards don't specify any loading indicators for `wire:click` actions. Users on slower connections may tap multiple times.

## Questions for the Team

1. **Should the mobile "Nothing planned" empty state show the Add dropdown for non-authenticated/read-only users?** Currently the desktop shows nothing at all for read-only users when a slot is empty (no dashed button). The mobile plan shows "Nothing planned" text for empty slots regardless of auth. Is that intentional? It seems fine but worth confirming.

2. **Should the mobile delete buttons have confirmation dialogs?** The desktop uses `wire:confirm` on delete buttons. The Task 002 example includes `wire:confirm` -- just confirming this is intentional for mobile where accidental taps are more likely.

3. **Is the `min-h-[44px]` arbitrary value acceptable?** Tailwind v4 supports `min-h-11` (44px = 2.75rem = 11 * 4px). Using the design system token `min-h-11` would be more consistent than the arbitrary value `min-h-[44px]`.
