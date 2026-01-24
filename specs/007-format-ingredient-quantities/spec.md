# Feature Specification: Format Ingredient Quantities Display

**Feature Branch**: `007-format-ingredient-quantities`
**Created**: 2025-12-06
**Status**: Draft
**Input**: User description: "When viewing a recipe change the ingredient quantity so it does not display a decimal if it is zero. Example 2.000 lb should be displayed as 2 lb."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - View Recipe with Whole Number Quantities (Priority: P1)

When users view a recipe, ingredient quantities that are whole numbers should display without unnecessary decimal places (e.g., "2 lb" instead of "2.000 lb"), making the recipe easier to read and more professional in appearance.

**Why this priority**: This is the core requirement - improving readability for the most common case (whole number quantities). It provides immediate value and delivers the primary user benefit.

**Independent Test**: Can be fully tested by viewing any recipe with whole number ingredient quantities (e.g., 2 cups flour, 1 lb beef) and verifying decimals are not shown. Delivers a cleaner, more readable recipe display.

**Acceptance Scenarios**:

1. **Given** a recipe has an ingredient with quantity 2.000, **When** user views the recipe, **Then** the quantity displays as "2" (not "2.000" or "2.00")
2. **Given** a recipe has an ingredient with quantity 1.000 lb, **When** user views the recipe, **Then** the quantity displays as "1 lb"
3. **Given** a recipe has an ingredient with quantity 5.000 cups, **When** user views the recipe, **Then** the quantity displays as "5 cups"

---

### User Story 2 - View Recipe with Fractional Quantities (Priority: P2)

When users view a recipe, ingredient quantities that are fractional amounts should display appropriately (e.g., "1.5 cups" or "0.5 tsp"), ensuring precision is maintained while keeping the display clean and removing trailing zeros.

**Why this priority**: While important for recipe accuracy, fractional quantities are less common than whole numbers and the current decimal display is acceptable. This is an enhancement to further improve readability.

**Independent Test**: Can be fully tested by viewing recipes with fractional quantities (0.5, 1.5, 2.75, etc.) and verifying they display clearly without trailing zeros. Delivers improved readability for fractional measurements.

**Acceptance Scenarios**:

1. **Given** a recipe has an ingredient with quantity 1.500, **When** user views the recipe, **Then** the quantity displays as "1.5" (not "1.500")
2. **Given** a recipe has an ingredient with quantity 0.500, **When** user views the recipe, **Then** the quantity displays as "0.5"
3. **Given** a recipe has an ingredient with quantity 2.750, **When** user views the recipe, **Then** the quantity displays as "2.75" (preserving necessary decimal precision)

---

### User Story 3 - View Recipe with Edge Case Quantities (Priority: P3)

When users view a recipe with unusual quantity values (null, very small decimals, or very large numbers), the system should handle these gracefully without errors or display issues.

**Why this priority**: Edge cases are rare in typical recipes but need to be handled to prevent errors. Lower priority as these are exceptional scenarios.

**Independent Test**: Can be fully tested by creating recipes with null quantities, very small decimals (0.001), or large numbers (1000.000) and verifying proper display. Delivers system robustness.

**Acceptance Scenarios**:

1. **Given** a recipe has an ingredient with null quantity, **When** user views the recipe, **Then** the ingredient displays without a quantity (current behavior maintained)
2. **Given** a recipe has an ingredient with quantity 0.001, **When** user views the recipe, **Then** the quantity displays with appropriate precision (not rounded to 0)
3. **Given** a recipe has an ingredient with quantity 1000.000, **When** user views the recipe, **Then** the quantity displays as "1000"

---

### Edge Cases

- What happens when quantity is exactly 0.000? (Should likely display as empty or "0")
- How does the system handle very precise decimals like 0.333333 (repeating thirds)?
- What happens with null or missing quantity values? (Should maintain current behavior)
- How are quantities displayed when they're stored with varying decimal precision in the database (1.0 vs 1.00 vs 1.000)?

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST display ingredient quantities without trailing zeros after the decimal point (e.g., "2.000" displays as "2")
- **FR-002**: System MUST preserve necessary decimal precision for fractional quantities (e.g., "1.5" or "0.75" are displayed with their significant decimals)
- **FR-003**: System MUST handle whole number quantities by displaying them as integers without decimal points (e.g., "2" not "2.0")
- **FR-004**: System MUST maintain existing behavior for null or missing quantity values (display ingredient name only)
- **FR-005**: System MUST apply consistent quantity formatting across all recipe ingredient displays

### Key Entities

- **RecipeIngredient**: Represents an ingredient in a recipe with quantity (decimal up to 3 places), unit (measurement enum), ingredient reference, and optional notes
- **MeasurementUnit**: Enumeration of valid measurement units (tsp, tbsp, cup, oz, lb, gram, etc.)

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of recipe ingredient quantities that are whole numbers display without decimal points or trailing zeros
- **SC-002**: Recipe ingredient display maintains precision for all fractional values (no unintended rounding or truncation)
- **SC-003**: Users viewing recipes see cleaner, more professional quantity formatting (qualitative improvement validated through visual inspection)
- **SC-004**: No errors or display issues occur when viewing recipes with edge case quantities (null, very small, very large)

## Assumptions

- Quantities are stored in the database as decimals with up to 3 decimal places precision
- The existing RecipeIngredient model and database schema do not need modification
- Formatting is a display-only concern and does not affect data storage or input validation
- The application already has similar formatting logic in the GroceryItem model that can serve as reference
- Users expect professional recipe formatting similar to cookbooks and recipe websites

## Out of Scope

- Changing how ingredient quantities are stored in the database
- Converting decimal quantities to common fractions (e.g., 0.5 to "½") - this could be a future enhancement
- Modifying quantity input validation or editing interfaces
- Changing quantity display in other areas of the application (grocery lists, etc.) unless they use the same display logic
- Localization or internationalization of number formats (e.g., European comma vs period for decimals)
