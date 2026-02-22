# Feature Specification: Grocery List Category Filtering and Auto-Categorization

**Feature Branch**: `012-grocery-list-categories`
**Created**: 2026-02-22
**Status**: Draft
**Input**: User description: "Change the generating grocery list from a meal plan to set grocery item categories. This could use the existing user_item_templates to find the category for a recipe ingredient? Additionally add a step when generating list from a meal plan to remove items by category. This will be optional. Example do not add spices to the grocery list since I will already have these items"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Exclude Categories When Generating a Grocery List (Priority: P1)

A user who generates a grocery list from a meal plan wants to skip certain categories of ingredients because they always keep those items stocked at home. Before the list is finalized, the user is presented with an optional step to select one or more categories to exclude. Ingredients in those categories are not added to the generated list. If the user skips this step, all ingredients are included as before.

**Why this priority**: This is the core user-facing benefit described in the request. It prevents unwanted items from cluttering the grocery list and eliminates the need to manually delete them after generation. This directly improves the usability of the meal plan–to–grocery list workflow.

**Independent Test**: Can be fully tested by generating a grocery list from a meal plan with Pantry-category ingredients present, selecting "Pantry" as an excluded category, and verifying no Pantry items appear in the resulting list.

**Acceptance Scenarios**:

1. **Given** a user is generating a grocery list from a meal plan, **When** the user selects one or more categories to exclude (e.g., Pantry) and confirms generation, **Then** no grocery items in those categories appear in the resulting list.
2. **Given** a user is generating a grocery list from a meal plan, **When** the user skips the category exclusion step, **Then** all ingredients are added to the list as usual with no items excluded.
3. **Given** a user selects a category to exclude that contains some ingredients, **When** the list is generated, **Then** only the items in the excluded categories are omitted — all other items are present.
4. **Given** a user is regenerating an existing grocery list, **When** they select categories to exclude, **Then** previously generated items in those excluded categories are removed and any new ingredients in those categories are also excluded.
5. **Given** a grocery list was generated with one or more excluded categories, **When** the user views the list, **Then** a dismissible notice names the excluded categories and offers a direct action to regenerate the list with different exclusions.

---

### User Story 2 - Improve Ingredient Categorization Using Personal Templates (Priority: P2)

A user has previously added items to grocery lists manually and those items were saved with a category in their personal item templates. When a new grocery list is generated from a meal plan, the system uses the user's saved templates to assign a better category to recipe ingredients that are uncategorized (currently showing as "Other"). This improves the accuracy and organization of the generated grocery list without any extra effort from the user.

**Why this priority**: This enhances the quality of category assignments automatically, making the category exclusion feature (P1) more effective. An ingredient categorized as "Other" cannot be meaningfully excluded by category. This story has lower priority because the core exclusion flow works even if some items land in "Other."

**Independent Test**: Can be fully tested by ensuring a user has a personal item template for "cumin" with category "Pantry," then generating a grocery list from a meal plan containing a recipe with "cumin" as an ingredient that has no assigned category, and verifying the resulting grocery item appears under "Pantry."

**Acceptance Scenarios**:

1. **Given** a recipe ingredient has no assigned category (displays as "Other"), **And** the user has a personal item template for that ingredient name with a specific category, **When** a grocery list is generated, **Then** the grocery item inherits the category from the user's template rather than "Other."
2. **Given** a recipe ingredient has no assigned category and no matching user template exists, **And** a matching common item template exists for that ingredient name, **When** a grocery list is generated, **Then** the grocery item inherits the category from the common item template.
3. **Given** a recipe ingredient already has a specific category assigned (e.g., "Produce"), **When** a grocery list is generated, **Then** the ingredient's assigned category is used regardless of any user or common template.
4. **Given** a recipe ingredient has no assigned category and no matching template exists in either user or common templates, **When** a grocery list is generated, **Then** the item falls back to "Other" as before.

---

### User Story 3 - Save Category Exclusion Preferences for Future Generations (Priority: P3)

A user who always excludes the same categories (e.g., Pantry and Spices) when generating grocery lists can save those selections as their default preference. The next time they generate a list, the excluded categories are pre-selected, reducing repetitive configuration.

**Why this priority**: This is a convenience improvement that reduces friction for repeat users, but the feature delivers full value without it. The exclusion step itself (P1) works on a per-generation basis. Saved preferences are an enhancement.

**Independent Test**: Can be fully tested by saving "Pantry" as an excluded category preference, then generating a new grocery list and verifying "Pantry" is pre-selected in the exclusion step without any manual selection.

**Acceptance Scenarios**:

1. **Given** a user selects categories to exclude and chooses to save them as defaults, **When** they next generate a grocery list, **Then** the saved excluded categories are pre-selected in the exclusion step.
2. **Given** a user has saved default exclusions, **When** they generate a grocery list and modify the exclusions for that generation, **Then** the per-generation selection is used without overwriting the saved defaults.
3. **Given** a user has saved default exclusions, **When** they choose to clear their saved preferences, **Then** no categories are pre-selected on future generations.

---

### Edge Cases

- What happens when all ingredients in a meal plan belong to excluded categories? The list is generated empty; the user sees a message indicating no items remain after category filtering, and they can regenerate without exclusions.
- What happens when an ingredient name in a user template matches an ingredient with the same name but different casing in a recipe? Matching should be case-insensitive.
- What happens when a user template and an ingredient both have a category that is not "Other"? The ingredient's existing category takes precedence (templates only fill in missing/uncategorized items).
- What happens if the user regenerates a grocery list and their category exclusions have changed since the original generation? Only the categories selected at the time of regeneration are applied; the list is rebuilt according to the current exclusion selections.
- What happens to manually added items during regeneration if their category is now excluded? Manually added items are never removed during regeneration — the exclusion step only affects generated (recipe-derived) items.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: When generating a grocery list from a meal plan, users MUST be presented with an optional step to select one or more ingredient categories to exclude from the list.
- **FR-002**: The category exclusion step MUST be skippable — if the user skips it, all ingredients are included as in the current behavior.
- **FR-003**: When categories are excluded, the system MUST omit all generated grocery items in those categories from the resulting list.
- **FR-004**: During grocery list generation, the system MUST check the user's personal item templates for any recipe ingredient that has no assigned category (currently "Other"), and if a template match is found by ingredient name (case-insensitive), use the template's category for the generated item. If no user template match exists, the system MUST also check the shared common item template library as a secondary fallback using the same case-insensitive name match.
- **FR-005**: The template-based category lookup (user templates first, common templates second) MUST NOT override an ingredient's existing assigned category — it only fills in items categorized as "Other."
- **FR-006**: The category exclusion step MUST display all available ingredient categories with counts indicating how many ingredients from the current meal plan fall into each category.
- **FR-007**: When regenerating an existing grocery list, the category exclusion step MUST be available and the previously excluded categories SHOULD be pre-selected if saved preferences exist.
- **FR-008**: Manually added grocery items MUST NOT be removed when excluded categories are applied during regeneration.
- **FR-009**: If all generated items are excluded by category, the system MUST generate an empty list and inform the user that no items remain after filtering.
- **FR-010**: Users MUST be able to save their selected category exclusions as a default preference for future grocery list generations.
- **FR-011**: Saved category exclusion preferences MUST be editable and clearable directly from the grocery list generation page — no separate settings screen is required.
- **FR-012**: When viewing a grocery list that was generated with one or more excluded categories, the list MUST display a dismissible notice naming the excluded categories.
- **FR-013**: The excluded-category notice on the list view MUST provide a direct action to regenerate the list, allowing the user to adjust or remove category exclusions.

### Key Entities

- **Grocery List**: A shopping list linked to a meal plan or standalone. Stores the categories excluded at generation time and surfaces them to the user in the list view.
- **Grocery Item**: An individual item on a grocery list with a category, source type (generated/manual), and quantity.
- **Ingredient Category**: A classification applied to grocery items. Categories include: Produce, Dairy, Meat, Seafood, Pantry, Frozen, Bakery, Deli, Beverages, Other.
- **User Item Template**: A user's personal item history record storing item name, preferred category, unit, and quantity. Used as the primary fallback category source during grocery list generation.
- **Common Item Template**: A shared, system-wide ingredient library with category assignments. Used as a secondary fallback for categorization when no matching user item template exists.
- **Category Exclusion Preference**: A user-level saved setting indicating which ingredient categories to pre-exclude when generating grocery lists.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Users can complete the grocery list generation flow — including selecting category exclusions — in the same number of steps or fewer than the current flow.
- **SC-002**: 100% of generated grocery items that were previously "Other" but have a matching user item template are assigned the correct category from that template.
- **SC-003**: Zero manually added items are removed from an existing grocery list when categories are excluded during regeneration.
- **SC-004**: Users with saved category preferences see those categories pre-selected on their next grocery list generation without any additional configuration.
- **SC-005**: When a user excludes one or more categories and generates a list, zero items from excluded categories appear in the resulting grocery list.

## Clarifications

### Session 2026-02-22

- Q: Should `CommonItemTemplate` also be checked as a secondary fallback for ingredient categorization (after `UserItemTemplate`) for recipe ingredients with no assigned category? → A: Yes — check UserItemTemplate first, then CommonItemTemplate as fallback.
- Q: Should the grocery list view display a notice indicating which categories were excluded during generation? → A: Yes — show a dismissible notice naming excluded categories with a direct action to regenerate.
- Q: Where should the UI for managing saved category exclusion preferences be accessible? → A: On the grocery list generation page only — no separate settings screen.

## Assumptions

- The existing ingredient categories (Produce, Dairy, Meat, Seafood, Pantry, Frozen, Bakery, Deli, Beverages, Other) are sufficient for this feature. No new categories (e.g., a dedicated "Spices" category) are added as part of this feature. Users wishing to exclude spices would select the "Pantry" category, as spices are typically categorized there.
- Category exclusion preferences are stored per user, not per meal plan. The same preferences apply regardless of which meal plan is being used to generate a list.
- Template-based category lookup applies only at generation time; it does not retroactively update existing grocery items or the ingredient master records.
- The category exclusion step and preference management are both presented on the grocery list generation page — no separate settings screen is introduced for this feature.
- Ingredient name matching between recipe ingredients and user templates is case-insensitive and exact (not partial/fuzzy).
