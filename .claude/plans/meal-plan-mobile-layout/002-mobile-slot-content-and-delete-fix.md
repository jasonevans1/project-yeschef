# Task 002: Recipe Cards, Note Cards, Add Buttons in Mobile Slots

**Status**: completed
**Depends on**: [001]
**Retry count**: 0

## Description
Fill each meal type section (scaffolded in Task 001) with full content: recipe assignment cards, note cards, an empty state, and the Add dropdown button. Also fix the desktop table's delete buttons to use `md:opacity-0 md:group-hover:opacity-100` so they are visible on mobile touch screens too (hover is not available on touch devices).

## Context
- Primary file: `resources/views/livewire/meal-plans/show.blade.php`
- Task 001 created the `@foreach($mealTypes as $mealType)` sections inside each day card with a placeholder label div. This task **replaces** the placeholder label div with the full content: a flex header row (label + Add button), recipe cards, note cards, and empty state.
- The `$assignments` and `$notes` variables in the view are keyed by `'Y-m-d_mealTypeValue'` -- use the same key format as the desktop table cells
- The `recipe` relationship is already eager-loaded via `render()` in Show.php (`mealAssignments.recipe`), so accessing `$assignment->recipe->servings` and `$assignment->recipe->name` will NOT cause N+1 queries.

**Key data access pattern** (use inside the `@foreach($mealTypes as $mealType)` loop):
```php
@php
    $key = $date->format('Y-m-d') . '_' . $mealType->value;
    $assignmentCollection = $assignments->get($key) ?? collect();
    $noteCollection = $notes->get($key) ?? collect();
    $hasItems = $assignmentCollection->isNotEmpty() || $noteCollection->isNotEmpty();
@endphp
```

**Wire:click handlers to use** (identical to desktop table):
- Open recipe selector: `wire:click="openRecipeSelector('{{ $date->format('Y-m-d') }}', '{{ $mealType->value }}')"`
- Open note form: `wire:click="openNoteForm('{{ $date->format('Y-m-d') }}', '{{ $mealType->value }}')"`
- Open recipe drawer: `wire:click="openRecipeDrawer({{ $assignment }})"`
- Open note drawer: `wire:click="openNoteDrawer({{ $note }})"`
- Remove assignment: `wire:click.stop="removeAssignment({{ $assignment->id }})"`
- Delete note: `wire:click.stop="deleteNote({{ $note->id }})"`

**Structure per meal type section (fills the `<!-- Content slot -->` placeholder from Task 001):**

```blade
{{-- Meal type header row --}}
<div class="flex items-center justify-between mb-2">
    <span class="text-sm font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wide">
        {{ ucfirst($mealType->value) }}
    </span>
    @can('update', $mealPlan)
        <flux:dropdown>
            <flux:button variant="ghost" icon="plus" size="sm" class="min-h-[44px]">
                Add
            </flux:button>
            <flux:menu>
                <flux:menu.item wire:click="openRecipeSelector(...)">Add Recipe</flux:menu.item>
                <flux:menu.item wire:click="openNoteForm(...)">Add Note</flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    @endcan
</div>

{{-- Items --}}
<div class="flex flex-col gap-2">
    {{-- Recipe cards --}}
    @foreach($assignmentCollection as $assignment)
        <div class="... relative group" wire:click="openRecipeDrawer({{ $assignment }})">
            <div class="font-medium text-sm ... pr-6">{{ $assignment->recipe->name }}</div>
            {{-- serving info --}}
            @can('update', $mealPlan)
                <div class="absolute top-1 right-1 opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity">
                    <flux:button wire:click.stop="removeAssignment({{ $assignment->id }})"
                                 wire:confirm="Remove this recipe?" size="xs" variant="ghost" icon="x-mark"
                                 class="text-red-600" />
                </div>
            @endcan
        </div>
    @endforeach

    {{-- Note cards (amber) --}}
    @foreach($noteCollection as $note)
        <div class="bg-amber-50 ... relative group" wire:click="openNoteDrawer({{ $note }})">
            {{-- icon + title + details --}}
            @can('update', $mealPlan)
                <div class="absolute top-1 right-1 opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity">
                    <flux:button wire:click.stop="deleteNote({{ $note->id }})"
                                 wire:confirm="Delete this note?" size="xs" variant="ghost" icon="x-mark"
                                 class="text-red-600" />
                </div>
            @endcan
        </div>
    @endforeach

    {{-- Empty state --}}
    @if(!$hasItems)
        <div class="text-xs text-gray-400 dark:text-zinc-500 italic py-1">Nothing planned</div>
    @endif
</div>
```

**Desktop delete button fix** (in the existing `<table>` section):
- Find the two existing delete button wrappers in the table cells (recipe delete and note delete)
- Change `opacity-0 group-hover:opacity-100` → `md:opacity-0 md:group-hover:opacity-100`
- This makes them always visible on mobile (below md), hover-revealed on desktop (md+)

**Mobile recipe card details:**
- White/zinc-800 background, border, rounded-lg, p-3, cursor-pointer
- `role="button"` and `tabindex="0"` for accessibility
- Name with `pr-6` to avoid overlap with delete button
- Serving multiplier badge (match desktop styling: gray pill)
- Notes excerpt: `Str::limit($assignment->notes, 50)` if notes exist

**Mobile note card details:**
- `bg-amber-50 dark:bg-amber-900/20`, `border-amber-200 dark:border-amber-700`
- Document icon (SVG, amber-600) + title (truncated 40 chars) + details (truncated 50 chars)
- `pr-6` on content div to avoid overlap with delete button

**Add button:**
- Use `flux:dropdown` with `flux:button` trigger (variant="ghost", icon="plus", size="sm")
- `class="min-h-[44px]"` on the button for touch target
- Show when user `@can('update', $mealPlan)` — read-only shared users see no add button
- Use plain "Add" label (desktop has a more complex empty/has-items toggle — simpler on mobile is fine)

## Requirements (Test Descriptions)
Add to: `tests/Feature/MealPlans/MobileLayoutTest.php`

- [ ] `it renders assigned recipe names in mobile day card slots` *(Note: recipe names also appear in desktop table HTML. Use `assertSee` which is sufficient since both desktop and mobile render. If you want stronger verification, assert the mobile-specific date format string like "Monday, Oct 14" appears alongside the recipe name.)*
- [ ] `it renders note titles in mobile day card slots`
- [ ] `it shows nothing planned text when a meal slot has no items` *(This text is mobile-only so `assertSee('Nothing planned')` is a reliable mobile-specific assertion)*
- [ ] `it includes openRecipeDrawer wire handler on mobile recipe cards`
- [ ] `it includes openNoteDrawer wire handler on mobile note cards`
- [ ] `it includes openRecipeSelector wire handler in mobile add buttons`
- [ ] `it includes openNoteForm wire handler in mobile add buttons`
- [ ] `it does not show add buttons for read-only shared users` *(Use ContentShareFactory: `ContentShare::factory()->forMealPlan($mealPlan)->create(['owner_id' => $owner->id, 'recipient_id' => $reader->id, 'recipient_email' => $reader->email])` -- default permission is `SharePermission::Read`)*
- [ ] `it renders serving multiplier on mobile recipe card when not 1x`
- [ ] `it does not render serving multiplier on mobile recipe card when 1x`

## Acceptance Criteria
- All requirements have passing tests
- Recipe cards in mobile slots: name, serving info, delete button (always-visible)
- Note cards in mobile slots: amber styling, title, details, delete button (always-visible)
- Empty slots show "Nothing planned"
- Add dropdown present per slot for authorized users
- Desktop delete buttons updated to `md:opacity-0 md:group-hover:opacity-100`
- No regressions in existing test suite
- Dark mode classes used throughout, matching existing file conventions
- `wire:key` on all `@foreach` loops in the mobile section (note: the existing desktop table loops do NOT have `wire:key` -- adding them to desktop is out of scope for this plan, but all new mobile loops must have them per code standards)

## Important Notes
- **Replace, don't append**: Task 001's placeholder label div inside `@foreach($mealTypes)` must be fully replaced with the header row + content structure. Do not nest inside the existing label div.
- **wire:key on all loops**: Add `wire:key` to every `@foreach` in the mobile section. Examples: `wire:key="mobile-recipe-{{ $assignment->id }}"`, `wire:key="mobile-note-{{ $note->id }}"`.
- **Serving multiplier conditional**: Copy the exact desktop pattern `@if($assignment->serving_multiplier != 1.00)` for the serving badge. The model casts `serving_multiplier` as `'decimal:2'` (string), and loose `!=` with float works via PHP type juggling.
- **ContentShareFactory for read-only test**: Use `ContentShare::factory()->forMealPlan($mealPlan)->create([...])`. The factory defaults to `SharePermission::Read`. The `forMealPlan()` state sets the shareable type/id. You need `use App\Models\ContentShare;` and `use App\Enums\SharePermission;` in the test file.
- **Desktop delete button fix**: There are exactly two delete button wrappers in the desktop table -- one for recipe assignments (around the `removeAssignment` call) and one for notes (around the `deleteNote` call). Both have `opacity-0 group-hover:opacity-100` that must change to `md:opacity-0 md:group-hover:opacity-100`.

## Implementation Notes
(Left blank -- filled in by implementer)
