# Recipe Servings Multiplier: Technical Research & Decisions

## Executive Summary

This document outlines the technical decisions for implementing a recipe servings multiplier feature that allows users to dynamically scale ingredient quantities. The feature will use Alpine.js for client-side reactivity, native JavaScript for decimal handling, a combined button + input control for UX, and comprehensive ARIA support for accessibility.

---

## 1. Alpine.js Best Practices for Livewire Integration

### Decision: Use Alpine.data() with Computed Properties via JavaScript Getters

**Implementation Pattern:**
```javascript
// In resources/js/app.js
Alpine.data('servingsMultiplier', () => ({
    multiplier: 1,
    originalServings: 0,

    // Computed property using JavaScript getter
    get scaledServings() {
        return Math.round(this.originalServings * this.multiplier);
    },

    // Method to scale individual ingredient quantity
    scaleQuantity(originalQuantity) {
        if (!originalQuantity) return null;
        const scaled = parseFloat(originalQuantity) * this.multiplier;
        return this.formatQuantity(scaled);
    },

    formatQuantity(value) {
        // Format to 3 decimals, remove trailing zeros
        let formatted = value.toFixed(3);
        formatted = formatted.replace(/\.?0+$/, '');
        return formatted;
    }
}));
```

**Usage in Blade:**
```blade
<div x-data="servingsMultiplier()" x-init="originalServings = {{ $recipe->servings }}">
    <span x-text="scaleQuantity({{ $ingredient->quantity }})"></span>
</div>
```

### Rationale

1. **Follows Existing Pattern**: The codebase already uses `Alpine.data()` for registering reusable components (see `ingredientCheckboxes()` in `/Users/jasonevans/projects/project-tabletop/resources/js/app.js`)

2. **Reactive Computed Properties**: JavaScript getters in Alpine.js objects automatically become reactive. When `multiplier` changes, any expression using `scaledServings` or `scaleQuantity()` will re-evaluate automatically

3. **Clean Separation**: Keeps complex logic in JavaScript rather than inline in Blade templates, improving maintainability

4. **No Special Syntax Required**: Getters are accessed like regular properties (e.g., `x-text="scaledServings"` without parentheses)

### Alternatives Considered

**Alternative 1: Inline x-data**
```blade
<div x-data="{ multiplier: 1, scale: (qty) => qty * multiplier }">
```
**Rejected because:**
- Doesn't follow existing codebase patterns
- Harder to test in isolation
- Code duplication if used on multiple pages

**Alternative 2: Livewire Properties with Wire Polling**
```php
public $multiplier = 1;

public function getScaledQuantityProperty($quantity) {
    return $quantity * $this->multiplier;
}
```
**Rejected because:**
- Unnecessary server round-trips for simple calculations
- Slower UX (network latency)
- Scaling should be instant client-side interaction

**Alternative 3: Using $wire.entangle()**
```blade
<div x-data="{ multiplier: $wire.entangle('multiplier') }">
```
**Rejected because:**
- Livewire 3 documentation explicitly notes `$wire.entangle()` is deprecated
- Direct `$wire` property access is simpler and recommended
- Only needed if server-side persistence is required (it's not for this feature)

### Key Alpine.js Concepts

**Reactivity**: Alpine uses Vue's reactivity engine under the hood. Properties defined in `x-data` are automatically reactive ([source](https://alpinejs.dev/advanced/reactivity))

**Data Binding**: Alpine doesn't need special binding syntax - expressions automatically re-evaluate when dependencies change ([source](https://alpinejs.dev/directives/data))

**Computed Properties**: JavaScript getters serve as Alpine's computed properties, recalculating when dependent properties change ([source](https://codecourse.com/articles/computed-properties-in-alpine-js))

---

## 2. Decimal Precision Handling in JavaScript

### Decision: Use Native JavaScript toFixed() with String Manipulation

**Implementation:**
```javascript
formatQuantity(value) {
    if (value === null || value === undefined) return null;

    // Round to 3 decimal places
    let formatted = value.toFixed(3);

    // Remove trailing zeros
    formatted = formatted.replace(/\.?0+$/, '');

    return formatted;
}
```

**Examples:**
- `2.000` → `"2"`
- `1.500` → `"1.5"`
- `0.333` → `"0.333"`
- `0.3333333` → `"0.333"` (rounded)

### Rationale

1. **Matches Existing Pattern**: The `RecipeIngredient` model already uses this exact approach in `getDisplayQuantityAttribute()` (lines 40-51 of `/Users/jasonevans/projects/project-tabletop/app/Models/RecipeIngredient.php`)

2. **No External Dependencies**: Keeps bundle size minimal (no additional libraries)

3. **Sufficient Precision**: 3 decimal places handles all common cooking measurements:
   - `0.125` (1/8 cup)
   - `0.333` (1/3 cup)
   - `0.250` (1/4 teaspoon)

4. **Simple Edge Case Handling**:
   - Very small quantities (< 0.001) round to 0, which is acceptable for cooking
   - Large quantities work fine up to JavaScript's safe integer limit

5. **No Floating-Point Issues**: For the multiplication range (0.25x to 10x) and typical ingredient quantities (0.125 to 1000), JavaScript's IEEE 754 floating-point precision is adequate

### Alternatives Considered

**Alternative 1: decimal.js Library**
```javascript
import Decimal from 'decimal.js';

scaleQuantity(qty) {
    return new Decimal(qty).times(this.multiplier).toFixed(3);
}
```
**Rejected because:**
- Adds 30KB minified to bundle size ([source](https://github.com/MikeMcl/decimal.js))
- Overkill for this use case - we're not doing financial calculations or scientific computing
- Performance overhead for simple multiplication operations
- The precision gains are imperceptible in cooking contexts (no one measures 0.3333333 cups vs 0.333 cups)

**Alternative 2: Math.round() with Scaling**
```javascript
Math.round(value * 1000) / 1000
```
**Rejected because:**
- Doesn't format to string automatically
- Still need string manipulation to remove trailing zeros
- More verbose than toFixed()

**Alternative 3: Intl.NumberFormat API**
```javascript
new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 3
}).format(value)
```
**Rejected because:**
- More complex API
- Locale-dependent formatting may cause inconsistencies
- Overkill for simple quantity display

### Edge Cases Handled

1. **Null/Undefined Quantities**: Return null to preserve "to taste" ingredients
2. **Very Small Results**: `0.001 × 0.25 = 0.00025 → "0"` (acceptable - rounds down)
3. **Repeating Decimals**: `1/3 × 3 = 0.999... → "1"` (rounds correctly)
4. **Large Quantities**: Works up to safe integer limits (ingredients rarely exceed 1000)

### Performance Considerations

According to 2025 benchmarks, `toFixed()` is highly optimized in modern JavaScript engines and performs better than library-based solutions for simple use cases ([source](https://www.slingacademy.com/article/controlling-precision-without-losing-performance-in-javascript/)).

---

## 3. Input Validation and UX Patterns

### Decision: Hybrid Button + Range Input with Manual Override

**Implementation:**
```blade
<div x-data="servingsMultiplier()" class="flex items-center gap-4">
    {{-- Preset decrease button --}}
    <flux:button
        @click="multiplier = Math.max(0.25, multiplier - 0.25)"
        variant="ghost"
        icon="minus"
        size="sm"
        aria-label="Decrease serving size"
    ></flux:button>

    {{-- Visual display + manual input --}}
    <div class="flex flex-col items-center">
        <flux:input
            type="number"
            x-model.number="multiplier"
            min="0.25"
            max="10"
            step="0.25"
            @input="multiplier = Math.max(0.25, Math.min(10, $event.target.value))"
            class="w-20 text-center"
            aria-label="Serving size multiplier"
        />
        <span class="text-xs text-gray-500" x-text="`${scaledServings} servings`"></span>
    </div>

    {{-- Preset increase button --}}
    <flux:button
        @click="multiplier = Math.min(10, multiplier + 0.25)"
        variant="ghost"
        icon="plus"
        size="sm"
        aria-label="Increase serving size"
    ></flux:button>
</div>
```

### Rationale

1. **Multiple Input Methods**: Supports different user preferences and abilities
   - Quick increments via buttons (0.25x steps)
   - Direct keyboard input for precise values
   - Mobile-friendly touch targets

2. **Meets 2025 Accessibility Standards**:
   - Button tap targets meet 24×24px minimum ([source](https://robertcelt95.medium.com/the-new-accessibility-standards-every-designer-must-know-in-2025-815a297d2c6d))
   - Keyboard navigation works out of the box
   - Screen readers announce changes via ARIA labels

3. **Clear Value Communication**:
   - Shows both multiplier (1.5×) and resulting servings (6 servings)
   - Immediate visual feedback on changes

4. **Constrained Range**:
   - Min: 0.25× (quarter recipe - practical minimum)
   - Max: 10× (feeding a crowd - practical maximum)
   - Step: 0.25 (quarter increments align with common fractions)

5. **Mobile-Optimized**:
   - Buttons provide large touch targets
   - Number input triggers numeric keyboard on mobile
   - No fine motor control required (unlike sliders)

### Alternatives Considered

**Alternative 1: Slider Only**
```blade
<input type="range" min="0.25" max="10" step="0.25" x-model="multiplier">
```
**Rejected because:**
- Poor accessibility - difficult for keyboard users to achieve precise values
- Small touch targets on mobile
- Specific values don't matter in sliders, but they DO matter here ([source](https://www.nngroup.com/articles/gui-slider-controls/))
- No direct way to input exact values like "2.75×"

**Alternative 2: Buttons Only (No Direct Input)**
```blade
<button @click="multiplier -= 0.25">-</button>
<span x-text="multiplier"></span>
<button @click="multiplier += 0.25">+</button>
```
**Rejected because:**
- Cumbersome for large changes (need 20 clicks to go from 1× to 6×)
- No way to jump to specific value
- UX research shows users prefer combined approaches ([source](https://www.patternfly.org/components/number-input/design-guidelines/))

**Alternative 3: Dropdown with Preset Values**
```blade
<select x-model="multiplier">
    <option value="0.5">Half (0.5×)</option>
    <option value="1">Original (1×)</option>
    <option value="2">Double (2×)</option>
</select>
```
**Rejected because:**
- Limits flexibility (can't do 1.75× or other fractional multipliers)
- Doesn't scale well (would need 40 options for full 0.25-10 range)
- Less intuitive than direct manipulation

### Client-Side Validation

```javascript
// Enforce range constraints
@input="multiplier = Math.max(0.25, Math.min(10, $event.target.value))"

// Ensure numeric type
x-model.number="multiplier"
```

**Validation Rules:**
- Minimum: 0.25 (prevents division by zero or negative multipliers)
- Maximum: 10 (prevents unrealistic scaling)
- Numeric only: `.number` modifier coerces strings to numbers
- Real-time clamping: Invalid values immediately corrected

### Mobile Considerations

According to 2025 UX research, number inputs with adjacent buttons provide the best mobile experience because:
- Native number inputs trigger optimized mobile keyboards
- Buttons provide clear, tappable targets (no precision required)
- No reliance on slider drag gestures (which can be imprecise on small screens)

([source](https://luhr.co/blog/2025/07/01/a-deep-dive-on-the-ux-of-number-inputs/))

---

## 4. Accessibility Considerations

### Decision: Comprehensive ARIA Implementation with Live Regions

**Implementation:**
```blade
<div
    x-data="servingsMultiplier()"
    x-init="originalServings = {{ $recipe->servings }}"
    role="group"
    aria-labelledby="servings-heading"
>
    <flux:heading id="servings-heading" size="sm" class="mb-2">
        Adjust Servings
    </flux:heading>

    {{-- Controls --}}
    <div class="flex items-center gap-4">
        <flux:button
            @click="multiplier = Math.max(0.25, multiplier - 0.25)"
            variant="ghost"
            icon="minus"
            size="sm"
            aria-label="Decrease serving size by 0.25"
        ></flux:button>

        <div class="flex flex-col items-center">
            <flux:input
                type="number"
                x-model.number="multiplier"
                min="0.25"
                max="10"
                step="0.25"
                aria-label="Serving size multiplier"
                aria-describedby="servings-result"
            />
            <span
                id="servings-result"
                class="text-xs text-gray-500"
                x-text="`${scaledServings} servings`"
            ></span>
        </div>

        <flux:button
            @click="multiplier = Math.min(10, multiplier + 0.25)"
            variant="ghost"
            icon="plus"
            size="sm"
            aria-label="Increase serving size by 0.25"
        ></flux:button>
    </div>

    {{-- Live region for announcements --}}
    <div
        aria-live="polite"
        aria-atomic="true"
        class="sr-only"
        x-text="`Recipe scaled to ${multiplier} times original, making ${scaledServings} servings`"
    ></div>

    {{-- Ingredients list --}}
    <ul aria-label="Scaled ingredient quantities">
        <li>
            <span aria-label="{{ $ingredient->name }}, {{ scaleQuantity($ingredient->quantity) }} {{ $ingredient->unit }}">
                <span class="font-medium" x-text="scaleQuantity({{ $ingredient->quantity }})"></span>
                {{ $ingredient->unit }} {{ $ingredient->name }}
            </span>
        </li>
    </ul>
</div>
```

### Rationale

1. **ARIA Live Regions**: Announces quantity changes to screen readers without disrupting navigation
   - Uses `aria-live="polite"` (90% of dynamic updates should use polite, not assertive) ([source](https://rightsaidjames.com/2025/08/aria-live-regions-when-to-use-polite-assertive/))
   - `aria-atomic="true"` ensures entire message is read, not just the changed part
   - `.sr-only` class (screen reader only) hides visual clutter

2. **Semantic Grouping**:
   - `role="group"` with `aria-labelledby` groups controls semantically
   - Clear heading establishes context for screen reader users

3. **Descriptive Labels**:
   - All interactive elements have explicit `aria-label` attributes
   - Button labels describe the action ("Decrease serving size by 0.25")
   - Input uses `aria-describedby` to connect with result display

4. **Keyboard Navigation**:
   - Standard tab order through buttons and input
   - Number input supports arrow keys and direct typing
   - No keyboard traps or non-standard interactions

5. **Focus Management**:
   - No need for programmatic focus changes (simple update doesn't warrant it)
   - Visual focus indicators inherit from Flux/Tailwind defaults

### Accessibility Standards Compliance

**WCAG 2.2 Level AA:**
- **1.3.1 Info and Relationships**: Semantic HTML with proper ARIA roles
- **2.1.1 Keyboard**: All functionality available via keyboard
- **2.4.6 Headings and Labels**: Clear, descriptive labels
- **2.5.5 Target Size**: Buttons meet 24×24px minimum ([source](https://www.a11y-collective.com/blog/aria-live/))
- **4.1.3 Status Messages**: Live regions announce dynamic changes

### Alternatives Considered

**Alternative 1: No Live Regions (Visual Only)**
```blade
<span x-text="`${scaledServings} servings`"></span>
```
**Rejected because:**
- Screen reader users wouldn't know quantities changed
- Violates WCAG 4.1.3 (Status Messages)
- Poor experience for visually impaired users

**Alternative 2: aria-live="assertive"**
**Rejected because:**
- Interrupts screen reader immediately (jarring for users)
- Reserved for urgent alerts (errors, warnings) ([source](https://www.sarasoueidan.com/blog/accessible-notifications-with-aria-live-regions-part-1/))
- Recipe scaling is informational, not critical

**Alternative 3: Manual Screen Reader Instructions**
```blade
<span aria-label="Use buttons to change serving size">...</span>
```
**Rejected because:**
- Live regions provide automatic, real-time feedback
- Manual approach requires users to navigate back to check values
- Less discoverable than automatic announcements

### Implementation Best Practices (2025)

Based on recent research, the live region implementation follows critical 2025 best practices:

1. **Persistent DOM Element**: The `aria-live` region exists on page load (not conditionally rendered), ensuring the accessibility API recognizes it ([source](https://k9n.dev/blog/2025-11-aria-live/))

2. **Empty Initial State**: The live region starts empty and updates via Alpine's reactivity, preventing premature announcements

3. **Polite Priority**: Uses `polite` setting for non-urgent updates, allowing users to finish current screen reader context before announcing changes

4. **Atomic Updates**: `aria-atomic="true"` ensures complete context ("Recipe scaled to 1.5 times original, making 6 servings") rather than just the number

---

## Summary of Decisions

| Aspect | Decision | Primary Reason |
|--------|----------|----------------|
| **Alpine.js Pattern** | `Alpine.data()` with JavaScript getters | Follows existing codebase patterns, clean reactivity |
| **Decimal Precision** | Native `toFixed(3)` + regex | Matches existing model accessors, sufficient for cooking |
| **Control UI** | Buttons + number input | Best accessibility, multiple input methods, mobile-friendly |
| **Accessibility** | ARIA live regions + semantic HTML | WCAG 2.2 compliance, excellent screen reader UX |

---

## Next Steps

1. Implement `servingsMultiplier` Alpine component in `/Users/jasonevans/projects/project-tabletop/resources/js/app.js`
2. Update recipe show view to integrate controls and live regions
3. Write Pest browser tests covering:
   - Multiplier UI interactions (buttons, input)
   - Quantity scaling accuracy
   - Accessibility (keyboard navigation, ARIA announcements)
4. Add visual regression tests for different multiplier values

---

## References

### Alpine.js & Livewire
- [Alpine.js Data Directive](https://alpinejs.dev/directives/data)
- [Alpine.js Reactivity](https://alpinejs.dev/advanced/reactivity)
- [Computed Properties in Alpine.js](https://codecourse.com/articles/computed-properties-in-alpine-js)
- [Livewire JavaScript Reference](https://livewire.laravel.com/docs/javascript) (from Laravel Boost search results)

### JavaScript Precision
- [Number.prototype.toFixed() - MDN](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Number/toFixed)
- [decimal.js - GitHub](https://github.com/MikeMcl/decimal.js)
- [Avoiding Floating-Point Pitfalls](https://www.slingacademy.com/article/avoiding-floating-point-pitfalls-in-javascript-calculations/)
- [Controlling Precision Without Losing Performance](https://www.slingacademy.com/article/controlling-precision-without-losing-performance-in-javascript/)

### UX & Input Patterns
- [A Deep Dive on UX of Number Inputs](https://luhr.co/blog/2025/07/01/a-deep-dive-on-the-ux-of-number-inputs/)
- [Slider Design Rules of Thumb - Nielsen Norman Group](https://www.nngroup.com/articles/gui-slider-controls/)
- [Number Input Design Guidelines - PatternFly](https://www.patternfly.org/components/number-input/design-guidelines/)
- [New Accessibility Standards for 2025](https://robertcelt95.medium.com/the-new-accessibility-standards-every-designer-must-know-in-2025-815a297d2c6d)

### Accessibility
- [ARIA Live Regions - MDN](https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Guides/Live_regions)
- [aria-live Attribute - MDN](https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Reference/Attributes/aria-live)
- [Accessible Notifications with ARIA Live Regions](https://www.sarasoueidan.com/blog/accessible-notifications-with-aria-live-regions-part-1/)
- [When Your Live Region Isn't Live](https://k9n.dev/blog/2025-11-aria-live/)
- [ARIA-live Announcements Cheatsheet](https://rightsaidjames.com/2025/08/aria-live-regions-when-to-use-polite-assertive/)
- [Complete Guide to ARIA Live Regions](https://www.a11y-collective.com/blog/aria-live/)
