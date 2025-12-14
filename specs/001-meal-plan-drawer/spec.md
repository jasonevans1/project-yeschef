# Feature Specification: Multi-Recipe Meal Slots with Recipe Drawer

**Feature Branch**: `001-meal-plan-drawer`
**Created**: 2025-12-14
**Status**: Draft
**Input**: User description: "Enable meal plan slots to support multiple recipes with a custom Alpine.js slide-out drawer for viewing recipe details including scaled ingredients and a link to the full recipe page."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Add Multiple Recipes to Same Meal Slot (Priority: P1)

As a meal planner, I want to assign multiple recipes to the same meal slot (e.g., "Lunch on Monday") so that I can plan complex meals with multiple dishes or provide meal options.

**Why this priority**: This is the core functional change that removes the current one-recipe limitation. Without this, none of the other enhancements are possible. It's the foundation for all other user stories.

**Independent Test**: Can be fully tested by assigning 2+ different recipes to the same date and meal type (e.g., add "Chicken Salad" and "Soup" to Monday Lunch), then verifying both recipes appear in the meal slot and delivers the ability to plan multi-dish meals.

**Acceptance Scenarios**:

1. **Given** I'm viewing a meal plan with an empty meal slot, **When** I assign a recipe to that slot, **Then** the recipe appears in the slot
2. **Given** a meal slot already has one recipe, **When** I assign a different recipe to the same slot, **Then** both recipes appear in the slot
3. **Given** a meal slot has multiple recipes, **When** I view the meal plan, **Then** all recipes are displayed as individual cards
4. **Given** multiple recipes in a slot, **When** I add another recipe, **Then** an "Add Another" or "Add Recipe" button remains visible

---

### User Story 2 - View Recipe Details in Slide-Out Drawer (Priority: P2)

As a meal planner, I want to click a recipe card and see detailed information in a slide-out drawer so that I can quickly review ingredients, cooking times, and instructions without leaving the meal plan page.

**Why this priority**: Enhances usability by providing quick access to recipe details. This is P2 because users can still assign multiple recipes (P1) and navigate to full recipe pages, but this significantly improves the workflow.

**Independent Test**: Can be fully tested by clicking any recipe card in a meal slot and verifying the drawer opens with recipe name, servings, prep/cook times, scaled ingredients, and instructions. Delivers value by reducing navigation clicks.

**Acceptance Scenarios**:

1. **Given** a meal slot with a recipe, **When** I click the recipe card, **Then** a drawer slides in from the right showing recipe details
2. **Given** the recipe drawer is open, **When** I click the backdrop or close button, **Then** the drawer closes smoothly
3. **Given** the recipe drawer is open, **When** I press the Escape key, **Then** the drawer closes
4. **Given** a recipe with ingredients, **When** I open the drawer, **Then** I see all ingredients with scaled quantities based on the serving multiplier
5. **Given** a recipe in the drawer, **When** I view the servings information, **Then** I see the base servings and multiplier (if different from 1.0)
6. **Given** a recipe in the drawer, **When** I view timing information, **Then** I see prep time and cook time displayed clearly

---

### User Story 3 - Navigate to Full Recipe Page (Priority: P3)

As a meal planner, I want to click a "View Full Recipe" button in the drawer so that I can access the complete recipe page with all details and editing capabilities.

**Why this priority**: This is a convenience feature for deeper recipe interaction. Users can always close the drawer and navigate manually, but this streamlines the workflow when they need the full recipe page.

**Independent Test**: Can be fully tested by opening a recipe drawer and clicking "View Full Recipe" button, then verifying it navigates to the correct recipe detail page (`/recipes/{id}`).

**Acceptance Scenarios**:

1. **Given** the recipe drawer is open, **When** I click the "View Full Recipe" button, **Then** I'm navigated to the full recipe page
2. **Given** I navigate to the full recipe page, **When** I view the URL, **Then** it matches `/recipes/{recipe_id}` pattern

---

### User Story 4 - View Recipes in Chronological Order (Priority: P3)

As a meal planner, I want recipes within a meal slot to appear in the order I added them so that I can see my planning timeline and understand which dishes I added first.

**Why this priority**: This is a nice-to-have organizational feature. The order provides context but doesn't block core functionality. Users can still use all features regardless of recipe order.

**Independent Test**: Can be fully tested by adding 3 recipes to the same meal slot at different times, then verifying they appear in chronological order based on when they were added.

**Acceptance Scenarios**:

1. **Given** I add Recipe A at 10:00 AM and Recipe B at 10:05 AM to the same meal slot, **When** I view the meal slot, **Then** Recipe A appears before Recipe B
2. **Given** a meal slot with 3 recipes added at different times, **When** I reload the meal plan page, **Then** the recipes remain in chronological order

---

### User Story 5 - Remove Individual Recipes from Multi-Recipe Slots (Priority: P2)

As a meal planner, I want to remove individual recipes from a meal slot without affecting other recipes in the same slot so that I can adjust my meal plan as needed.

**Why this priority**: This is essential for meal plan management when using multiple recipes per slot. Without this, users would have no way to undo recipe assignments, significantly impacting usability.

**Independent Test**: Can be fully tested by adding 2 recipes to a slot, removing one, and verifying only the removed recipe is deleted while the other remains.

**Acceptance Scenarios**:

1. **Given** a meal slot with 2 recipes, **When** I click the remove button on one recipe, **Then** only that recipe is removed and the other remains
2. **Given** a meal slot with 1 recipe, **When** I remove it, **Then** the slot becomes empty and shows "Add Recipe" button
3. **Given** a recipe card, **When** I hover over it, **Then** the remove button becomes visible

---

### Edge Cases

- **Empty meal slots**: When no recipes are assigned, display a larger "Add Recipe" button with sufficient clickable area (min-height: 60px)
- **Recipe scaling with fractional multipliers**: When serving multiplier is not a whole number (e.g., 1.5x), format scaled quantities to 3 decimal places maximum and remove trailing zeros (e.g., "1.500" becomes "1.5", "2.000" becomes "2")
- **Recipes without ingredients**: When a recipe has no ingredients defined, display appropriate message in drawer ("No ingredients listed")
- **Recipes without instructions**: When a recipe has no instructions, hide the instructions section in drawer
- **Unauthorized access**: When a user tries to open a drawer for a recipe in a meal plan they don't have access to, prevent drawer from opening
- **Mobile view**: On small screens (< 640px), drawer should take full width; on larger screens, use max-width of 512-672px
- **Dark mode**: All drawer components and recipe cards must support dark mode styling
- **Keyboard navigation**: Recipe cards must be focusable and activatable with keyboard (Enter/Space keys)
- **Long recipe names**: Recipe names that exceed card width should wrap or truncate with ellipsis

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST allow users to assign multiple recipes to the same meal slot (date + meal type combination)
- **FR-002**: System MUST display all recipes within a meal slot as individual cards
- **FR-003**: System MUST order recipes within a meal slot chronologically by creation time
- **FR-004**: System MUST provide an "Add Recipe" button that is always visible in meal slots (showing "Add Another" when recipes already exist)
- **FR-005**: Users MUST be able to click a recipe card to open a slide-out drawer with recipe details
- **FR-006**: System MUST display in the drawer: recipe name, serving information, multiplier, prep time, cook time, scaled ingredients, instructions, and notes (when available)
- **FR-007**: System MUST calculate and display ingredient quantities scaled by the serving multiplier
- **FR-008**: System MUST format scaled quantities to maximum 3 decimal places with trailing zeros removed
- **FR-009**: System MUST provide a "View Full Recipe" button in the drawer that links to the recipe detail page
- **FR-010**: Users MUST be able to close the drawer by clicking backdrop, close button, or pressing Escape key
- **FR-011**: System MUST provide a remove button on each recipe card to delete individual recipes from meal slots
- **FR-012**: System MUST verify user has view permission for the meal plan before opening recipe drawer
- **FR-013**: System MUST support smooth transitions for drawer opening/closing (slide from right)
- **FR-014**: System MUST provide responsive drawer sizing (full width on mobile, constrained width on desktop)
- **FR-015**: System MUST support dark mode for all recipe cards and drawer components
- **FR-016**: System MUST make recipe cards keyboard-accessible and focusable

### Key Entities

- **Meal Slot**: A combination of date and meal type (breakfast/lunch/dinner/snack) within a meal plan that can now contain multiple recipe assignments
- **Meal Assignment**: Links a recipe to a specific meal slot with serving multiplier information and creation timestamp
- **Recipe Card**: Visual representation of a recipe within a meal slot showing name, servings, and multiplier
- **Recipe Drawer**: Slide-out panel displaying comprehensive recipe details including scaled ingredients and navigation options

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Users can assign unlimited recipes to a single meal slot (previously limited to 1)
- **SC-002**: Users can view recipe details (ingredients, times, instructions) without navigating away from meal plan page in under 1 second
- **SC-003**: Recipe drawer opens and closes smoothly within 300ms with visible transitions
- **SC-004**: Ingredient quantities are displayed with correct scaling (quantity × multiplier) with no calculation errors
- **SC-005**: All interactive elements (cards, buttons, drawer) remain accessible via keyboard navigation
- **SC-006**: Dark mode styling matches existing application patterns with no visual inconsistencies
- **SC-007**: On mobile devices (< 640px width), drawer is fully usable without horizontal scrolling
- **SC-008**: Users can remove individual recipes from multi-recipe slots in 1 click (plus confirmation if added)
- **SC-009**: Recipe order within slots is predictable and consistent (chronological by add time)
- **SC-010**: 95% of recipe drawer interactions (open, view, close) complete successfully without errors
