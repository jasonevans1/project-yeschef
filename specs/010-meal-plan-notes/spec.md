# Feature Specification: Meal Plan Notes

**Feature Branch**: `010-meal-plan-notes`
**Created**: 2026-01-11
**Status**: Draft
**Input**: User description: "When adding a recipe to a meal plan add option to add a note instead of a recipe. When clicking on the add note button it will open a form with a title and details fields. This note will be displayed as an item on the meal plan view and can be edited or deleted from the meal plan. It will not be used when generating the grocery list."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Add Note to Meal Plan (Priority: P1)

As a user planning my meals, I want to add a free-form note to a specific date and meal slot in my meal plan, so that I can record meal-related information that isn't tied to a specific recipe (e.g., "Eating out at Mom's house", "Leftovers from yesterday", "Fasting day", "Order pizza").

**Why this priority**: This is the core functionality of the feature. Without the ability to add notes, the entire feature has no value. Users need flexibility beyond just recipe assignments.

**Independent Test**: Can be fully tested by adding a note with title and details to a meal slot and verifying it appears in the meal plan calendar view.

**Acceptance Scenarios**:

1. **Given** I am viewing a meal plan, **When** I click on an empty meal slot, **Then** I see options to either "Add Recipe" or "Add Note"
2. **Given** I am viewing a meal plan with a meal slot that already has a recipe, **When** I click the "Add Another" button, **Then** I see options to either "Add Recipe" or "Add Note"
3. **Given** I have clicked "Add Note", **When** the note form opens, **Then** I see a form with "Title" (required) and "Details" (optional) fields
4. **Given** I am in the add note form, **When** I fill in the title and optionally the details and submit, **Then** the note is saved and displayed in the meal plan calendar at the selected date and meal type
5. **Given** I am in the add note form with an empty title, **When** I try to submit, **Then** the form shows a validation error requiring the title field

---

### User Story 2 - View Note in Meal Plan (Priority: P1)

As a user viewing my meal plan, I want to see notes displayed alongside recipes in the calendar grid, so that I can see all my meal-related plans at a glance.

**Why this priority**: Viewing notes is essential for the feature to be useful. Users must be able to see what they've added.

**Independent Test**: Can be tested by creating a note and verifying it displays correctly in the meal plan calendar with its title visible.

**Acceptance Scenarios**:

1. **Given** I have a meal plan with notes added, **When** I view the meal plan, **Then** I see notes displayed in their respective date/meal type slots with their title visible
2. **Given** a meal slot has both recipes and notes, **When** I view that slot, **Then** both recipes and notes are displayed, visually distinguishable from each other
3. **Given** I am viewing a note in the meal plan, **When** I look at it, **Then** I can clearly identify it as a note (not a recipe) through visual styling

---

### User Story 3 - Edit Existing Note (Priority: P2)

As a user who has added a note to my meal plan, I want to edit the note's title and details, so that I can update my plans as circumstances change.

**Why this priority**: Editing is important for usability but not strictly required for initial functionality. Users can delete and recreate notes as a workaround.

**Independent Test**: Can be tested by editing an existing note and verifying the changes are saved and displayed.

**Acceptance Scenarios**:

1. **Given** I am viewing a meal plan with a note, **When** I click on the note, **Then** a drawer or modal opens showing the note details with an edit option
2. **Given** I am viewing a note's details, **When** I click the edit button, **Then** I can modify the title and details fields
3. **Given** I am editing a note, **When** I save my changes, **Then** the updated note is displayed in the meal plan
4. **Given** I am editing a note and clear the title, **When** I try to save, **Then** validation prevents saving with an empty title

---

### User Story 4 - Delete Note from Meal Plan (Priority: P2)

As a user who has added a note to my meal plan, I want to delete the note, so that I can remove outdated or incorrect information.

**Why this priority**: Deletion is essential for managing content but secondary to adding and viewing notes.

**Independent Test**: Can be tested by deleting a note and verifying it no longer appears in the meal plan.

**Acceptance Scenarios**:

1. **Given** I am viewing a note in the meal plan calendar, **When** I hover over the note, **Then** I see a delete button (consistent with recipe deletion UX)
2. **Given** I click the delete button on a note, **When** confirming the action, **Then** the note is removed from the meal plan
3. **Given** I have deleted a note, **When** I view the meal plan, **Then** the note no longer appears in that meal slot

---

### User Story 5 - Notes Excluded from Grocery List (Priority: P1)

As a user generating a grocery list from my meal plan, I want notes to be excluded from the grocery list generation, so that only actual recipes contribute ingredients to my shopping list.

**Why this priority**: This is a critical business rule. Including notes in grocery generation would cause errors or confusion since notes have no ingredients.

**Independent Test**: Can be tested by creating a meal plan with both recipes and notes, generating a grocery list, and verifying only recipe ingredients appear.

**Acceptance Scenarios**:

1. **Given** I have a meal plan with recipes and notes, **When** I generate a grocery list, **Then** only ingredients from recipes are included
2. **Given** I have a meal plan with only notes (no recipes), **When** I generate a grocery list, **Then** the grocery list is empty or a message indicates no recipes to generate from

---

### Edge Cases

- What happens when a note has a very long title? Title should be truncated in the calendar view with full title visible in the detail drawer.
- What happens when a note has very long details? Details should scroll or truncate appropriately in the detail view.
- How does a note appear on mobile/smaller screens? Notes should be responsive and maintain readability on all screen sizes.
- What happens if a user tries to add both a recipe and a note at the same time? Each action is independent; users add one item at a time.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST provide an "Add Note" option alongside the existing "Add Recipe" option when adding items to a meal slot
- **FR-002**: System MUST display a form with "Title" (required, max 255 characters) and "Details" (optional, max 2000 characters) fields when adding a note
- **FR-003**: System MUST validate that the title field is not empty before saving a note
- **FR-004**: System MUST associate each note with a specific meal plan, date, and meal type
- **FR-005**: System MUST display notes in the meal plan calendar view, visually distinct from recipes
- **FR-006**: System MUST allow multiple notes in a single meal slot (consistent with multiple recipes per slot)
- **FR-007**: System MUST allow users to view note details by clicking on the note
- **FR-008**: System MUST allow users to edit note title and details after creation
- **FR-009**: System MUST allow users to delete notes from the meal plan
- **FR-010**: System MUST exclude notes when generating grocery lists from meal plans
- **FR-011**: System MUST restrict note access to the meal plan owner (consistent with meal plan authorization)

### Key Entities

- **MealPlanNote**: Represents a free-form note within a meal plan. Contains a title (required), details (optional), and is associated with a specific date and meal type within a meal plan. Distinct from MealAssignment which links recipes. Belongs to a MealPlan and inherits ownership from the meal plan's user.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Users can add a note to any meal slot in under 30 seconds
- **SC-002**: Notes are visually distinguishable from recipes at a glance in the calendar view
- **SC-003**: 100% of grocery lists generated from meal plans contain zero items derived from notes
- **SC-004**: Users can complete full note lifecycle (add, view, edit, delete) without errors
- **SC-005**: Notes and recipes coexist in the same meal slots without visual or functional conflicts

## Assumptions

- Notes follow the same date and meal type assignment pattern as recipes (using MealType enum: breakfast, lunch, dinner, snack)
- Notes do not have serving multipliers since they are not recipes
- The visual styling for notes will be consistent with the existing Flux UI component library
- Notes are only visible to the meal plan owner (inherits authorization from MealPlan)
- Note title will be displayed in the calendar grid; full details visible in a drawer/modal view
