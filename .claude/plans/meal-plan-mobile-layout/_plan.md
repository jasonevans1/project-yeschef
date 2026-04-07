# Plan: Meal Plan Mobile Layout

## Created
2026-04-05

## Status
completed

## Objective
Restructure the meal plan show page so that on mobile screens (< md / 768px) each day renders as a full-width card with meal types stacked vertically, while tablet and desktop screens continue using the existing table layout unchanged.

## Scope

### In Scope
- Mobile day-card layout (< md breakpoint) showing each day as a card with Breakfast/Lunch/Dinner/Snack sections
- Add buttons with correct tap targets (min-h-[44px]) per meal slot
- Recipe and note cards inside mobile slots with always-visible delete buttons
- Fix desktop delete buttons to use `md:opacity-0 md:group-hover:opacity-100` so they are also always visible on mobile touch screens (same pattern)
- Empty state ("Nothing planned") per slot
- All existing wire:click handlers reused verbatim — no new component logic
- Dark mode support throughout

### Out of Scope
- Any changes to the Livewire component (Show.php) — purely view changes
- Changes to modals, drawers, or share modal — they already work on mobile
- Accordions or collapsible days for long meal plans
- Any changes to the desktop table layout
- Changes to the header section (already passable on mobile)

## Success Criteria
- [ ] Mobile day-card layout renders on screens < md
- [ ] Desktop table is hidden on mobile (`hidden md:block`)
- [ ] All four meal types (Breakfast, Lunch, Dinner, Snack) appear per day
- [ ] Assigned recipes and notes show in their respective mobile slots
- [ ] Delete buttons visible on mobile without hover
- [ ] Add buttons have adequate touch targets
- [ ] All existing tests still pass
- [ ] New mobile layout tests pass

## Task Overview
| Task | Description | Depends On | Status |
|------|-------------|------------|--------|
| 001 | Responsive wrapper split + mobile day-card skeleton | — | completed |
| 002 | Recipe cards, note cards, add buttons in mobile slots | 001 | completed |

## Architecture Notes

### Breakpoint Strategy
- `hidden md:block` on the existing `overflow-x-auto` table wrapper → desktop only
- `block md:hidden` on the new mobile container → mobile only
- Padding moves from the outer wrapper onto each child to avoid double-padding (p-6 on desktop div, p-4 on mobile day divs)

### Data Access in Mobile View
The `render()` method already passes `$dates`, `$assignments`, `$notes`, `$mealTypes`. The mobile view uses:
```php
$key = $date->format('Y-m-d') . '_' . $mealType->value;
$assignmentCollection = $assignments->get($key) ?? collect();
$noteCollection = $notes->get($key) ?? collect();
```
This is the same key format used in the desktop table cells.

### Delete Button Fix (both desktop and mobile)
Current desktop: `opacity-0 group-hover:opacity-100`
Fixed desktop + always-visible mobile: `md:opacity-0 md:group-hover:opacity-100`
Mobile cards: use `opacity-100` baseline (no class override needed since md: classes don't apply)

### Task 001 → 002 Handoff
Task 001 scaffolds the mobile container with day cards and a placeholder label div per meal type. Task 002 **replaces** that placeholder label div with the full content (header row with Add button, recipe cards, note cards, empty state). Task 002 also modifies the desktop table's delete button opacity classes. The desktop table content is NOT frozen after Task 001.

### Wire Attribute Reuse
All `wire:click` calls are identical to the desktop table:
- `openRecipeSelector('{{ $date->format('Y-m-d') }}', '{{ $mealType->value }}')`
- `openNoteForm('{{ $date->format('Y-m-d') }}', '{{ $mealType->value }}')`
- `openRecipeDrawer({{ $assignment }})`
- `openNoteDrawer({{ $note }})`
- `removeAssignment({{ $assignment->id }})`
- `deleteNote({{ $note->id }})`

### MealType Enum
Has 4 cases: BREAKFAST, LUNCH, DINNER, SNACK — mobile view iterates all four via `$mealTypes`.

## Dependency Graph
```
001 ──► 002
```

## Risks & Mitigations
- **Long meal plans**: 14-day plan = 14 cards × 4 sections — lots of scroll, but no accordion scope in this plan
- **assertSee duplicate content**: Recipe names appear in both desktop and mobile HTML -- tests use `assertSee` which will pass. For mobile-specific assertions, use "Nothing planned" (mobile-only) or the mobile date format (`l, M j`) as distinguishing markers.
- **Existing tests**: Desktop table markup is preserved in Task 001. Task 002 modifies desktop delete button opacity classes but does not change structure -- zero test regressions expected.
- **ContentShareFactory**: The read-only user test in Task 002 requires `ContentShare::factory()->forMealPlan($mealPlan)` -- see `database/factories/ContentShareFactory.php` for available states (`forMealPlan`, `withWritePermission`, etc.).
