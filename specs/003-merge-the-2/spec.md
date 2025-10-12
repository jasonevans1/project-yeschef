# Feature Specification: Family Meal Planning Application with Grocery List Management

**Feature Branch**: `003-merge-the-2`
**Created**: 2025-10-10
**Status**: Draft
**Input**: User description: "Merge the 2 specs. 001-build-an-application and 002-update-the-spec. These should be a single spec to build a meal planning application."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Browse and View Recipes (Priority: P1)

A user wants to explore available recipes to find meals their family will enjoy. They can browse through the recipe database, view recipe details including ingredients, cooking instructions, preparation time, and nutritional information.

**Why this priority**: Without a recipe database that users can access and browse, meal planning cannot begin. This is the foundational capability that all other features depend on.

**Independent Test**: Can be fully tested by creating sample recipes in the database and verifying users can view the list and access recipe details. Delivers immediate value by allowing users to discover and save recipes.

**Acceptance Scenarios**:

1. **Given** a user is on the recipes page, **When** they view the recipe list, **Then** they see all available recipes with names, images, and brief descriptions
2. **Given** a user selects a recipe, **When** the recipe details page loads, **Then** they see the full recipe including ingredients list, step-by-step instructions, prep/cook times, and servings
3. **Given** a user is viewing recipes, **When** they filter or search by criteria (meal type, dietary restrictions, ingredients), **Then** they see only recipes matching their criteria

---

### User Story 2 - Create a Basic Meal Plan (Priority: P1)

A user wants to plan meals for their family for a specific time period. They can create a new meal plan by selecting a date range, then assign recipes to specific meals (breakfast, lunch, dinner) on specific days within that plan.

**Why this priority**: This is the core value proposition of the application. Users need to be able to create meal plans to solve their primary problem of organizing family meals.

**Independent Test**: Can be fully tested by creating a meal plan for a single day with one recipe and verifying it saves correctly. Delivers immediate value by allowing users to organize meals even for just one day.

**Acceptance Scenarios**:

1. **Given** a user wants to plan meals, **When** they create a new meal plan and specify start/end dates, **Then** a new meal plan is created covering that date range
2. **Given** a user has an active meal plan, **When** they select a day and meal slot (breakfast/lunch/dinner), **Then** they can assign a recipe from the database to that slot
3. **Given** a user has assigned recipes to meal slots, **When** they view their meal plan calendar, **Then** they see all assigned meals organized by day and meal type
4. **Given** a user has a meal plan, **When** they remove a recipe from a meal slot, **Then** that slot becomes available for reassignment
5. **Given** a user has multiple meal plans, **When** they view their meal plans list, **Then** they see all active and past meal plans with date ranges

---

### User Story 3 - Generate Grocery List from Meal Plan (Priority: P1)

A user with a completed meal plan wants to know what ingredients to buy. They can generate a grocery list that aggregates all ingredients from the recipes in their meal plan, combining quantities for duplicate ingredients.

**Why this priority**: This provides significant convenience and is a core feature expected from meal planning apps. It transforms the meal plan into actionable shopping guidance, making the application immediately practical.

**Independent Test**: Can be fully tested by creating a meal plan with 2-3 recipes, generating a grocery list, and verifying all ingredients appear with correct aggregated quantities. Delivers value by eliminating manual ingredient tracking.

**Acceptance Scenarios**:

1. **Given** a user has a meal plan with assigned recipes, **When** they request to generate a grocery list, **Then** the system creates a list of all unique ingredients with aggregated quantities
2. **Given** a grocery list has been generated, **When** the user views it, **Then** ingredients are organized by category (produce, dairy, meat, pantry, etc.)
3. **Given** a user is viewing their grocery list, **When** they mark items as purchased, **Then** those items are visually indicated as completed
4. **Given** a user has generated a grocery list, **When** they modify their meal plan (add/remove recipes), **Then** they can regenerate the list to reflect the changes

---

### User Story 4 - Manually Manage Grocery List Items (Priority: P1)

A user has generated a grocery list from their meal plan but realizes they need additional items not included in their planned recipes (e.g., household staples, snacks, beverages), or they need to adjust quantities of existing items based on what they have at home. They can manually add new items to the grocery list, edit existing items (changing name, quantity, unit, or category), and delete unnecessary items.

**Why this priority**: Generated grocery lists are rarely complete. Users always need to add pantry staples, household items, or special occasion items. They also need to adjust quantities based on household inventory or preferences. Without these capabilities, the grocery list is only partially useful and inflexible.

**Independent Test**: Can be fully tested by generating a grocery list, manually adding new items, editing existing item quantities, and deleting unwanted items, then verifying all changes persist correctly. Delivers immediate value by making grocery lists comprehensive and practical.

**Acceptance Scenarios**:

1. **Given** a user is viewing a grocery list, **When** they select "Add Item", **Then** they see a form to enter item name, quantity, unit, and category
2. **Given** a user has entered item details, **When** they save the new item, **Then** it appears in the grocery list under the appropriate category
3. **Given** a user adds a manually created item, **When** they regenerate the grocery list from the meal plan, **Then** the manually added item remains in the list
4. **Given** a user is viewing a grocery list, **When** they select an item to edit, **Then** they see a form pre-filled with the current item details (name, quantity, unit, category)
5. **Given** a user has modified item details, **When** they save changes, **Then** the item updates in the grocery list with the new values
6. **Given** a user edits a generated item, **When** they regenerate the grocery list from the meal plan, **Then** the system preserves manual edits or provides conflict resolution UI
7. **Given** a user is viewing a grocery list, **When** they select an item and choose to delete it, **Then** the item is removed from the list
8. **Given** a user deletes a generated item, **When** they regenerate the grocery list from the same meal plan, **Then** the system remembers the deletion preference or provides conflict resolution UI
9. **Given** a user is adding an item without specifying a category, **When** they save the item, **Then** the system assigns it to a default category (e.g., "Miscellaneous")
10. **Given** a user is adding an item, **When** they provide only a name without quantity/unit, **Then** the system allows saving with optional quantity/unit fields

---

### User Story 5 - Create and Manage Personal Recipes (Priority: P2)

A user wants to add their own family recipes to the system alongside the existing recipe database. They can create new recipes by entering recipe details, ingredients with quantities, and step-by-step instructions. Once created, users can edit and delete their personal recipes, which are available for use in meal planning.

**Why this priority**: While the system recipe database is useful, families have favorite recipes they'll want to include. This enhances personalization but isn't required for basic meal planning functionality.

**Independent Test**: Can be fully tested by creating a new custom recipe, verifying it saves, appears in the recipe list, and can be used in meal plans. Delivers value by allowing recipe collection customization.

**Acceptance Scenarios**:

1. **Given** a user wants to create a new recipe, **When** they access the recipe creation form, **Then** they can enter recipe name, description, prep time, cook time, servings, and optional metadata (cuisine, difficulty, dietary tags)
2. **Given** a user is creating a recipe, **When** they add ingredients, **Then** they can specify ingredient name, quantity, unit of measurement, and category for each ingredient
3. **Given** a user is creating a recipe, **When** they add preparation instructions, **Then** they can enter multiple steps in sequential order with optional images
4. **Given** a user has completed all required recipe fields, **When** they save the recipe, **Then** the recipe is saved to their personal collection and appears in their recipe list
5. **Given** a user is creating a recipe with missing required fields, **When** they attempt to save, **Then** the system displays validation errors indicating which fields are incomplete
6. **Given** a user has personal recipes, **When** they browse recipes, **Then** they see both system recipes and their personal recipes (with visual distinction)
7. **Given** a user selects their own recipe, **When** they choose to edit it, **Then** they can modify any recipe details (name, ingredients, instructions, metadata) and save changes
8. **Given** a user has a personal recipe, **When** they delete it, **Then** it is removed from their collection (but preserved in existing meal plans where already assigned)
9. **Given** a user has created a personal recipe, **When** they view it in the recipe list, **Then** it is clearly marked as their own recipe (e.g., "My Recipe" badge or icon)

---

### User Story 6 - Create Standalone Grocery Lists (Priority: P2)

A user wants to create a shopping list for items not related to meal planning - perhaps for a party, holiday baking, or general household shopping. They can create a new standalone grocery list without linking it to any meal plan, then manually add all items.

**Why this priority**: This extends the grocery list feature beyond meal planning to a general shopping list tool. It's valuable but not essential since the primary use case is meal-plan-generated lists.

**Independent Test**: Can be fully tested by creating a new blank grocery list, adding multiple items manually, and verifying it behaves like any other grocery list (mark items, edit, delete). Delivers value by making the tool more versatile.

**Acceptance Scenarios**:

1. **Given** a user is on the grocery lists page, **When** they select "Create New List", **Then** they can create a blank grocery list with a name/title without requiring a meal plan
2. **Given** a user has created a standalone grocery list, **When** they add items manually, **Then** it functions identically to a meal-plan-generated list (mark as purchased, edit, delete)
3. **Given** a user has multiple grocery lists (some from meal plans, some standalone), **When** they view their lists, **Then** they can distinguish between meal-plan-linked and standalone lists
4. **Given** a user has a standalone grocery list, **When** they view it, **Then** there is no option to regenerate from meal plan (since there is no source meal plan)

---

### User Story 7 - Adjust Meal Plan for Household Size (Priority: P3)

A user cooking for a different number of people than a recipe's default servings wants to adjust quantities. They can specify serving size adjustments for their meal plan, and the system scales ingredient quantities proportionally.

**Why this priority**: This is a convenience feature that enhances usability but isn't essential for basic meal planning functionality. Manual calculation is possible as a workaround.

**Independent Test**: Can be fully tested by creating a meal plan, adjusting serving sizes, and verifying ingredient quantities scale correctly in the grocery list. Delivers value by automating portion calculations.

**Acceptance Scenarios**:

1. **Given** a user is creating a meal plan, **When** they specify the number of servings needed for the household, **Then** the system stores this preference
2. **Given** a recipe serves 4 and the user needs 6 servings, **When** they add it to a meal plan, **Then** all ingredient quantities are scaled by 1.5x
3. **Given** a user has a meal plan with scaled recipes, **When** they generate a grocery list, **Then** all quantities reflect the adjusted serving sizes

---

### User Story 8 - Export and Share Grocery Lists (Priority: P3)

A user wants to use their grocery list while shopping or share it with family members. They can export the grocery list to common formats (PDF, text, email) or share it via a link.

**Why this priority**: This is a quality-of-life enhancement that improves the shopping experience but users can manually copy lists as needed. Nice to have but not critical.

**Independent Test**: Can be fully tested by generating a grocery list and verifying export functionality produces readable, formatted output. Delivers value by making lists portable.

**Acceptance Scenarios**:

1. **Given** a user has generated a grocery list, **When** they choose to export it, **Then** they can download it as a PDF or plain text file
2. **Given** a user wants to share their list, **When** they generate a shareable link, **Then** family members can view (but not edit) the list via the link (requires authentication)
3. **Given** a user exports a grocery list, **When** they open the exported file, **Then** items are clearly formatted with quantities, organized by category

---

### Edge Cases

- What happens when a user deletes a recipe that is already assigned to an active meal plan? (System should preserve the recipe data within that meal plan or warn the user)
- How does the system handle recipes with unusual measurements (pinch, dash, to taste) when scaling servings?
- What happens when a user creates a meal plan spanning weeks but only assigns recipes to a few days? (Allow partial plans, don't require complete coverage)
- How does the system handle duplicate recipes assigned to different meals on the same day?
- What happens when generating a grocery list from a meal plan with no assigned recipes? (Show empty state with guidance)
- How does the system handle concurrent edits to the same meal plan by multiple household members?
- What happens when a user tries to create overlapping meal plans for the same date range?
- How are fractional ingredient quantities displayed in grocery lists (e.g., 1.33 cups)?
- What happens when a user creates a recipe without any ingredients? (Should system allow it or require at least one ingredient?)
- How does the system handle duplicate ingredient names within the same recipe (e.g., "salt" appears twice in different steps)?
- What happens when a user tries to create a recipe with an identical name to one they already created?
- How does the system handle very long recipe instructions (e.g., 50+ steps)?
- What happens when a user manually adds an item with the same name as a generated item in the same grocery list? (System should allow duplicates or offer to merge/adjust quantities)
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

**Recipe Management**:
- **FR-001**: System MUST maintain a database of recipes accessible to all users
- **FR-002**: System MUST display recipe details including name, description, ingredients list with quantities, preparation instructions, prep time, cook time, total time, servings, and optional fields (difficulty level, cuisine type, dietary tags, nutritional information)
- **FR-003**: Users MUST be able to search and filter recipes by name, ingredients, meal type, dietary restrictions, cuisine type, and preparation time
- **FR-004**: Users MUST be able to create their own personal recipes with the same data fields as system recipes
- **FR-005**: Users MUST be able to edit and delete only their own personal recipes
- **FR-006**: System MUST distinguish between system-provided recipes and user-created recipes in the interface

**Meal Planning**:
- **FR-007**: Users MUST be able to create a new meal plan by specifying a start date and end date
- **FR-008**: System MUST support meal plans ranging from a single day to multiple weeks
- **FR-009**: Users MUST be able to assign recipes to specific meal slots (breakfast, lunch, dinner, snacks) for each day in the meal plan
- **FR-010**: Users MUST be able to remove recipes from meal slots without deleting the entire meal plan
- **FR-011**: Users MUST be able to view their meal plan in a calendar format showing all assigned meals
- **FR-012**: Users MUST be able to create multiple meal plans (past, current, and future)
- **FR-013**: Users MUST be able to edit meal plans by adding or removing recipe assignments
- **FR-014**: Users MUST be able to delete entire meal plans
- **FR-015**: System MUST allow partial meal plans where not all days or meals have assigned recipes

**Grocery List Generation**:
- **FR-016**: Users MUST be able to generate a grocery list from any meal plan
- **FR-017**: System MUST aggregate duplicate ingredients across all recipes in a meal plan, combining quantities with proper unit conversion (e.g., 2 cups + 1 pint)
- **FR-018**: System MUST organize grocery list items by category (produce, dairy, meat, seafood, pantry, frozen, bakery, etc.)
- **FR-019**: Users MUST be able to mark individual grocery list items as purchased/completed
- **FR-020**: Users MUST be able to regenerate a grocery list after modifying the source meal plan
- **FR-021**: System MUST preserve manually marked items when regenerating a grocery list from the same meal plan

**Manual Grocery List Item Management**:
- **FR-022**: Users MUST be able to manually add new items to any grocery list by specifying item name (required), quantity (optional), unit (optional), and category (optional with default)
- **FR-023**: Users MUST be able to edit any item in a grocery list, changing the name, quantity, unit, or category
- **FR-024**: Users MUST be able to delete any item from a grocery list (both generated and manually added items)
- **FR-025**: System MUST distinguish between generated items (from meal plan recipes) and manually added items in the data model
- **FR-026**: System MUST preserve manually added items when regenerating a grocery list from its source meal plan
- **FR-027**: System MUST handle edited generated items when regenerating by either preserving edits or providing conflict resolution UI
- **FR-028**: System MUST track deleted generated items when regenerating to avoid re-adding items the user explicitly removed

**Standalone Grocery Lists**:
- **FR-029**: Users MUST be able to create a new grocery list without linking it to a meal plan
- **FR-030**: System MUST allow users to name/title standalone grocery lists
- **FR-031**: System MUST distinguish between meal-plan-linked and standalone grocery lists in the interface
- **FR-032**: Standalone grocery lists MUST support all the same operations as generated lists (add, edit, delete, mark as purchased)

**Serving Size Adjustments**:
- **FR-033**: Users MUST be able to specify a serving size adjustment when adding a recipe to a meal plan
- **FR-034**: System MUST scale all ingredient quantities proportionally based on the serving size adjustment
- **FR-035**: System MUST display the adjusted serving size and original serving size in meal plan views
- **FR-036**: System MUST use scaled quantities when generating grocery lists

**Data Management**:
- **FR-037**: System MUST associate recipes with users who created them (for personal recipes)
- **FR-038**: System MUST associate meal plans with the user who created them
- **FR-039**: System MUST associate grocery lists with their source meal plan (if applicable) and owner
- **FR-040**: System MUST prevent users from accessing or modifying other users' personal recipes, meal plans, and grocery lists
- **FR-041**: System MUST persist all user data (recipes, meal plans, grocery lists) reliably

**Data Validation**:
- **FR-042**: System MUST validate that item names are not empty when creating or editing grocery list items
- **FR-043**: System MUST validate that quantities (when provided) are positive numbers
- **FR-044**: System MUST allow grocery list items to be saved without quantity or unit specified (optional fields)
- **FR-045**: System MUST prevent users from editing or deleting items in grocery lists they do not own

**Export and Sharing**:
- **FR-046**: Users MUST be able to export grocery lists as PDF files
- **FR-047**: Users MUST be able to export grocery lists as plain text files
- **FR-048**: Users MUST be able to generate a shareable read-only link for their grocery lists that requires recipients to be authenticated users (must be logged in to view)

### Key Entities

- **Recipe**: Represents a dish with ingredients and cooking instructions. Attributes include name, description, ingredients (with quantities and units), step-by-step instructions, prep time, cook time, servings, meal type, cuisine, dietary tags, difficulty, nutritional information. May be system-provided or user-created.

- **Ingredient**: Represents a food item used in recipes. Attributes include name, quantity, unit of measurement, category (produce, dairy, etc.). Related to recipes through a many-to-many relationship (recipes can have many ingredients, ingredients appear in many recipes).

- **Meal Plan**: Represents a user's planned meals for a date range. Attributes include name/title, start date, end date, created date, owner. Contains multiple meal assignments.

- **Meal Assignment**: Represents a recipe assigned to a specific meal slot. Attributes include date, meal type (breakfast/lunch/dinner/snack), assigned recipe, serving size multiplier. Belongs to a meal plan.

- **Grocery List**: Represents a shopping list either generated from a meal plan or created standalone. Attributes include name/title, generation date, optional source meal plan (nullable), owner, completed status. Contains multiple grocery items.

- **Grocery Item**: Represents an ingredient item on a grocery list. Attributes include ingredient name, quantity, unit, category, purchased status, source type (generated from recipe or manually added), original values (for tracking manual edits to generated items), soft delete timestamp (for tracking user-deleted generated items during regeneration). Belongs to a grocery list, may reference multiple recipe ingredients that were aggregated.

- **User**: Represents a person using the system. Has ownership relationships with personal recipes, meal plans, and grocery lists.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Users can create a meal plan for one week (7 days) with assigned recipes in under 5 minutes
- **SC-002**: Users can generate a grocery list from a meal plan in under 10 seconds
- **SC-003**: System correctly aggregates duplicate ingredients across recipes 100% of the time (verified by test cases)
- **SC-004**: Users can find relevant recipes through search in under 3 clicks/interactions
- **SC-005**: 90% of users successfully complete their first meal plan creation without assistance or errors
- **SC-006**: Generated grocery lists group all items into appropriate categories with 95% accuracy
- **SC-007**: Ingredient quantity scaling for different serving sizes maintains mathematical accuracy within 0.1 units
- **SC-008**: System supports meal plans up to 4 weeks (28 days) without performance degradation
- **SC-009**: Users can export a grocery list in under 5 seconds
- **SC-010**: Mobile and desktop users can complete all core workflows (browse recipes, create meal plan, generate grocery list) with equivalent functionality
- **SC-011**: Users can create a new personal recipe with 5-10 ingredients in under 3 minutes
- **SC-012**: 85% of users successfully create their first personal recipe without validation errors on first submission
- **SC-013**: Users can manually add an item to a grocery list in under 15 seconds
- **SC-014**: Users can edit an existing grocery list item in under 20 seconds
- **SC-015**: Users can delete an item from a grocery list in under 5 seconds
- **SC-016**: 95% of users successfully add their first manual item to a grocery list without validation errors
- **SC-017**: System preserves manually added items through meal plan regeneration 100% of the time
- **SC-018**: Users can create a standalone grocery list with 10 manually added items in under 3 minutes
- **SC-019**: Manual edits to grocery list items persist correctly 100% of the time (verified by test cases)
- **SC-020**: 90% of users understand the difference between meal-plan-linked and standalone grocery lists without explicit instructions

## Assumptions *(mandatory)*

- Users are authenticated and have individual accounts (following the existing Laravel Fortify authentication system)
- Recipe database will be seeded with an initial collection of system recipes by administrators
- Users plan meals for a single household (no multi-household management required)
- Ingredient quantity units follow standard cooking measurements (cups, tablespoons, teaspoons, ounces, pounds, grams, etc.)
- Unit conversions follow standard culinary conversion rates (e.g., 2 cups = 1 pint)
- Grocery list categories follow common supermarket organization
- Users have basic familiarity with meal planning concepts (breakfast, lunch, dinner)
- Internet connectivity is available when using the application
- Users access the application through web browsers (following the existing Livewire/Laravel architecture)
- Recipe ingredient quantities can be scaled linearly (no complex scaling logic for non-linear ingredients like yeast)
- Date ranges are in the user's local timezone
- A "meal slot" represents one meal occasion (breakfast, lunch, dinner, or snack) on one specific day
- Users manage their own meal plans independently (no collaborative editing required in initial version)
- Item categories for grocery lists follow the same category system established for recipe ingredients (produce, dairy, meat, etc.)
- Users typically add 5-10 additional manual items to generated grocery lists
- Manually added items follow the same data structure as generated items (name, quantity, unit, category)
- Regenerating a grocery list is an explicit user action (not automatic when meal plan changes)
- Users understand that deleting items from a grocery list does not affect the source meal plan or recipes
- Standard grocery item naming conventions apply (users enter "Milk" not product codes)
- Quantity units for manual items follow the same measurement standards as recipe ingredients
- Most users will edit generated lists rather than create standalone lists
- Item names are text strings with reasonable length limits (e.g., 255 characters)

## Non-Goals *(optional)*

- Real-time collaborative meal planning (multiple users editing the same plan simultaneously)
- Integration with online grocery delivery services or ordering APIs
- Calorie tracking or detailed nutritional analysis dashboards
- Meal plan recommendations or AI-suggested recipes
- Social features (sharing recipes with other users, rating/reviewing recipes)
- Meal preparation scheduling or cooking timers
- Inventory management (tracking what ingredients users already have at home)
- Budget tracking or cost estimation for grocery lists
- Barcode scanning for grocery items
- Mobile native applications (iOS/Android apps)
- Offline functionality
- Recipe video integration
- Meal plan templates or pre-built meal plans
- Integration with fitness or health tracking apps
- Automatic grocery list optimization by store layout
- Automatic conflict resolution between manual edits and meal plan regeneration (will prompt user or preserve manual edits)
- AI-powered item suggestions or auto-complete for manual item entry
- Shared editing of grocery lists by multiple users simultaneously
- Template grocery lists or pre-filled item suggestions
- Item price tracking or cost estimation for manual items
- Voice input for adding items
- Automatic duplicate detection and merging for similar item names
- Item history or frequently added items tracking
- Bulk import of items from external sources
- Integration with store inventory or product databases
- Custom category creation by users (will use existing predefined categories)

## Dependencies *(optional)*

- Existing Laravel 12 application framework with authentication (Laravel Fortify)
- Existing user authentication system and user accounts
- Database infrastructure (SQLite for dev/test, MariaDB via DDEV)
- Livewire 3 for interactive UI components

## Open Questions *(optional)*

- Should the system support recipe versioning if a user edits a recipe that's already in an old meal plan?
- Should meal plans automatically archive or expire after their end date has passed?
- What level of detail is needed for nutritional information in recipes (optional vs required fields)?
- Should users be able to copy/clone existing meal plans to quickly create similar plans?
- Should the system support meal notes or special instructions at the meal plan level (e.g., "dinner party on Friday")?
- How should the system handle conflict resolution when a user edits a generated grocery item and then regenerates the list - preserve edits, show diff, or allow user to choose?
- Should deleted generated items be permanently removed from regeneration memory after a certain time period or when the meal plan is deleted?
