# Task 004: Fix Livewire 4 View and Directive Changes

**Status**: completed
**Depends on**: [001]
**Retry count**: 0

## Description
Audit all Blade view files under `resources/views/livewire/` for Livewire 4 breaking changes in directives, wire: modifiers, and component tag syntax. Apply fixes where found.

## Context
- Directory to audit: `resources/views/livewire/` (all `.blade.php` files)
- Also check: `resources/views/components/` for any embedded Livewire tags

### Livewire 4 View Breaking Changes

**1. wire:model modifier changes (Medium Impact)**
In Livewire 4, `.blur` and `.change` modifiers control *client-side* sync timing, not just server sync. The previous behavior (immediate client update + deferred server sync) now requires the `.live` prefix:
```blade
{{-- Livewire 3 --}}
wire:model.blur="field"
wire:model.change="field"

{{-- Livewire 4 --}}
wire:model.live.blur="field"
wire:model.live.change="field"
```
`.lazy` remains backward compatible.

**2. wire:navigate:scroll replaces wire:scroll (Low Impact)**
If any scrollable containers use `wire:scroll`, rename to `wire:navigate:scroll`.

**3. Component tags must self-close (Low Impact)**
```blade
{{-- Livewire 3 (may have worked unclosed) --}}
<livewire:some-component>

{{-- Livewire 4 (required) --}}
<livewire:some-component />
```

**4. Livewire config key renames (Medium Impact)**
If `config/livewire.php` does not exist (not published), no action needed — Livewire 4 uses its own vendor defaults.
If it IS published (check first), rename:
- `layout` → `component_layout`
- `lazy_placeholder` → `component_placeholder`

**5. wire:model.deep for containers (Rare)**
`wire:model` no longer captures events from child elements by default. If any custom component wraps an input inside a div with `wire:model`, add `.deep`. Standard Flux components (`flux:input`, `flux:textarea`, etc.) should handle this internally.

## Requirements (Test Descriptions)
- [x] `it has no wire:model.blur without .live prefix in blade views`
- [x] `it has no wire:model.change without .live prefix in blade views`
- [x] `it has no unclosed livewire component tags`
- [x] `it has no wire:scroll directives (replaced by wire:navigate:scroll)`
- [x] `it passes existing form-submission feature tests for recipes`
- [x] `it passes existing form-submission feature tests for meal plans`
- [x] `it passes existing form-submission feature tests for grocery lists`
- [x] `it passes existing form-submission feature tests for auth (login, register)`

## Acceptance Criteria
- All requirements have passing tests
- All blade views use correct Livewire 4 directive syntax
- No `wire:model.blur` or `wire:model.change` without `.live` prefix
- All `<livewire:>` tags are properly closed
- Existing feature tests continue to pass

## Implementation Notes
Search commands to find affected files:
```bash
grep -rn "wire:model\.blur\|wire:model\.change" resources/views/
grep -rn "wire:scroll" resources/views/
grep -rn "<livewire:[a-z]" resources/views/  # check for unclosed tags
```
