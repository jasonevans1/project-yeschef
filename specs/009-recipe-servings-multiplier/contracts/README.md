# API Contracts: Recipe Servings Multiplier

**Feature**: 009-recipe-servings-multiplier
**Type**: Client-Side Only (No API Contracts)

## Overview

This feature requires **no API contracts** because all functionality is client-side:

- **No HTTP Endpoints**: The feature uses existing recipe detail page routes
- **No AJAX Requests**: All calculations happen in the browser using Alpine.js
- **No Server State Changes**: Multiplier state is session-based (browser memory only)
- **Read-Only Data**: Uses existing Livewire component data binding

## Existing Endpoints Used (Read-Only)

### Recipe Show Route

**Route**: `GET /recipes/{recipe}`
**Controller/Component**: `App\Livewire\Recipes\Show`
**Authorization**: `RecipePolicy@view`

**Purpose**: Loads recipe data including ingredients for display

**No Changes Required**: The existing route provides all necessary data through Livewire's automatic data binding.

## Data Binding (Livewire → Alpine.js)

The feature uses standard Livewire data binding to hydrate Alpine.js state:

```blade
<div
    x-data="servingsMultiplier()"
    x-init="originalServings = {{ $recipe->servings }}"
>
    @foreach ($recipe->recipeIngredients as $ingredient)
        <span x-text="scaleQuantity({{ $ingredient->quantity }})"></span>
    @endforeach
</div>
```

**Data Flow**:
1. Laravel/Livewire renders recipe data server-side
2. Blade template injects values into Alpine.js `x-init` and inline expressions
3. Alpine.js reads values on page load (one-time hydration)
4. All subsequent updates are client-side only

## No WebSocket/Real-Time Communication

This feature does not use:
- WebSockets
- Server-Sent Events (SSE)
- Polling
- Livewire wire:poll

All updates are instant client-side calculations.

## Summary

**API Contract Status**: Not applicable - this is a presentation-layer enhancement with no server communication beyond initial page load.

For API contracts in features that require them, see other spec directories (e.g., `specs/006-import-recipe/contracts/`).
