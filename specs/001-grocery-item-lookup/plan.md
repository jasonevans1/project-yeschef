# Implementation Plan: Grocery Item Autocomplete Lookup

**Branch**: `001-grocery-item-lookup` | **Date**: 2025-12-27 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-grocery-item-lookup/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

Build an autocomplete system for grocery list items that suggests item names with pre-populated categories as users type. The system learns from user behavior, prioritizing personal usage history over common defaults. Includes a management interface for power users to customize their item templates. Technical approach uses Livewire components with Alpine.js for autocomplete UI, database tables for common defaults and user-specific templates, and fuzzy text matching for suggestions.

## Technical Context

**Language/Version**: PHP 8.3 (Laravel 12)
**Primary Dependencies**: Livewire 3, Livewire Volt, Livewire Flux (UI components), Laravel Fortify (authentication), Alpine.js (included with Livewire for autocomplete interactivity)
**Storage**: MariaDB (production via DDEV), SQLite (development/testing) - requires two new tables: `common_item_templates` and `user_item_templates`
**Testing**: Pest (PHP unit/feature tests), Playwright (E2E autocomplete interaction tests)
**Target Platform**: Web application (responsive design for desktop and mobile browsers)
**Project Type**: Web application (Laravel monolith with Livewire frontend)
**Performance Goals**: Autocomplete query response <200ms for up to 10,000 user item templates; support concurrent autocomplete queries from multiple users
**Constraints**: Must work gracefully on mobile devices with slower network connections; JavaScript required for autocomplete functionality
**Scale/Scope**: Per-user feature (scales with user count); approximately 100-200 common default items; unlimited user-specific templates (reasonable use assumed)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### Principle I: Livewire-First Architecture ✅

- **Compliance**: Autocomplete UI will be built as Livewire component enhancements to existing `GroceryLists\Show` component
- **Justification**: Uses Livewire wire:model.live for reactive search, wire:click for suggestion selection
- **Route Pattern**: Existing routes remain unchanged; autocomplete is embedded in existing add item workflow

### Principle II: Component-Driven Development ✅

- **Compliance**: Will use Flux input components for autocomplete field, Flux dropdown/listbox for suggestions display
- **Organization**: Autocomplete logic added to existing `app/Livewire/GroceryLists/Show.php` or extracted to `app/Livewire/GroceryLists/ItemAutocomplete.php` if complexity warrants
- **View Structure**: `resources/views/livewire/grocery-lists/show.blade.php` or new `resources/views/livewire/grocery-lists/item-autocomplete.blade.php`

### Principle III: Test-First Development ✅

- **Compliance**: Will write Pest tests BEFORE implementation for:
  - Autocomplete query endpoint (returns matching suggestions)
  - User item template creation/update on item save
  - Common defaults seeding on user registration
  - Personal history prioritization over common defaults
- **E2E Testing**: Playwright tests for:
  - Typing in item field triggers autocomplete suggestions
  - Selecting suggestion populates category field
  - Personal history suggestions appear correctly

### Principle IV: Full-Stack Integration Testing ✅

- **Compliance**: Integration tests will validate:
  - Complete autocomplete lifecycle (type → suggest → select → save)
  - Database state changes (user_item_templates updated)
  - Session persistence across requests
  - Authorization (users cannot access others' templates)

### Principle V: Developer Experience & Observability ✅

- **Compliance**:
  - DDEV environment will be used for local development
  - Laravel Pint will be run before commits
  - Database queries will be logged in development for debugging fuzzy search performance
  - `php artisan pail` will monitor autocomplete query logs

### Technology Stack Standards ✅

- **Compliance**: Uses existing stack without modifications:
  - Laravel 12 + Livewire 3 (no new frameworks)
  - Alpine.js (already included with Livewire, no separate installation)
  - Tailwind CSS 4.x for autocomplete dropdown styling
  - Pest + Playwright for testing
  - MariaDB/SQLite for storage

**Gate Status**: ✅ PASS - No constitutional violations. Feature aligns with all principles and uses existing technology stack.

## Project Structure

### Documentation (this feature)

```text
specs/001-grocery-item-lookup/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output - fuzzy matching, autocomplete patterns
├── data-model.md        # Phase 1 output - common_item_templates, user_item_templates
├── quickstart.md        # Phase 1 output - developer setup guide
├── contracts/           # Phase 1 output - autocomplete API endpoint contract
│   └── autocomplete-api.json
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
# Laravel 12 Web Application Structure (existing)
app/
├── Livewire/
│   └── GroceryLists/
│       ├── Show.php                    # MODIFIED: Add autocomplete logic or extract to ItemAutocomplete.php
│       └── ItemTemplates/              # NEW: Optional management interface (P3)
│           ├── Index.php
│           ├── Edit.php
│           └── Delete.php
├── Models/
│   ├── CommonItemTemplate.php         # NEW: Global default items
│   └── UserItemTemplate.php           # NEW: User-specific item history
├── Services/
│   └── ItemAutoCompleteService.php    # NEW: Fuzzy matching, suggestion ranking logic
└── Http/
    └── Requests/
        └── StoreGroceryItemRequest.php # MODIFIED: Track item usage for user templates

database/
├── migrations/
│   ├── [timestamp]_create_common_item_templates_table.php  # NEW
│   └── [timestamp]_create_user_item_templates_table.php    # NEW
└── seeders/
    └── CommonItemTemplateSeeder.php   # NEW: Seed 100-200 common items

resources/
└── views/
    └── livewire/
        └── grocery-lists/
            ├── show.blade.php          # MODIFIED: Add autocomplete UI
            └── item-templates/         # NEW: Optional management UI (P3)
                ├── index.blade.php
                └── edit.blade.php

tests/
├── Feature/
│   ├── GroceryLists/
│   │   └── AutocompleteItemTest.php   # NEW: Autocomplete query tests
│   └── ItemTemplates/
│       ├── CreateUserTemplateTest.php  # NEW: Auto-creation on item save
│       ├── PrioritizePersonalHistoryTest.php  # NEW
│       └── ManageTemplatesTest.php     # NEW: Optional management UI tests (P3)
└── Unit/
    └── Services/
        └── ItemAutoCompleteServiceTest.php  # NEW: Fuzzy matching logic tests

e2e/
└── grocery-lists/
    └── autocomplete-item.spec.ts       # NEW: Playwright E2E tests
```

**Structure Decision**: Using Laravel 12 monolith structure with Livewire components. Autocomplete functionality will be embedded in existing grocery list item addition workflow (`GroceryLists\Show` component). New models (`CommonItemTemplate`, `UserItemTemplate`) follow existing model patterns. Service class (`ItemAutoCompleteService`) handles fuzzy matching and suggestion ranking to keep component logic clean.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

**No violations** - This table is not required. Feature fully complies with all constitutional principles.

---

# Phase 0: Research & Technical Decisions

## Research Topics

The following topics require investigation to resolve technical unknowns:

1. **Fuzzy Text Matching for Autocomplete**
   - Research PHP fuzzy matching libraries (e.g., Levenshtein distance, similar_text, external packages)
   - Evaluate performance characteristics for real-time autocomplete (must respond <200ms)
   - Determine indexing strategy for fast substring searches (database LIKE vs. full-text search vs. in-memory)

2. **Livewire Autocomplete UI Patterns**
   - Research best practices for Livewire + Alpine.js autocomplete components
   - Evaluate wire:model.live vs. wire:model.debounce for query input
   - Determine how to handle keyboard navigation (arrow keys, enter to select)
   - Investigate accessibility requirements (ARIA attributes, screen reader support)

3. **User Item Template Update Strategy**
   - Determine when to create vs. update user item templates (on every item save, debounced, batch)
   - Design usage count increment strategy (simple counter vs. time-weighted frequency)
   - Evaluate whether to update templates on item edit/delete or only on creation

4. **Common Default Items Curation**
   - Research industry-standard grocery item categorizations
   - Determine initial seed list size (100-200 items) and selection criteria
   - Identify data source for common items (manual curation vs. external dataset)

5. **Database Indexing for Performance**
   - Determine optimal indexes for autocomplete queries (item name prefix search)
   - Evaluate composite indexes for user_id + item name lookups
   - Test query performance with 10,000+ user item templates

---

# Phase 1: Design & Contracts

## Data Model

See `data-model.md` for complete entity definitions.

**Summary**:
- `common_item_templates`: Global default items (name, category, unit, quantity)
- `user_item_templates`: User-specific item history (user_id, name, category, unit, quantity, usage_count, last_used_at)
- Relationships: User hasMany UserItemTemplates

## API Contracts

See `contracts/autocomplete-api.json` for OpenAPI specification.

**Summary**:
- `GET /api/grocery-lists/{list}/items/autocomplete?query={term}`: Returns matching suggestions with category, unit, quantity
- Response prioritizes user templates over common defaults, ranked by usage_count and fuzzy match score

## Quickstart Guide

See `quickstart.md` for developer setup instructions.

**Summary**:
1. Run migrations to create new tables
2. Seed common item templates
3. Install frontend dependencies (none required, Alpine.js included with Livewire)
4. Run tests to verify autocomplete functionality
5. Test in browser with DDEV environment

---

**Planning complete**. Proceed to Phase 0 research by generating `research.md`.
