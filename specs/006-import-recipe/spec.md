# Feature Specification: Import Recipe from URL

**Feature Branch**: `006-import-recipe`
**Created**: 2025-11-30
**Status**: Draft
**Input**: User description: "Create a import recipe feature. Input a URL for a page that has a recipe that supports the schema.org microdata standard. If the page supports the schema.org microdata standard then show a page with the recipe info and ask the user confirm the import. Clicking import will create a new recipe from this URL."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Successfully Import Recipe from Valid URL (Priority: P1)

A user discovers a recipe on an external website (e.g., food blog, cooking site) that supports schema.org Recipe markup. They want to import this recipe into their personal recipe collection without manually retyping all the details.

**Why this priority**: This is the core happy path - the primary reason the feature exists. Without this working, the feature provides no value.

**Independent Test**: Can be fully tested by providing a valid URL with schema.org Recipe microdata, verifying the parsed recipe data displays correctly, and confirming the recipe is saved to the database after import.

**Acceptance Scenarios**:

1. **Given** I am on the import recipe page, **When** I enter a URL containing valid schema.org Recipe microdata and submit, **Then** the system extracts the recipe data and displays a preview page showing the recipe name, ingredients, instructions, cooking time, servings, and any available images
2. **Given** I see the recipe preview page with extracted data, **When** I click the "Import Recipe" button, **Then** a new recipe is created in my collection with all the extracted information and I am redirected to view the newly imported recipe
3. **Given** I see the recipe preview page, **When** I click "Cancel" or navigate away, **Then** no recipe is created and I return to the previous page

---

### User Story 2 - Handle URL Without Recipe Microdata (Priority: P2)

A user attempts to import a recipe from a URL that does not contain schema.org Recipe microdata (e.g., plain text recipe, non-recipe page, or unsupported markup format).

**Why this priority**: Error handling for invalid input is critical for user experience, but secondary to the core functionality. Users need clear feedback when import fails.

**Independent Test**: Can be tested by providing URLs without schema.org markup and verifying appropriate error messages are shown without creating any recipe records.

**Acceptance Scenarios**:

1. **Given** I am on the import recipe page, **When** I enter a URL that does not contain schema.org Recipe microdata and submit, **Then** the system displays an error message explaining that no recipe data was found on that page
2. **Given** I see the "no recipe data found" error, **When** I am presented with options, **Then** I can either try a different URL or return to my recipe collection
3. **Given** the URL fails to load or returns an error, **When** the system cannot fetch the page, **Then** I see an error message indicating the page could not be accessed

---

### User Story 3 - Preview Before Import (Priority: P1)

A user wants to review the extracted recipe data before committing to import it, ensuring the automated extraction captured the information correctly.

**Why this priority**: This is part of the core workflow specified in the requirements. The confirmation step prevents unwanted or incorrect recipes from cluttering the user's collection.

**Independent Test**: Can be tested by verifying that extracted recipe data is displayed in a preview/confirmation interface before any database records are created, and that the user can cancel without side effects.

**Acceptance Scenarios**:

1. **Given** recipe data has been extracted from a URL, **When** I view the preview page, **Then** I see all extracted fields clearly labeled including recipe name, description, ingredients list, step-by-step instructions, prep time, cook time, total time, servings, and images
2. **Given** I am reviewing the preview, **When** the extracted data is incomplete or incorrect, **Then** I can choose to cancel the import without creating a recipe
3. **Given** I review the preview and the data looks correct, **When** I confirm the import, **Then** the recipe is saved exactly as shown in the preview

---

### User Story 4 - Handle Duplicate Recipe URLs (Priority: P3)

A user attempts to import a recipe from a URL they have already imported previously.

**Why this priority**: This is a quality-of-life improvement that prevents duplicate entries, but the feature still works without it. Users can manually manage duplicates.

**Independent Test**: Can be tested by importing the same URL twice and verifying the system's behavior (either prevents duplicate or allows it with notification).

**Acceptance Scenarios**:

1. **Given** I have previously imported a recipe from a specific URL, **When** I attempt to import the same URL again, **Then** the system notifies me that this recipe has already been imported and shows a link to the existing recipe
2. **Given** I see the duplicate notification, **When** I am presented with options, **Then** I can either view the existing recipe or proceed to import a duplicate copy if I choose

---

### Edge Cases

- What happens when the page contains multiple schema.org Recipe objects (e.g., a page with several recipe variations)?
- How does the system handle recipe data with missing optional fields (e.g., no prep time, no image, no description)?
- What happens when the URL redirects to a different page?
- How does the system handle very long ingredient lists or instruction steps (thousands of items)?
- What happens when the page requires authentication or is behind a paywall?
- How does the system handle non-English recipe content?
- What happens when the schema.org markup is malformed or partially invalid?

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST provide an interface where users can input a URL for recipe import
- **FR-002**: System MUST fetch and parse the HTML content from the provided URL
- **FR-003**: System MUST detect and extract schema.org Recipe microdata from the fetched page
- **FR-004**: System MUST validate that the URL contains schema.org Recipe structured data before proceeding
- **FR-005**: System MUST display an error message when the URL does not contain valid schema.org Recipe microdata
- **FR-006**: System MUST extract all available Recipe properties including: name, ingredients, instructions, and recipeYield (servings)
- **FR-007**: System SHOULD extract optional Recipe properties when available: description, image, prepTime, cookTime, totalTime, recipeCategory, recipeCuisine, nutrition information
- **FR-008**: System MUST display a preview page showing all extracted recipe data before import
- **FR-009**: System MUST provide a confirmation action (e.g., "Import Recipe" button) on the preview page
- **FR-010**: System MUST provide a cancel action on the preview page that abandons the import
- **FR-011**: System MUST create a new recipe record only after user confirms the import
- **FR-012**: System MUST store the source URL with the imported recipe for reference
- **FR-013**: System MUST associate the imported recipe with the authenticated user's account
- **FR-014**: System MUST handle network errors gracefully when fetching external URLs
- **FR-015**: System MUST handle timeouts when external pages take too long to load
- **FR-016**: System MUST sanitize extracted HTML content to prevent security vulnerabilities

### Key Entities

- **Recipe**: Represents an imported recipe with attributes including name, description, ingredients list, instructions, preparation time, cooking time, total time, servings, category, cuisine, nutritional information, images, source URL, and owner (user who imported it)
- **User**: The authenticated user who initiates the import and owns the resulting recipe

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Users can successfully import a recipe from a valid schema.org URL in under 30 seconds (from entering URL to viewing imported recipe)
- **SC-002**: System correctly extracts and displays recipe data from at least 95% of pages containing valid schema.org Recipe microdata
- **SC-003**: Users receive clear error messages within 5 seconds when attempting to import from invalid or inaccessible URLs
- **SC-004**: 90% of users successfully complete their first recipe import on the first attempt without assistance
- **SC-005**: Imported recipes contain all critical fields (name, ingredients, instructions) with 100% accuracy when present in source markup
- **SC-006**: Zero security vulnerabilities introduced through processing of external content (XSS, injection attacks, etc.)

## Assumptions

- Users are authenticated before accessing the import feature
- The system has network access to fetch external URLs
- External recipe websites are publicly accessible (not behind authentication)
- Recipe data in schema.org format follows the Recipe type specification (https://schema.org/Recipe)
- The application already has a Recipe model/entity for storing recipe data
- The application has a mechanism for displaying recipes to users
- Standard web request timeout limits apply (e.g., 30 seconds)
- The system will handle schema.org data in JSON-LD, Microdata, or RDFa formats (all valid schema.org embedding methods)
