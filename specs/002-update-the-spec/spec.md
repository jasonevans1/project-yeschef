# Feature Specification: Manual Grocery List Item Management

**Feature Branch**: `002-update-the-spec`
**Created**: 2025-10-10
**Status**: Draft
**Input**: User description: "Update the spec.md so the requirements for grocery lists allow a user to manually create,update or delete items from a grocery list."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Manually Add Items to Generated Grocery List (Priority: P1)

A user has generated a grocery list from their meal plan but realizes they need additional items not included in their planned recipes (e.g., household staples, snacks, beverages). They can manually add new items to the existing grocery list by specifying the item name, quantity, unit, and category.

**Why this priority**: This is the most critical enhancement because generated grocery lists are rarely complete. Users always need to add pantry staples, household items, or special occasion items. Without this capability, the grocery list is only partially useful.

**Independent Test**: Can be fully tested by generating a grocery list from a meal plan, manually adding 2-3 new items with different categories, and verifying they appear in the list alongside generated items. Delivers immediate value by making grocery lists comprehensive.

**Acceptance Scenarios**:

1. **Given** a user is viewing a grocery list, **When** they select "Add Item", **Then** they see a form to enter item name, quantity, unit, and category
2. **Given** a user has entered item details, **When** they save the new item, **Then** it appears in the grocery list under the appropriate category
3. **Given** a user adds a manually created item, **When** they regenerate the grocery list from the meal plan, **Then** the manually added item remains in the list
4. **Given** a user is adding an item without specifying a category, **When** they save the item, **Then** the system assigns it to a default category (e.g., "Other" or "Miscellaneous")
5. **Given** a user is adding an item, **When** they provide only a name without quantity/unit, **Then** the system allows saving with optional quantity/unit fields

---

### User Story 2 - Edit Existing Grocery List Items (Priority: P1)

A user reviewing their grocery list notices an item needs adjustment - perhaps the quantity is wrong, they want a different brand specification in the name, or the category needs correction. They can edit any item in the grocery list, whether it was generated from recipes or manually added.

**Why this priority**: Users need to adjust quantities based on household inventory, preferences, or sales. Being unable to modify items makes the list rigid and less useful. This is essential for grocery list practicality.

**Independent Test**: Can be fully tested by creating or generating a grocery list, editing an item's name, quantity, and category, then verifying changes persist. Delivers value by allowing grocery list customization to real-world needs.

**Acceptance Scenarios**:

1. **Given** a user is viewing a grocery list, **When** they select an item to edit, **Then** they see a form pre-filled with the current item details (name, quantity, unit, category)
2. **Given** a user has modified item details, **When** they save changes, **Then** the item updates in the grocery list with the new values
3. **Given** a user edits a generated item, **When** they regenerate the grocery list from the meal plan, **Then** the system preserves manual edits to that item or prompts the user about conflicts
4. **Given** a user edits an item's quantity, **When** they save with an invalid value (negative number, non-numeric text), **Then** the system displays a validation error
5. **Given** a user edits an item's category, **When** they change it to a different category, **Then** the item moves to the new category section in the list

---

### User Story 3 - Delete Items from Grocery List (Priority: P1)

A user reviewing their grocery list realizes some items are unnecessary - they already have them at home, they've changed their mind about a recipe, or an item was added by mistake. They can remove individual items from the grocery list without affecting the source meal plan.

**Why this priority**: Users need flexibility to remove items they already own or no longer need. Without deletion capability, the list becomes cluttered with unnecessary items, reducing its usefulness while shopping.

**Independent Test**: Can be fully tested by creating a grocery list with multiple items, deleting specific items, and verifying they're removed while others remain. Delivers value by keeping grocery lists relevant and practical.

**Acceptance Scenarios**:

1. **Given** a user is viewing a grocery list, **When** they select an item and choose to delete it, **Then** the item is removed from the list
2. **Given** a user deletes a manually added item, **When** they regenerate the grocery list, **Then** the deleted item remains gone (not re-added)
3. **Given** a user deletes a generated item, **When** they regenerate the grocery list from the same meal plan, **Then** the system either re-adds it or remembers the deletion preference
4. **Given** a user is about to delete an item, **When** they trigger deletion, **Then** the system may optionally request confirmation for destructive actions
5. **Given** a user has deleted all items from a category, **When** they view the grocery list, **Then** the empty category section is either hidden or shows an empty state

---

### User Story 4 - Create Grocery List Without Meal Plan (Priority: P2)

A user wants to create a shopping list for items not related to meal planning - perhaps for a party, holiday baking, or general household shopping. They can create a new standalone grocery list without linking it to any meal plan, then manually add all items.

**Why this priority**: This extends the grocery list feature beyond meal planning to a general shopping list tool. It's valuable but not essential since the primary use case is meal-plan-generated lists. Users can work around this by creating an empty meal plan.

**Independent Test**: Can be fully tested by creating a new blank grocery list, adding multiple items manually, and verifying it behaves like any other grocery list (mark items, edit, delete). Delivers value by making the tool more versatile.

**Acceptance Scenarios**:

1. **Given** a user is on the grocery lists page, **When** they select "Create New List", **Then** they can create a blank grocery list with a name/title without requiring a meal plan
2. **Given** a user has created a standalone grocery list, **When** they add items manually, **Then** it functions identically to a meal-plan-generated list (mark as purchased, edit, delete)
3. **Given** a user has multiple grocery lists (some from meal plans, some standalone), **When** they view their lists, **Then** they can distinguish between meal-plan-linked and standalone lists
4. **Given** a user has a standalone grocery list, **When** they view it, **Then** there is no option to regenerate from meal plan (since there is no source meal plan)

---

### Edge Cases

- What happens when a user manually adds an item with the same name as a generated item in the same list? (System should allow duplicates or offer to merge/adjust quantities)
- What happens when a user edits a generated item and then regenerates the list from the meal plan? (System should preserve manual edits or show conflict resolution UI)
- What happens when a user deletes a generated item and then regenerates the list? (System should remember deletion preference or warn user about re-adding)
- How does the system handle items added without units or quantities? (Allow optional fields, display gracefully in list)
- What happens when a user creates a standalone grocery list with no items? (Allow empty lists, show helpful empty state)
- What happens when a user tries to edit/delete an item from a read-only shared grocery list? (Prevent modifications, show appropriate permission message)
- How does the system handle very long item names or quantities with many decimal places?
- What happens when a user adds duplicate items manually with slight name variations (e.g., "Milk" vs "milk" vs "Whole Milk")? (System should allow but may suggest duplicates)
- What happens when a user has marked items as purchased and then edits or deletes them? (Maintain purchased status or clear it upon edit)

## Requirements *(mandatory)*

### Functional Requirements

**Manual Grocery List Item Management**:
- **FR-034**: Users MUST be able to manually add new items to any grocery list by specifying item name (required), quantity (optional), unit (optional), and category (optional with default)
- **FR-035**: Users MUST be able to edit any item in a grocery list, changing the name, quantity, unit, or category
- **FR-036**: Users MUST be able to delete any item from a grocery list (both generated and manually added items)
- **FR-037**: System MUST distinguish between generated items (from meal plan recipes) and manually added items in the data model
- **FR-038**: System MUST preserve manually added items when regenerating a grocery list from its source meal plan
- **FR-039**: System MUST handle edited generated items when regenerating by either preserving edits or providing conflict resolution UI
- **FR-040**: System MUST track deleted generated items when regenerating to avoid re-adding items the user explicitly removed

**Standalone Grocery Lists**:
- **FR-041**: Users MUST be able to create a new grocery list without linking it to a meal plan
- **FR-042**: System MUST allow users to name/title standalone grocery lists
- **FR-043**: System MUST distinguish between meal-plan-linked and standalone grocery lists in the interface
- **FR-044**: Standalone grocery lists MUST support all the same operations as generated lists (add, edit, delete, mark as purchased)

**Data Validation**:
- **FR-045**: System MUST validate that item names are not empty when creating or editing items
- **FR-046**: System MUST validate that quantities (when provided) are positive numbers
- **FR-047**: System MUST allow items to be saved without quantity or unit specified (optional fields)
- **FR-048**: System MUST prevent users from editing or deleting items in grocery lists they do not own

### Key Entities *(include if feature involves data)*

Updates to existing entities from spec 001:

- **Grocery List**: Add optional relationship to meal plan (nullable - can exist without a meal plan). Add name/title attribute for standalone lists.

- **Grocery Item**: Add "source type" attribute to distinguish between generated (from recipe) and manually added items. Add "original values" tracking for generated items that have been manually edited. Add "deleted at" soft delete timestamp for tracking user-deleted generated items during regeneration.

- **User**: Retains ownership of grocery lists (both standalone and meal-plan-linked).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-013**: Users can manually add an item to a grocery list in under 15 seconds
- **SC-014**: Users can edit an existing grocery list item in under 20 seconds
- **SC-015**: Users can delete an item from a grocery list in under 5 seconds
- **SC-016**: 95% of users successfully add their first manual item to a grocery list without validation errors
- **SC-017**: System preserves manually added items through meal plan regeneration 100% of the time
- **SC-018**: Users can create a standalone grocery list with 10 manually added items in under 3 minutes
- **SC-019**: Manual edits to grocery list items persist correctly 100% of the time (verified by test cases)
- **SC-020**: 90% of users understand the difference between meal-plan-linked and standalone grocery lists without explicit instructions

## Assumptions *(mandatory)*

- Users are authenticated and grocery lists are associated with individual user accounts (following existing authentication)
- Item categories follow the same category system established for recipe ingredients (produce, dairy, meat, etc.)
- Users typically add 5-10 additional manual items to generated grocery lists
- Manually added items follow the same data structure as generated items (name, quantity, unit, category)
- Regenerating a grocery list is an explicit user action (not automatic when meal plan changes)
- Users understand that deleting items from a grocery list does not affect the source meal plan or recipes
- Standard grocery item naming conventions apply (users enter "Milk" not product codes)
- Quantity units for manual items follow the same measurement standards as recipe ingredients
- Most users will edit generated lists rather than create standalone lists
- Item names are text strings with reasonable length limits (e.g., 255 characters)

## Non-Goals *(optional)*

- Automatic conflict resolution between manual edits and meal plan regeneration (will prompt user or preserve manual edits)
- AI-powered item suggestions or auto-complete for manual item entry
- Shared editing of grocery lists by multiple users simultaneously
- Template grocery lists or pre-filled item suggestions
- Item price tracking or cost estimation for manual items
- Barcode scanning for adding items to grocery lists
- Voice input for adding items
- Automatic duplicate detection and merging for similar item names
- Item history or frequently added items tracking
- Bulk import of items from external sources
- Integration with store inventory or product databases
- Custom category creation by users (will use existing predefined categories)

## Dependencies *(optional)*

- Existing grocery list functionality from Feature 001 (generate from meal plan, mark as purchased, export)
- Existing category system for ingredients
- User authentication and authorization system
- Existing Livewire 3 component architecture for forms and list views
