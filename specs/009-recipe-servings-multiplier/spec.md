# Feature Specification: Recipe Servings Multiplier

**Feature Branch**: `009-recipe-servings-multiplier`
**Created**: 2025-12-14
**Status**: Draft
**Input**: User description: "Add a servings multipler field to the recipe page https://project-tabletop.ddev.site/recipes/104. This will Adjust the serving multiplier to scale ingredient quantities (0.25x to 10x)."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Adjust Recipe Servings (Priority: P1)

A user viewing a recipe wants to adjust the serving size to match the number of people they're cooking for. They can increase or decrease the servings multiplier, and all ingredient quantities automatically recalculate to show the scaled amounts.

**Why this priority**: This is the core value proposition of the feature - allowing users to dynamically scale recipes without manual calculation. Without this, users must mentally calculate scaled quantities themselves.

**Independent Test**: Can be fully tested by viewing any recipe with ingredients, adjusting the multiplier slider/input, and verifying ingredient quantities update in real-time. Delivers immediate value as a standalone feature.

**Acceptance Scenarios**:

1. **Given** a user is viewing a recipe with 4 servings and an ingredient requiring 2 cups of flour, **When** they set the multiplier to 2x (8 servings), **Then** the flour quantity displays as "4 cups"
2. **Given** a user is viewing a recipe, **When** they adjust the multiplier to 0.5x, **Then** all ingredient quantities are halved
3. **Given** a user is viewing a recipe with fractional ingredient quantities (e.g., 1.5 cups), **When** they apply a multiplier of 2x, **Then** the quantity displays as "3 cups"
4. **Given** a user adjusts the multiplier, **When** they navigate away from the recipe and return, **Then** the multiplier resets to 1x (default)
5. **Given** a recipe has an ingredient with no quantity specified, **When** the user adjusts the multiplier, **Then** that ingredient displays unchanged

---

### User Story 2 - Visual Multiplier Control (Priority: P2)

A user wants an intuitive way to adjust the serving multiplier without typing exact numbers. They can use a visual control (slider or preset buttons) to quickly set common multiplier values like 0.5x, 1x, 2x, 4x.

**Why this priority**: Enhances usability but the feature works without it (users can type values). Provides convenience for common scaling scenarios.

**Independent Test**: Can be tested by interacting with the multiplier control UI elements and verifying the multiplier value updates. Works independently of the calculation logic.

**Acceptance Scenarios**:

1. **Given** a user is viewing a recipe, **When** they click a "2x" preset button, **Then** the multiplier is set to 2.0 and ingredients scale accordingly
2. **Given** a user is using a slider control, **When** they drag it to the 0.5x position, **Then** the multiplier updates to 0.5 and ingredients recalculate
3. **Given** a user has adjusted the multiplier using a preset or slider, **When** they manually type a custom value, **Then** the typed value overrides the preset/slider value

---

### User Story 3 - Preserve Original Servings Display (Priority: P3)

A user wants to see both the original recipe servings and the adjusted servings count so they understand how the recipe has been scaled.

**Why this priority**: Helpful context but not essential for core functionality. Users can calculate this themselves if needed.

**Independent Test**: Can be tested by verifying the display shows both original servings (e.g., "4 servings") and scaled servings (e.g., "Adjusted to 8 servings") when multiplier is changed.

**Acceptance Scenarios**:

1. **Given** a recipe originally serves 4 people, **When** a user sets the multiplier to 2x, **Then** the page displays "Adjusted to 8 servings (from 4)"
2. **Given** a user sets the multiplier to 1x, **When** viewing the servings information, **Then** only the original servings count is shown (no "adjusted" text)

---

### Edge Cases

- What happens when the multiplier results in very small quantities (e.g., 0.25 cups becomes 0.0625 cups at 0.25x)?
- How does the system handle ingredient quantities that are null/undefined when applying a multiplier?
- What happens if a user enters a multiplier value outside the 0.25x to 10x range?
- How are fractional quantities displayed when they result in repeating decimals (e.g., 1/3 cup × 2 = 0.666...)?
- What happens when a user adjusts the multiplier while the page is still loading ingredient data?

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST allow users to adjust the serving multiplier within the range of 0.25x to 10x
- **FR-002**: System MUST recalculate all ingredient quantities in real-time when the multiplier changes
- **FR-003**: System MUST display scaled ingredient quantities using the same unit as the original ingredient
- **FR-004**: System MUST preserve the original recipe servings count and display it alongside the adjusted servings
- **FR-005**: System MUST handle ingredients with null/undefined quantities by leaving them unchanged when multiplier is applied
- **FR-006**: System MUST format scaled quantities to remove unnecessary trailing zeros (e.g., "2.0" displays as "2")
- **FR-007**: System MUST default the multiplier to 1x when a recipe page is first loaded or refreshed
- **FR-008**: System MUST validate multiplier input and reject values outside the 0.25x to 10x range
- **FR-009**: System MUST round scaled quantities to a maximum of 3 decimal places for display
- **FR-010**: Users MUST be able to input custom multiplier values via a text input field
- **FR-011**: System MUST provide visual controls (buttons or slider) for common multiplier values (0.25x, 0.5x, 1x, 2x, 4x)
- **FR-012**: System MUST calculate the adjusted servings count as original servings × multiplier and display it to the user

### Key Entities

- **Recipe**: Represents a recipe with a base servings count and associated ingredients
- **Recipe Ingredient**: Represents an ingredient within a recipe, including quantity, unit, and display formatting
- **Servings Multiplier**: A decimal value between 0.25 and 10.0 that scales all ingredient quantities

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Users can adjust recipe servings from 0.25x to 10x and see ingredient quantities update within 200 milliseconds
- **SC-002**: Scaled ingredient quantities are accurate to within 0.001 of the mathematically correct value
- **SC-003**: 95% of users successfully scale a recipe on their first attempt without errors or confusion
- **SC-004**: Users can complete the scaling action (finding the control, adjusting it, and verifying results) in under 10 seconds
- **SC-005**: The feature works correctly across all modern browsers (Chrome, Firefox, Safari, Edge) and mobile devices
- **SC-006**: Zero data loss occurs - original recipe data is never modified, only display values change

## Assumptions

- Ingredient quantities in the database are stored as decimal values with 3 decimal place precision
- The recipe page already displays ingredient quantities with proper formatting
- Users are viewing recipes in a modern web browser with JavaScript enabled
- The existing recipe display uses Livewire/Alpine.js for interactivity
- Multiplier state is session-based only (not persisted to database or user preferences)
- The servings multiplier applies equally to all ingredients in the recipe
- Users understand that the multiplier affects quantities but not cooking times or temperatures

## Out of Scope

- Persisting user's preferred multiplier across sessions or for specific recipes
- Adjusting cooking times or temperatures based on serving size changes
- Converting between different units (e.g., cups to tablespoons) as part of scaling
- Suggesting optimal pan sizes or cooking vessel changes for scaled recipes
- Nutritional information recalculation based on scaled servings
- Printing or exporting recipes with custom serving sizes
