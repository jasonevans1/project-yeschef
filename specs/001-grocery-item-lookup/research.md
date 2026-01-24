# Research Findings: Grocery Item Autocomplete Lookup

**Feature**: 001-grocery-item-lookup
**Created**: 2025-12-27
**Status**: Research Complete

## Overview

This document consolidates research findings for implementing an autocomplete system for grocery list items. The research covered fuzzy text matching, Livewire autocomplete patterns, and user template tracking strategies.

---

## 1. Fuzzy Text Matching Strategy

### Decision: Laravel Scout Database Driver + LIKE Queries

**Rationale:**
- Meets 200ms response time requirement (typically 15-40ms)
- Built into Laravel 12 (no external dependencies)
- Works with MariaDB/SQLite setup
- Scales well to 10,000+ user item templates

### Implementation Approach

**Primary Query**: Database-level LIKE queries with indexes
```php
// Prefix search (fastest)
UserItemTemplate::where('user_id', auth()->id())
    ->where('name', 'like', $query . '%')
    ->orderByDesc('usage_count')
    ->orderByDesc('last_used_at')
    ->limit(10)
    ->get();

// Contains search (fallback)
UserItemTemplate::where('user_id', auth()->id())
    ->where('name', 'like', '%' . $query . '%')
    ->orderByDesc('usage_count')
    ->limit(10)
    ->get();
```

**Optional Enhancement**: For typo tolerance, add PHP-level fuzzy filtering on limited result set (50-100 items):
```php
use Skogsvik\StrSim\Jaro;

$filtered = $candidates->filter(function ($item) use ($query, $jaro) {
    $score = $jaro->similarity(strtolower($query), strtolower($item->name));
    return $score >= 0.75; // 75% similarity threshold
})->take(20);
```

### Alternatives Considered

| Approach | Performance | Fuzzy Match | Decision |
|----------|-------------|-------------|----------|
| PHP levenshtein() on all items | ❌ 5-20 seconds | ✅ Yes | Rejected - too slow |
| MariaDB Full-Text Search | ✅ 15-40ms | ❌ No | Limited - no n-gram support |
| LIKE with standard index | ✅ 20-50ms | ❌ No | **Selected** - simple, reliable |
| Jaro-Winkler on filtered results | ⚠️ 100-300ms | ✅ Yes | Optional - second pass only |
| External search (Typesense/Meilisearch) | ✅ 5-20ms | ✅ Yes | Rejected - overkill for scale |

### Database Indexing Strategy

```php
Schema::create('user_item_templates', function (Blueprint $table) {
    // ...fields...

    // Indexes for autocomplete performance
    $table->index('name'); // For LIKE prefix queries
    $table->unique(['user_id', 'name']); // Prevent duplicates
    $table->index(['user_id', 'usage_count', 'last_used_at']); // For ranking
});
```

**Expected Performance:**
- Prefix search: 10-30ms for 10,000 items
- Contains search: 20-50ms for 10,000 items
- Well within 200ms budget

---

## 2. Livewire + Alpine.js Autocomplete UI Patterns

### Decision: wire:model.live.debounce.300ms with Alpine State Management

**Rationale:**
- Balances responsiveness with network efficiency
- Alpine handles client-side state (dropdown visibility, keyboard navigation)
- Livewire handles server-side state (database queries, suggestions)
- Follows existing codebase patterns (Alpine used in settings components)

### Recommended Component Architecture

```blade
<div x-data="groceryAutocomplete()">
  {{-- Input Field --}}
  <input
    type="text"
    wire:model.live.debounce.300ms="searchQuery"
    @focus="isOpen = true"
    @blur="setTimeout(() => isOpen = false, 150)"
    @keydown.arrow-down.prevent="selectNext()"
    @keydown.arrow-up.prevent="selectPrevious()"
    @keydown.enter.prevent="selectCurrent()"
    @keydown.escape="closeDropdown()"
    aria-autocomplete="list"
    aria-controls="suggestions-list"
    aria-expanded="@{{ isOpen }}"
    aria-activedescendant="@{{ activeIndex !== null ? `suggestion-${activeIndex}` : '' }}"
  />

  {{-- Suggestions Dropdown --}}
  <div
    x-show="isOpen && $wire.suggestions.length > 0"
    id="suggestions-list"
    role="listbox"
    aria-label="Available grocery items"
  >
    <template x-for="(item, index) in $wire.suggestions" :key="item.id">
      <div
        :id="`suggestion-${index}`"
        @click="selectItem(item)"
        @mouseenter="activeIndex = index"
        role="option"
        :aria-selected="activeIndex === index"
        :class="activeIndex === index ? 'bg-blue-100' : ''"
      >
        {{ item.name }}
      </div>
    </template>
  </div>
</div>

<script>
function groceryAutocomplete() {
  return {
    isOpen: false,
    activeIndex: null,

    selectNext() {
      this.activeIndex = (this.activeIndex ?? -1) + 1;
      if (this.activeIndex >= this.$wire.suggestions.length) {
        this.activeIndex = 0;
      }
      this.scrollIntoView();
    },

    selectPrevious() {
      this.activeIndex = (this.activeIndex ?? 0) - 1;
      if (this.activeIndex < 0) {
        this.activeIndex = this.$wire.suggestions.length - 1;
      }
      this.scrollIntoView();
    },

    selectCurrent() {
      if (this.activeIndex !== null) {
        this.selectItem(this.$wire.suggestions[this.activeIndex]);
      }
    },

    selectItem(item) {
      this.$wire.selectGroceryItem(item);
      this.closeDropdown();
    },

    closeDropdown() {
      this.isOpen = false;
      this.activeIndex = null;
    },

    scrollIntoView() {
      this.$nextTick(() => {
        const active = document.getElementById(`suggestion-${this.activeIndex}`);
        if (active) {
          active.scrollIntoView({ block: 'nearest' });
        }
      });
    },
  };
}
</script>
```

### Keyboard Navigation Requirements

| Key | Behavior |
|-----|----------|
| Arrow Down | Move to next suggestion |
| Arrow Up | Move to previous suggestion |
| Enter | Select active suggestion |
| Escape | Close dropdown |
| Tab | Close dropdown & move focus away |

### Accessibility (ARIA) Requirements

Essential ARIA attributes per W3C WAI-ARIA Authoring Practices:
- `role="combobox"` on input
- `aria-autocomplete="list"` indicates list suggestions
- `aria-expanded="true|false"` tracks dropdown state
- `aria-activedescendant="id"` identifies active option (focus stays on input)
- `role="listbox"` on dropdown container
- `role="option"` on each suggestion
- `aria-selected="true|false"` highlights active option

### Mobile Considerations

- **Touch targets**: Minimum 44x44px (use h-12 Tailwind class)
- **No hover-dependent UI**: Use @click as primary interaction
- **Touch-aware selection**: Add `touch-manipulation` class to disable double-tap zoom delay
- **Viewport management**: Dropdown should fit without pushing content
- **Active states**: Use `active:` Tailwind utilities for touch feedback

### Integration with Flux Components

**Note**: Flux combobox component is Pro-only (not available in free tier)

**Recommended Hybrid Approach**:
```blade
<flux:field>
  <flux:label>Search Grocery Items</flux:label>

  {{-- Custom autocomplete (Alpine + Livewire) --}}
  <div x-data="groceryAutocomplete()">
    <input class="flux-input" wire:model.live.debounce.300ms="searchQuery" />
    {{-- Custom dropdown --}}
  </div>
</flux:field>

{{-- Use Flux badges for selected items --}}
<template x-for="item in selectedItems">
  <flux:badge>
    <span x-text="item.name"></span>
    <button @click="removeItem(item)">
      <flux:icon.x-mark />
    </button>
  </flux:badge>
</template>
```

---

## 3. User Item Template Update Strategy

### Decision: Observer + Async Job Pattern

**Rationale:**
- Non-blocking (user request completes immediately)
- Built into Laravel (no external dependencies)
- Respects existing queue infrastructure
- Follows Observer pattern already used in Laravel Scout

### When to Create/Update Templates

**Track**: Item creation only (via `GroceryItemObserver`)
**Ignore**: Item edits, deletes, updates

**Reasoning:**
- Creations indicate user preference (adding "milk" repeatedly)
- Edits are corrections (typos, quantity adjustments)
- Deletes are list cleanup (purchased items), not preference changes

### Usage Count Tracking Strategy

**Decision**: Simple counter + last_used_at timestamp

```php
// On each item creation
UserItemTemplate::updateOrCreate(
    ['user_id' => $userId, 'name' => $itemName],
    [
        'usage_count' => \DB::raw('usage_count + 1'),
        'last_used_at' => now(),
        'category' => $category,
        'unit' => $unit,
    ]
);
```

**Autocomplete Query**:
```php
UserItemTemplate::where('user_id', auth()->id())
    ->orderByDesc('usage_count')
    ->orderByDesc('last_used_at')
    ->limit(10)
    ->get();
```

**Alternatives Considered:**

| Approach | Accuracy | Complexity | Performance | Decision |
|----------|----------|-----------|-------------|----------|
| Simple counter | Good | Low | O(1) increments | **Selected** |
| Time-weighted decay | Better | Medium | O(1) + weekly job | Rejected - unnecessary |
| Time-series (event log) | Best | High | O(n) analysis | Rejected - over-engineered |

### Database Schema

```php
Schema::create('user_item_templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('category')->nullable();
    $table->string('unit')->nullable();
    $table->unsignedInteger('usage_count')->default(1);
    $table->timestamp('last_used_at')->useCurrent();
    $table->timestamps();

    // Prevent duplicates
    $table->unique(['user_id', 'name']);

    // Query performance
    $table->index(['user_id', 'usage_count', 'last_used_at']);
});
```

### Implementation Pattern

**Observer** (app/Observers/GroceryItemObserver.php):
```php
public function created(GroceryItem $item): void
{
    if ($item->source_type === SourceType::MANUAL) {
        dispatch(new UpdateUserItemTemplate(
            userId: $item->groceryList->user_id,
            itemName: $item->name,
            category: $item->category?->value,
            unit: $item->unit?->value,
        ));
    }
}
```

**Async Job** (app/Jobs/UpdateUserItemTemplate.php):
```php
public function handle(): void
{
    UserItemTemplate::updateOrCreate(
        ['user_id' => $this->userId, 'name' => $this->itemName],
        [
            'usage_count' => \DB::raw('usage_count + 1'),
            'last_used_at' => now(),
            'category' => $this->category,
            'unit' => $this->unit,
        ]
    );
}
```

### Performance Optimizations

**Caching Strategy**:
```php
// Cache top 10 suggestions per user for 1 hour
$suggestions = cache()->remember(
    "suggestions.{$userId}",
    3600,
    fn() => UserItemTemplate::where('user_id', $userId)
        ->orderByDesc('usage_count')
        ->limit(10)
        ->get()
);

// Invalidate on template update
cache()->forget("suggestions.{$userId}");
```

**Query Optimization**:
- Unique index on `(user_id, name)` prevents duplicates
- Composite index on `(user_id, usage_count, last_used_at)` optimizes sorting
- Expected performance: ~20ms for autocomplete queries

---

## 4. Common Default Items Curation

### Decision: Manual curation with database seeder

**Initial Seed List**: 100-200 common grocery items

**Categorization Strategy**:
- Use existing `IngredientCategory` enum (produce, dairy, meat, seafood, pantry, frozen, bakery, deli, beverages, other)
- Research industry-standard categorizations (USDA food groups, typical grocery store layouts)
- Default units based on common purchase patterns (e.g., milk → gallon, eggs → whole, lettuce → whole)

**Seed Data Examples**:

| Name | Category | Unit | Default Quantity |
|------|----------|------|------------------|
| milk | dairy | gallon | 1 |
| bread | bakery | whole | 1 |
| eggs | dairy | whole | 12 |
| banana | produce | whole | 6 |
| chicken breast | meat | lb | 2 |
| ground beef | meat | lb | 1 |
| tomato | produce | whole | 4 |
| lettuce | produce | whole | 1 |
| cheddar cheese | dairy | lb | 1 |
| pasta | pantry | lb | 1 |

**Implementation**:
```php
// database/seeders/CommonItemTemplateSeeder.php
public function run(): void
{
    $items = [
        ['name' => 'milk', 'category' => 'dairy', 'unit' => 'gallon', 'quantity' => 1],
        ['name' => 'bread', 'category' => 'bakery', 'unit' => 'whole', 'quantity' => 1],
        // ... 100-200 items
    ];

    foreach ($items as $item) {
        CommonItemTemplate::create($item);
    }
}
```

**Future Enhancement**: Admin UI to add/edit common templates (not in initial scope)

---

## 5. Architecture Summary

### Data Flow

```
User Types "mil"
    ↓
[Livewire wire:model.live.debounce.300ms]
    ↓ (300ms debounce)
[Database Query]
  ├─ Query user_item_templates (user's personal history)
  ├─ Query common_item_templates (global defaults)
  └─ Merge results (user templates first)
    ↓ (~20-50ms)
[Return Top 10 Results]
    ↓
[Alpine.js Renders Dropdown]
    ↓
[User Selects Item via Arrow Keys + Enter]
    ↓
[Livewire Saves Item]
    ↓
[Observer Dispatches UpdateUserItemTemplate Job]
    ↓ (async, 5s delay)
[Queue Worker Updates user_item_templates]
    ↓
[Cache Invalidated]
```

### Technology Stack

- **Backend**: Laravel 12, PHP 8.3
- **Frontend**: Livewire 3, Alpine.js (bundled), Tailwind CSS 4.x
- **Database**: MariaDB (production), SQLite (dev/test)
- **Queue**: Laravel queue (database driver)
- **Search**: Laravel Scout database driver (built-in)

### Performance Targets

| Operation | Target | Strategy |
|-----------|--------|----------|
| Autocomplete query | <200ms | Database indexes, limit 10 results |
| Item save | <100ms | Async observer, non-blocking |
| Template update | N/A | Background job, 5s delay |
| Cache hit | <5ms | 1-hour TTL, per-user cache |

---

## 6. Implementation Checklist

### Phase 0: Database Setup
- [ ] Create `common_item_templates` migration
- [ ] Create `user_item_templates` migration
- [ ] Create CommonItemTemplate model
- [ ] Create UserItemTemplate model
- [ ] Create CommonItemTemplateSeeder with 100-200 items
- [ ] Run migrations and seeders

### Phase 1: Observer + Job
- [ ] Create GroceryItemObserver
- [ ] Create UpdateUserItemTemplate job
- [ ] Register observer in AppServiceProvider
- [ ] Test observer with existing add item functionality

### Phase 2: Autocomplete UI
- [ ] Create autocomplete Livewire component (or enhance Show.php)
- [ ] Add Alpine.js state management script
- [ ] Implement keyboard navigation (arrows, enter, escape)
- [ ] Add ARIA attributes for accessibility
- [ ] Style dropdown with Tailwind (mobile-friendly)

### Phase 3: Testing
- [ ] Write Pest tests for autocomplete query logic
- [ ] Write Pest tests for observer/job workflow
- [ ] Write Playwright E2E tests for autocomplete interaction
- [ ] Write Playwright tests for keyboard navigation

### Phase 4: Optimization
- [ ] Add caching layer for user suggestions
- [ ] Add database indexes for performance
- [ ] Test with 10,000+ user templates
- [ ] Verify <200ms response time

---

## 7. Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Autocomplete too slow (>200ms) | Poor UX | Use database indexes, cache top 10 per user |
| Duplicate user templates | Data integrity | Unique constraint on (user_id, name) |
| Observer job failures | Missing templates | Queue monitoring, retry logic |
| Mobile keyboard navigation | Accessibility | ARIA attributes, touch-friendly targets |
| Fuzzy matching inaccuracy | Poor suggestions | Start with LIKE, add Jaro-Winkler if needed |

---

## 8. Open Questions / Future Enhancements

1. **Synonyms Handling**: How to handle "soda" vs "pop" vs "soft drink"?
   - Solution: Add `search_keywords` field to common_item_templates

2. **Multi-language Support**: International users with non-English item names?
   - Solution: Store item names in multiple languages (future enhancement)

3. **Common Template Updates**: How often to update common defaults?
   - Solution: Admin UI for template management (not in initial scope)

4. **Time-Weighted Decay**: Should old templates decay in relevance?
   - Solution: Not needed for initial launch; monitor usage patterns first

5. **Cross-Device Sync**: Are user templates synced across devices?
   - Solution: Yes - stored server-side per user_id, accessible everywhere

---

## Sources & References

### Fuzzy Text Matching
- [PHP: soundex - Manual](https://www.php.net/manual/en/function.soundex.php)
- [Laravel Scout Documentation](https://laravel.com/docs/12.x/scout)
- [MariaDB Full-Text Search Documentation](https://mariadb.com/kb/en/full-text-indexes/)
- [Building fast, fuzzy site search with Laravel and Typesense](https://laravel-news.com/building-fast-fuzzy-site-search-with-laravel-and-typesense)
- [GitHub - wyndow/fuzzywuzzy](https://github.com/wyndow/fuzzywuzzy)

### Livewire + Alpine.js Autocomplete
- [WAI-ARIA Combobox Pattern - W3C](https://www.w3.org/WAI/ARIA/apg/patterns/combobox/)
- [Editable Combobox With List Autocomplete Example - W3C](https://www.w3.org/WAI/ARIA/apg/patterns/combobox/examples/combobox-autocomplete-list/)
- [Create an accessible combobox using ARIA - Pope Tech Blog](https://blog.pope.tech/2024/07/01/create-an-accessible-combobox-using-aria/)
- [Livewire Documentation - wire:model modifiers](https://livewire.laravel.com/docs/forms)
- [Alpine.js Official Documentation - Dropdown](https://alpinejs.dev/component/dropdown)

### Laravel Observers & Jobs
- [Laravel Model Observers Documentation](https://laravel.com/docs/12.x/eloquent#observers)
- [Laravel Queues Documentation](https://laravel.com/docs/12.x/queues)
- [Laravel Events & Listeners](https://laravel.com/docs/12.x/events)

---

**Research Complete**: 2025-12-27
**Next Phase**: Generate data-model.md and contracts/ (Phase 1)
