# Tasks: Grocery Item Autocomplete Lookup

**Input**: Design documents from `/specs/001-grocery-item-lookup/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/autocomplete-api.json

**Tests**: Following constitution's Test-First Development principle - tests MUST be written BEFORE implementation

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

Laravel 12 monolith structure:
- Models: `app/Models/`
- Livewire components: `app/Livewire/`
- Services: `app/Services/`
- Observers/Jobs: `app/Observers/`, `app/Jobs/`
- Migrations: `database/migrations/`
- Seeders: `database/seeders/`
- Views: `resources/views/livewire/`
- Feature tests: `tests/Feature/`
- Unit tests: `tests/Unit/`
- E2E tests: `e2e/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Database schema and models that ALL user stories depend on

- [X] T001 Create migration for common_item_templates table in database/migrations/YYYY_MM_DD_HHMMSS_create_common_item_templates_table.php
- [X] T002 Create migration for user_item_templates table in database/migrations/YYYY_MM_DD_HHMMSS_create_user_item_templates_table.php
- [X] T003 [P] Create CommonItemTemplate model in app/Models/CommonItemTemplate.php
- [X] T004 [P] Create UserItemTemplate model in app/Models/UserItemTemplate.php
- [X] T005 Add itemTemplates relationship to User model in app/Models/User.php
- [X] T006 Create CommonItemTemplateSeeder with 100-200 items in database/seeders/CommonItemTemplateSeeder.php
- [X] T007 Run migrations and seeders to initialize database schema
- [X] T008 [P] Create GroceryItemObserver in app/Observers/GroceryItemObserver.php (skeleton only, will implement in US2)
- [X] T009 [P] Create UpdateUserItemTemplate job in app/Jobs/UpdateUserItemTemplate.php (skeleton only, will implement in US2)
- [X] T010 Register observer in AppServiceProvider boot method in app/Providers/AppServiceProvider.php

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core autocomplete service that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [X] T011 Create ItemAutoCompleteService with query method in app/Services/ItemAutoCompleteService.php
- [X] T012 Implement LIKE-based search in ItemAutoCompleteService (prefix + contains matching)
- [X] T013 Implement suggestion ranking (user templates first, then common defaults) in ItemAutoCompleteService
- [X] T014 Add database indexes for autocomplete performance (verify in migrations T001, T002)

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Quick Item Addition with Category Suggestion (Priority: P1) 🎯 MVP

**Goal**: Users can type common item names and get autocomplete suggestions with pre-filled categories from common defaults

**Independent Test**: Type "milk" in add item form → see suggestion → select → category auto-populates to "Dairy"

### Tests for User Story 1 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [X] T015 [P] [US1] Write Pest test for autocomplete query returning common templates in tests/Feature/GroceryLists/AutocompleteItemTest.php
- [X] T016 [P] [US1] Write Pest test for partial name matching ("banan" → "banana") in tests/Feature/GroceryLists/AutocompleteItemTest.php
- [X] T017 [P] [US1] Write Pest test for category auto-population on selection in tests/Feature/GroceryLists/AutocompleteItemTest.php
- [X] T018 [P] [US1] Write Pest test for user can override suggested values in tests/Feature/GroceryLists/AutocompleteItemTest.php
- [X] T019 [P] [US1] Write Playwright E2E test for typing triggers dropdown in e2e/grocery-lists/autocomplete-item.spec.ts
- [X] T020 [P] [US1] Write Playwright E2E test for selecting suggestion populates fields in e2e/grocery-lists/autocomplete-item.spec.ts
- [X] T021 [P] [US1] Write Playwright E2E test for keyboard navigation (arrows, enter, escape) in e2e/grocery-lists/autocomplete-item.spec.ts

### Implementation for User Story 1

- [X] T022 [US1] Add searchQuery and suggestions properties to GroceryLists\Show component in app/Livewire/GroceryLists/Show.php
- [X] T023 [US1] Implement #[Computed] suggestions() method using ItemAutoCompleteService in app/Livewire/GroceryLists/Show.php
- [X] T024 [US1] Implement selectGroceryItem(array $item) method in app/Livewire/GroceryLists/Show.php
- [X] T025 [US1] Add autocomplete input with wire:model.live.debounce.300ms in resources/views/livewire/grocery-lists/show.blade.php
- [X] T026 [US1] Add Alpine.js groceryAutocomplete() state management script in resources/views/livewire/grocery-lists/show.blade.php
- [X] T027 [US1] Implement dropdown suggestions list with ARIA attributes in resources/views/livewire/grocery-lists/show.blade.php
- [X] T028 [US1] Add keyboard navigation handlers (@keydown.arrow-down, @keydown.arrow-up, @keydown.enter, @keydown.escape) in resources/views/livewire/grocery-lists/show.blade.php
- [X] T029 [US1] Style autocomplete dropdown with Tailwind CSS (mobile-responsive, 44px touch targets) in resources/views/livewire/grocery-lists/show.blade.php
- [X] T030 [US1] Run Pest tests for US1 and verify all pass
- [X] T031 [US1] Run Playwright E2E tests for US1 and verify all pass
- [X] T032 [US1] Run vendor/bin/pint to format code

**Checkpoint**: At this point, User Story 1 should be fully functional - new users can use autocomplete with common defaults

---

## Phase 4: User Story 2 - Personal Item History Learning (Priority: P2)

**Goal**: System learns user preferences and prioritizes personal history over common defaults in autocomplete suggestions

**Independent Test**: Add "almond milk" as "beverages" → type "almond" → suggestion uses "beverages" (not default "dairy")

### Tests for User Story 2 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [X] T033 [P] [US2] Write Pest test for user template creation on item save in tests/Feature/ItemTemplates/CreateUserTemplateTest.php
- [X] T034 [P] [US2] Write Pest test for usage_count increment on repeat saves in tests/Feature/ItemTemplates/CreateUserTemplateTest.php
- [X] T035 [P] [US2] Write Pest test for personal history prioritized over common defaults in tests/Feature/ItemTemplates/PrioritizePersonalHistoryTest.php
- [X] T036 [P] [US2] Write Pest test for most frequent category wins (5x produce > 2x pantry) in tests/Feature/ItemTemplates/PrioritizePersonalHistoryTest.php
- [X] T037 [P] [US2] Write Pest test for observer only tracks manual items (not generated) in tests/Feature/ItemTemplates/CreateUserTemplateTest.php
- [X] T038 [P] [US2] Write Pest test for job updates last_used_at timestamp in tests/Feature/ItemTemplates/CreateUserTemplateTest.php
- [X] T039 [P] [US2] Write Playwright E2E test for personal suggestions appear first in e2e/grocery-lists/autocomplete-item.spec.ts

### Implementation for User Story 2

- [X] T040 [US2] Implement GroceryItemObserver created() method to dispatch job in app/Observers/GroceryItemObserver.php
- [X] T041 [US2] Add source_type check (only track SourceType::MANUAL) in app/Observers/GroceryItemObserver.php
- [X] T042 [US2] Implement UpdateUserItemTemplate job handle() method in app/Jobs/UpdateUserItemTemplate.php
- [X] T043 [US2] Use updateOrCreate to increment usage_count and update last_used_at in app/Jobs/UpdateUserItemTemplate.php
- [X] T044 [US2] Add cache invalidation (Cache::forget) after template update in app/Jobs/UpdateUserItemTemplate.php
- [X] T045 [US2] Update ItemAutoCompleteService to query user templates first in app/Services/ItemAutoCompleteService.php
- [X] T046 [US2] Implement ranking: user templates (by usage_count DESC, last_used_at DESC) before common defaults in app/Services/ItemAutoCompleteService.php
- [X] T047 [US2] Add de-duplication logic (user template wins over common default with same name) in app/Services/ItemAutoCompleteService.php
- [X] T048 [US2] Run Pest tests for US2 and verify all pass
- [ ] T049 [US2] Run Playwright E2E tests for US2 and verify all pass
- [X] T050 [US2] Run vendor/bin/pint to format code

**Checkpoint**: At this point, User Stories 1 AND 2 should both work - autocomplete learns user preferences over time

---

## Phase 5: User Story 3 - Managing Personal Item Templates (Priority: P3)

**Goal**: Power users can view, edit, and delete their personal item templates via management UI

**Independent Test**: Navigate to item templates page → edit "milk" category to "beverages" → save → type "mil" → sees "beverages"

### Tests for User Story 3 ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [X] T051 [P] [US3] Write Pest test for viewing all user templates in tests/Feature/ItemTemplates/ManageTemplatesTest.php
- [X] T052 [P] [US3] Write Pest test for editing template category in tests/Feature/ItemTemplates/ManageTemplatesTest.php
- [X] T053 [P] [US3] Write Pest test for editing template updates autocomplete suggestions in tests/Feature/ItemTemplates/ManageTemplatesTest.php
- [X] T054 [P] [US3] Write Pest test for manually creating template in tests/Feature/ItemTemplates/ManageTemplatesTest.php
- [X] T055 [P] [US3] Write Pest test for deleting template falls back to common defaults in tests/Feature/ItemTemplates/ManageTemplatesTest.php
- [X] T056 [P] [US3] Write Pest test for authorization (cannot view/edit other users' templates) in tests/Feature/ItemTemplates/ManageTemplatesTest.php
- [X] T057 [P] [US3] Write Playwright E2E test for template CRUD workflow in e2e/grocery-lists/item-templates.spec.ts

### Implementation for User Story 3

- [X] T058 [P] [US3] Create ItemTemplates\Index Livewire component in app/Livewire/GroceryLists/ItemTemplates/Index.php
- [X] T059 [P] [US3] Create ItemTemplates\Edit Livewire component in app/Livewire/GroceryLists/ItemTemplates/Edit.php
- [X] T060 [P] [US3] Create ItemTemplates\Delete Livewire component (or method in Index) in app/Livewire/GroceryLists/ItemTemplates/Index.php
- [X] T061 [US3] Add routes for item templates management in routes/web.php
- [X] T062 [US3] Implement authorization policy for user item templates in app/Policies/UserItemTemplatePolicy.php
- [X] T063 [P] [US3] Create index view listing user's templates in resources/views/livewire/grocery-lists/item-templates/index.blade.php
- [X] T064 [P] [US3] Create edit form with Flux components in resources/views/livewire/grocery-lists/item-templates/edit.blade.php
- [X] T065 [US3] Add navigation link to "My Item Templates" in app header/sidebar
- [X] T066 [US3] Implement save() method with validation in ItemTemplates\Edit component
- [X] T067 [US3] Implement delete() method with confirmation in ItemTemplates\Index component
- [X] T068 [US3] Add cache invalidation after edit/delete operations
- [X] T069 [US3] Run Pest tests for US3 and verify all pass
- [X] T070 [US3] Run Playwright E2E tests for US3 and verify all pass
- [X] T071 [US3] Run vendor/bin/pint to format code

**Checkpoint**: All user stories should now be independently functional - full autocomplete feature complete

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [ ] T072 [P] Add performance monitoring for autocomplete queries (<200ms target)
- [ ] T073 [P] Write unit tests for ItemAutoCompleteService fuzzy matching in tests/Unit/Services/ItemAutoCompleteServiceTest.php
- [ ] T074 [P] Add accessibility audit (ARIA labels, keyboard nav, screen reader testing)
- [ ] T075 [P] Test mobile responsive design on various viewports (iPhone, iPad, Android)
- [ ] T076 [P] Add error handling for autocomplete failures (graceful degradation)
- [ ] T077 [P] Implement optional caching for top 10 user suggestions (1-hour TTL)
- [ ] T078 [P] Add database query logging in development for performance debugging
- [ ] T079 Run full test suite (all Pest tests + all Playwright tests)
- [ ] T080 Run vendor/bin/pint on entire codebase
- [ ] T081 Validate quickstart.md instructions (run through setup guide)
- [ ] T082 Update CLAUDE.md if needed (document autocomplete patterns)
- [ ] T083 Create demo video/screenshots of autocomplete in action

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3-5)**: All depend on Foundational phase completion
  - User stories can then proceed in parallel (if staffed)
  - Or sequentially in priority order (P1 → P2 → P3)
- **Polish (Phase 6)**: Depends on all desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Depends on Foundational (Phase 2) - No dependencies on other stories ✅ Can start immediately after Phase 2
- **User Story 2 (P2)**: Depends on Foundational (Phase 2) - Enhances US1 but is independently testable ✅ Can start after Phase 2 (parallel with US1 if desired)
- **User Story 3 (P3)**: Depends on US2 (needs user templates to exist) - Should run after US2 completes

### Within Each User Story

- Tests (Pest + Playwright) MUST be written and FAIL before implementation
- Models before services (T003-T005 before T011-T014)
- Services before Livewire components (T011-T014 before T022-T032)
- Observer/Job setup before US2 implementation (T040-T047)
- Core implementation before integration (T022-T029 before T030-T032)
- Story complete before moving to next priority

### Parallel Opportunities

- **Setup phase**: T003 and T004 (models), T008 and T009 (observer/job skeletons) can run in parallel
- **Foundational phase**: No parallel tasks (service implementation is sequential)
- **User Story 1 tests**: T015-T021 can all run in parallel (different test files)
- **User Story 2 tests**: T033-T039 can all run in parallel (different test files)
- **User Story 3 tests**: T051-T057 can all run in parallel (different test files)
- **User Story 3 components**: T058-T060 (Livewire components), T063-T064 (views) can run in parallel
- **Polish phase**: T072-T078 can all run in parallel (different concerns)
- **Once Foundational completes**: US1, US2 can start in parallel (US3 waits for US2)

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together:
Task: "Write Pest test for autocomplete query returning common templates in tests/Feature/GroceryLists/AutocompleteItemTest.php"
Task: "Write Pest test for partial name matching in tests/Feature/GroceryLists/AutocompleteItemTest.php"
Task: "Write Pest test for category auto-population in tests/Feature/GroceryLists/AutocompleteItemTest.php"
Task: "Write Pest test for user can override suggested values in tests/Feature/GroceryLists/AutocompleteItemTest.php"
Task: "Write Playwright E2E test for typing triggers dropdown in e2e/grocery-lists/autocomplete-item.spec.ts"
Task: "Write Playwright E2E test for selecting suggestion populates fields in e2e/grocery-lists/autocomplete-item.spec.ts"
Task: "Write Playwright E2E test for keyboard navigation in e2e/grocery-lists/autocomplete-item.spec.ts"

# After tests written and failing, launch implementation tasks sequentially:
# (Implementation tasks have dependencies, so they run in order T022 → T023 → T024 → etc.)
```

---

## Parallel Example: User Story 3

```bash
# Launch all tests for User Story 3 together:
Task: "Write Pest test for viewing all user templates in tests/Feature/ItemTemplates/ManageTemplatesTest.php"
Task: "Write Pest test for editing template category in tests/Feature/ItemTemplates/ManageTemplatesTest.php"
Task: "Write Pest test for editing template updates autocomplete suggestions in tests/Feature/ItemTemplates/ManageTemplatesTest.php"
Task: "Write Pest test for manually creating template in tests/Feature/ItemTemplates/ManageTemplatesTest.php"
Task: "Write Pest test for deleting template falls back to common defaults in tests/Feature/ItemTemplates/ManageTemplatesTest.php"
Task: "Write Pest test for authorization in tests/Feature/ItemTemplates/ManageTemplatesTest.php"
Task: "Write Playwright E2E test for template CRUD workflow in e2e/grocery-lists/item-templates.spec.ts"

# Launch Livewire components in parallel:
Task: "Create ItemTemplates\Index Livewire component in app/Livewire/GroceryLists/ItemTemplates/Index.php"
Task: "Create ItemTemplates\Edit Livewire component in app/Livewire/GroceryLists/ItemTemplates/Edit.php"

# Launch views in parallel:
Task: "Create index view listing user's templates in resources/views/livewire/grocery-lists/item-templates/index.blade.php"
Task: "Create edit form with Flux components in resources/views/livewire/grocery-lists/item-templates/edit.blade.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (T001-T010)
2. Complete Phase 2: Foundational (T011-T014) - CRITICAL - blocks all stories
3. Complete Phase 3: User Story 1 (T015-T032)
4. **STOP and VALIDATE**: Test User Story 1 independently
   - Type "milk" → see suggestion
   - Select → category auto-populates
   - Save item
5. Deploy/demo if ready

**Deliverable**: Autocomplete with common defaults for all users

### Incremental Delivery

1. Complete Setup + Foundational → Foundation ready (T001-T014)
2. Add User Story 1 → Test independently → Deploy/Demo (MVP!) (T015-T032)
3. Add User Story 2 → Test independently → Deploy/Demo (personal learning) (T033-T050)
4. Add User Story 3 → Test independently → Deploy/Demo (power user management) (T051-T071)
5. Polish → Final release (T072-T083)
6. Each story adds value without breaking previous stories

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together (T001-T014)
2. Once Foundational is done:
   - Developer A: User Story 1 (T015-T032)
   - Developer B: User Story 2 (T033-T050) - Can start immediately, enhances US1
   - Developer C: Waits for US2 → User Story 3 (T051-T071)
3. Stories complete and integrate independently

---

## Task Summary

**Total Tasks**: 83
- Phase 1 (Setup): 10 tasks
- Phase 2 (Foundational): 4 tasks (BLOCKING)
- Phase 3 (User Story 1 - MVP): 18 tasks (7 tests + 11 implementation)
- Phase 4 (User Story 2): 18 tasks (7 tests + 11 implementation)
- Phase 5 (User Story 3): 21 tasks (7 tests + 14 implementation)
- Phase 6 (Polish): 12 tasks

**Parallel Opportunities**: 47 tasks marked [P] can run in parallel within their phase

**Independent Test Criteria**:
- **US1**: Type common item → see suggestion → category auto-populates
- **US2**: Add custom item → type again → personal suggestion appears first
- **US3**: Edit template → autocomplete uses updated values

**Suggested MVP Scope**: Phase 1 + Phase 2 + Phase 3 (User Story 1) = 32 tasks
- Delivers autocomplete with common defaults
- Provides immediate value to all users
- Foundation for personalization (US2) and management (US3)

**Format Validation**: ✅ All tasks follow checklist format:
- All tasks start with `- [ ]`
- All tasks have sequential IDs (T001-T083)
- All user story tasks have [Story] labels ([US1], [US2], [US3])
- All parallelizable tasks marked [P]
- All tasks include exact file paths

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Verify tests fail before implementing
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Run vendor/bin/pint before committing
- Avoid: vague tasks, same file conflicts, cross-story dependencies that break independence
- Follow Test-First Development (Constitution Principle III) - tests BEFORE implementation
