# Tasks: Content Sharing

**Input**: Design documents from `/specs/011-share-content/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/sharing-actions.md, quickstart.md

**Tests**: Included per constitution (Test-First Development is NON-NEGOTIABLE). Tests written first, must fail before implementation.

**Organization**: Tasks grouped by user story to enable independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Create the data layer and shared components that all user stories depend on

- [x] T001 Create `content_shares` migration with polymorphic columns, nullable `shareable_id`, `recipient_email`, indexes, and unique constraint in `database/migrations/2026_02_10_000000_create_content_shares_table.php`
- [x] T002 [P] Create `SharePermission` enum with `Read` and `Write` cases in `app/Enums/SharePermission.php`
- [x] T003 [P] Create `ShareableType` enum with `Recipe`, `MealPlan`, `GroceryList` cases in `app/Enums/ShareableType.php`
- [x] T004 Create `ContentShare` model with `owner`, `recipient`, `shareable` (morphTo) relationships, fillable fields, and casts in `app/Models/ContentShare.php`
- [x] T005 Create `ContentShareFactory` with default state, `forRecipe()`, `forMealPlan()`, `forGroceryList()`, `shareAll()`, and `pending()` (null recipient_id) states in `database/factories/ContentShareFactory.php`
- [x] T006 Add `outgoingShares()` and `incomingShares()` HasMany relationships to `app/Models/User.php`
- [x] T007 [P] Add `contentShares()` MorphMany relationship to `app/Models/Recipe.php`
- [x] T008 [P] Add `contentShares()` MorphMany relationship to `app/Models/MealPlan.php`
- [x] T009 [P] Add `contentShares()` MorphMany relationship to `app/Models/GroceryList.php`
- [x] T010 [P] Add `scopeAccessibleBy(Builder $query, User $user)` query scope to `app/Models/Recipe.php` returning owned + specifically shared + share-all items
- [x] T011 [P] Add `scopeAccessibleBy(Builder $query, User $user)` query scope to `app/Models/MealPlan.php` returning owned + specifically shared + share-all items
- [x] T012 [P] Add `scopeAccessibleBy(Builder $query, User $user)` query scope to `app/Models/GroceryList.php` returning owned + specifically shared + share-all items
- [x] T013 Run migration and verify schema on SQLite: `php artisan migrate`

**Checkpoint**: Data layer ready — ContentShare model, factory, enums, relationships, and query scopes all in place

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Authorization infrastructure that MUST be complete before any user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

### Tests for Foundational Phase

- [x] T014 [P] Write policy tests for Recipe sharing permissions (owner, read-shared, write-shared, unshared, share-all) in `tests/Feature/Sharing/SharePermissionsTest.php`
- [x] T015 [P] Write policy tests for MealPlan sharing permissions (owner, read-shared, write-shared, unshared, share-all) in `tests/Feature/Sharing/SharePermissionsTest.php`
- [x] T016 [P] Write policy tests for GroceryList sharing permissions (owner, read-shared, write-shared, unshared, share-all) in `tests/Feature/Sharing/SharePermissionsTest.php`

### Implementation for Foundational Phase

- [x] T017 Add `share()` method (owner-only) and `hasShareAccess()`/`hasWriteShareAccess()` private helpers to `app/Policies/RecipePolicy.php`. Extend `view()` to allow shared access (read or write). Extend `update()` to allow write-shared access. Keep `delete()` owner-only.
- [x] T018 [P] Add `share()` method (owner-only) and `hasShareAccess()`/`hasWriteShareAccess()` private helpers to `app/Policies/MealPlanPolicy.php`. Extend `view()` and `update()` for shared access. Keep `delete()` owner-only.
- [x] T019 [P] Add `share()` method (owner-only) and `hasShareAccess()`/`hasWriteShareAccess()` private helpers to `app/Policies/GroceryListPolicy.php`. Extend `view()` and `update()` for shared access. Keep `delete()` owner-only. Preserve existing `viewShared()` for token-based sharing.
- [x] T020 Write ContentShare model relationship and scope tests in `tests/Feature/Sharing/ContentShareModelTest.php` — test `accessibleBy` scope for owned items, specific shares, share-all shares, and no-access cases
- [x] T021 Run foundational tests: `php artisan test tests/Feature/Sharing/SharePermissionsTest.php tests/Feature/Sharing/ContentShareModelTest.php`

**Checkpoint**: Authorization layer ready — policies check share permissions, scopes return correct items. All user story implementation can now begin.

---

## Phase 3: User Story 1 — Share a Specific Item with Another User (Priority: P1) 🎯 MVP

**Goal**: Owner shares a recipe/meal plan/grocery list with another user by email. Content is immediately accessible to the recipient.

**Independent Test**: Navigate to a recipe, click share, enter email, select permission, verify recipient sees shared item.

### Tests for User Story 1

- [x] T022 [P] [US1] Write tests for sharing a recipe from the show page (valid email, self-share prevention, non-owner blocked, upsert on duplicate) in `tests/Feature/Sharing/ShareRecipeTest.php`
- [x] T023 [P] [US1] Write tests for sharing a meal plan from the show page in `tests/Feature/Sharing/ShareMealPlanTest.php`
- [x] T024 [P] [US1] Write tests for sharing a grocery list from the show page in `tests/Feature/Sharing/ShareGroceryListTest.php`

### Implementation for User Story 1

- [x] T025 [US1] Create reusable share modal Blade component with email input, permission select (read-only / read-write), and submit button in `resources/views/components/share-modal.blade.php`
- [x] T026 [US1] Add `shareWith()` Livewire action, `shareEmail`/`sharePermission` properties, `showShareModal` state, and share modal to `app/Livewire/Recipes/Show.php` and `resources/views/livewire/recipes/show.blade.php`. Include share button, self-share prevention, upsert logic, and success flash.
- [x] T027 [P] [US1] Add `shareWith()` Livewire action, share properties, modal state, and share button to `app/Livewire/MealPlans/Show.php` and `resources/views/livewire/meal-plans/show.blade.php`
- [x] T028 [P] [US1] Add `shareWith()` Livewire action, share properties, modal state, and share button to `app/Livewire/GroceryLists/Show.php` and `resources/views/livewire/grocery-lists/show.blade.php`
- [x] T029 [US1] Run US1 tests: `php artisan test tests/Feature/Sharing/ShareRecipeTest.php tests/Feature/Sharing/ShareMealPlanTest.php tests/Feature/Sharing/ShareGroceryListTest.php`

**Checkpoint**: Users can share specific items with other registered users. MVP is functional.

---

## Phase 4: User Story 2 — Share All Items of a Content Type (Priority: P1)

**Goal**: Owner shares all recipes, all meal plans, or all grocery lists with a user. Future items are automatically included.

**Independent Test**: Enable "share all recipes" with a user, create a new recipe, verify recipient sees the new recipe without any additional sharing action.

### Tests for User Story 2

- [x] T030 [P] [US2] Write tests for share-all creation (all recipes, all meal plans, all grocery lists), auto-inclusion of future items, coexistence with specific-item shares, and upsert behavior in `tests/Feature/Sharing/ShareAllTest.php`

### Implementation for User Story 2

- [x] T031 [US2] Create `Settings\Sharing` Livewire component with outgoing shares list (grouped by recipient), `shareAll()` action with email/type/permission inputs, and self-share prevention in `app/Livewire/Settings/Sharing.php`
- [x] T032 [US2] Create sharing settings Blade view with share-all form (email, content type dropdown, permission select), active shares list with type/item/permission columns in `resources/views/livewire/settings/sharing.blade.php`
- [x] T033 [US2] Add route `GET /settings/sharing` pointing to `Settings\Sharing` in `routes/web.php`
- [x] T034 [US2] Add "Sharing" navlist entry to settings sidebar in `resources/views/components/settings/layout.blade.php`
- [x] T035 [US2] Run US2 tests: `php artisan test tests/Feature/Sharing/ShareAllTest.php`

**Checkpoint**: Users can share all items of a content type and manage shares from settings. Future items auto-included.

---

## Phase 5: User Story 3 — View and Interact with Shared Content (Priority: P2)

**Goal**: Recipients see shared items in their dashboard/list views with owner labels. Read-only users can view; read-write users can edit. No delete or re-share for any recipient.

**Independent Test**: Log in as recipient, verify shared items appear in recipe/meal plan/grocery list indexes with owner badge, check read-only blocks editing, check read-write allows editing.

### Tests for User Story 3

- [ ] T036 [P] [US3] Write tests for shared content display: shared items appear in index views, owner name label visible, read-only items hide edit/delete controls, read-write items show edit controls, recipients cannot delete or re-share in `tests/Feature/Sharing/SharedContentDisplayTest.php`

### Implementation for User Story 3

- [ ] T037 [US3] Update recipe index query in `app/Livewire/Recipes/Index.php` to use `accessibleBy(auth()->user())` scope instead of `where('user_id', auth()->id())`. Eager-load `user` relationship for owner name display.
- [ ] T038 [P] [US3] Update meal plan index query in `app/Livewire/MealPlans/Index.php` to use `accessibleBy(auth()->user())` scope. Eager-load `user` relationship.
- [ ] T039 [P] [US3] Update grocery list index query in `app/Livewire/GroceryLists/Index.php` to use `accessibleBy(auth()->user())` scope. Eager-load `user` relationship.
- [ ] T040 [US3] Update dashboard queries in `app/Livewire/Dashboard.php` to include shared meal plans and grocery lists using `accessibleBy()` scope
- [ ] T041 [US3] Add "Shared by {owner name}" badge to recipe cards in `resources/views/livewire/recipes/index.blade.php` for items where `user_id !== auth()->id()`
- [ ] T042 [P] [US3] Add "Shared by {owner name}" badge to meal plan cards in `resources/views/livewire/meal-plans/index.blade.php`
- [ ] T043 [P] [US3] Add "Shared by {owner name}" badge to grocery list cards in `resources/views/livewire/grocery-lists/index.blade.php`
- [ ] T044 [US3] Conditionally hide edit/delete/share buttons on show pages (`recipes/show`, `meal-plans/show`, `grocery-lists/show`) when the current user is not the owner. Show edit button only if user has write permission via policy `can('update', $model)`.
- [ ] T045 [US3] Run US3 tests: `php artisan test tests/Feature/Sharing/SharedContentDisplayTest.php`

**Checkpoint**: Recipients see shared content with owner labels. Permission enforcement works end-to-end.

---

## Phase 6: User Story 4 — Manage and Revoke Sharing (Priority: P2)

**Goal**: Owner views all active shares in a management screen, can change permission levels, and revoke access immediately.

**Independent Test**: Navigate to sharing settings, change a share from read-write to read-only, verify recipient's access is downgraded. Revoke a share, verify recipient loses access.

### Tests for User Story 4

- [ ] T046 [P] [US4] Write tests for share management: list all outgoing shares, update permission level, revoke share, revoke share-all removes all access, specific revoke under share-all keeps access in `tests/Feature/Sharing/ShareManagementTest.php`

### Implementation for User Story 4

- [ ] T047 [US4] Add `updatePermission(int $shareId, string $newPermission)` action to `app/Livewire/Settings/Sharing.php` — find share, verify owner, update permission field
- [ ] T048 [US4] Add `revokeShare(int $shareId)` action to `app/Livewire/Settings/Sharing.php` — find share, verify owner, delete record
- [ ] T049 [US4] Add permission change dropdown and revoke button per share row in `resources/views/livewire/settings/sharing.blade.php`
- [ ] T050 [US4] Run US4 tests: `php artisan test tests/Feature/Sharing/ShareManagementTest.php`

**Checkpoint**: Owners have full control to manage, modify, and revoke shares.

---

## Phase 7: User Story 5 — Receive Invitation to Join (Priority: P3)

**Goal**: Non-registered users receive an invitation email when content is shared with their email. Upon registration, all pending shares activate automatically.

**Independent Test**: Share an item with a non-registered email, verify invitation email is sent. Register with that email, verify shared content appears immediately.

### Tests for User Story 5

- [ ] T051 [P] [US5] Write tests for invitation system: invitation email sent for non-registered email, email not sent for registered user, pending shares resolved on registration, multiple pending shares all resolve at once, registration with different email does not resolve shares in `tests/Feature/Sharing/ShareInvitationTest.php`

### Implementation for User Story 5

- [ ] T052 [US5] Create `ShareInvitation` mailable with `ownerName`, `contentDescription`, `registerUrl` properties in `app/Mail/ShareInvitation.php`
- [ ] T053 [US5] Create invitation email Blade template with personalized message and registration link in `resources/views/mail/share-invitation.blade.php`
- [ ] T054 [US5] Add invitation sending logic to `shareWith()` in Show components and `shareAll()` in Settings\Sharing: if `User::where('email', $email)->doesntExist()`, send `ShareInvitation` mailable
- [ ] T055 [US5] Create `ResolvePendingShares` event listener on `Illuminate\Auth\Events\Registered` — query `ContentShare::whereNull('recipient_id')->where('recipient_email', $user->email)` and set `recipient_id` in `app/Listeners/ResolvePendingShares.php`
- [ ] T056 [US5] Register `ResolvePendingShares` listener in `app/Providers/AppServiceProvider.php` or via `Event::listen()` in `bootstrap/app.php`
- [ ] T057 [US5] Run US5 tests: `php artisan test tests/Feature/Sharing/ShareInvitationTest.php`

**Checkpoint**: Full invitation flow works. Non-registered users can be invited, and their shares activate upon registration.

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Quality assurance, code formatting, and regression testing

- [ ] T058 Run Laravel Pint to format all new and modified files: `vendor/bin/pint --dirty`
- [ ] T059 Verify existing token-based grocery list sharing still works: `php artisan test tests/Feature/GroceryLists/ShareGroceryListTest.php`
- [ ] T060 Run full test suite to verify no regressions: `php artisan test`
- [ ] T061 Verify all edge cases: self-sharing prevention, duplicate share upsert, owner deletion cascades shares, share-all + specific share coexistence

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — can start immediately
- **Foundational (Phase 2)**: Depends on Phase 1 completion — BLOCKS all user stories
- **US1 (Phase 3)**: Depends on Phase 2 — foundational sharing action
- **US2 (Phase 4)**: Depends on Phase 2 — can run in parallel with US1
- **US3 (Phase 5)**: Depends on Phase 2 — can run in parallel with US1/US2 (uses `accessibleBy` scope from Phase 1)
- **US4 (Phase 6)**: Depends on Phase 4 (builds on Settings\Sharing component from US2)
- **US5 (Phase 7)**: Depends on Phase 2 — can run in parallel with US1/US2/US3
- **Polish (Phase 8)**: Depends on all user stories being complete

### User Story Dependencies

- **US1 (P1)**: Independent after Phase 2. Core sharing action.
- **US2 (P1)**: Independent after Phase 2. Creates Settings\Sharing component used by US4.
- **US3 (P2)**: Independent after Phase 2. Updates index/show views.
- **US4 (P2)**: Depends on US2 (extends Settings\Sharing component with manage/revoke actions).
- **US5 (P3)**: Independent after Phase 2. Adds invitation layer to sharing.

### Within Each User Story

- Tests MUST be written and FAIL before implementation
- Models/data layer before UI components
- Backend logic before Blade views
- Core implementation before integration
- Run story-specific tests at checkpoint

### Parallel Opportunities

- T002 + T003 (enums) can run in parallel
- T007 + T008 + T009 (morphMany on each model) can run in parallel
- T010 + T011 + T012 (accessibleBy scopes) can run in parallel
- T014 + T015 + T016 (policy tests) can run in parallel
- T017 + T018 + T019 (policy implementations) — T018/T019 parallel after T017 establishes pattern
- T022 + T023 + T024 (US1 tests) can run in parallel
- T027 + T028 (US1 MealPlan/GroceryList show) can run in parallel
- US1, US2, US3, US5 can all proceed in parallel after Phase 2

---

## Parallel Example: User Story 1

```bash
# Launch all US1 tests together (write first, ensure they fail):
Task: "Write recipe sharing tests in tests/Feature/Sharing/ShareRecipeTest.php"
Task: "Write meal plan sharing tests in tests/Feature/Sharing/ShareMealPlanTest.php"
Task: "Write grocery list sharing tests in tests/Feature/Sharing/ShareGroceryListTest.php"

# Then create shared component:
Task: "Create share modal Blade component in resources/views/components/share-modal.blade.php"

# Then implement Show page actions in parallel:
Task: "Add shareWith() to Recipes/Show (establishes pattern)"
Task: "Add shareWith() to MealPlans/Show (follows pattern)"  # [P] after T026
Task: "Add shareWith() to GroceryLists/Show (follows pattern)"  # [P] after T026
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (data layer, enums, model, factory, relationships, scopes)
2. Complete Phase 2: Foundational (policy extensions + tests)
3. Complete Phase 3: User Story 1 (share specific items from show pages)
4. **STOP and VALIDATE**: Test US1 independently — share a recipe, verify recipient access
5. Deploy/demo if ready — users can share individual items

### Incremental Delivery

1. Setup + Foundational → Data layer and authorization ready
2. Add US1 → Share specific items → Test → Deploy (MVP!)
3. Add US2 → Share all of a type → Test → Deploy
4. Add US3 → Recipients see shared content with labels → Test → Deploy
5. Add US4 → Manage and revoke shares → Test → Deploy
6. Add US5 → Invitation emails for non-users → Test → Deploy
7. Each story adds value without breaking previous stories

### Suggested MVP Scope

**US1 (Share a Specific Item)** alone delivers a functional sharing feature. Users can share individual recipes, meal plans, or grocery lists with other registered users. This requires Phases 1-3 (Setup, Foundational, US1) = 29 tasks.

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story is independently completable and testable
- Verify tests fail before implementing
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Existing token-based grocery list sharing (anonymous) must not be broken
- The `accessibleBy` scope is the key shared infrastructure — it enables both US3 (display) and policy checks
