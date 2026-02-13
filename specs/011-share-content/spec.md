# Feature Specification: Content Sharing

**Feature Branch**: `011-share-content`
**Created**: 2026-02-10
**Status**: Draft
**Input**: User description: "Add option to share all my recipes, meal plans and grocery lists with another user. Share via email address (invite non-users to sign up). 'Share all' auto-includes future items. No acceptance required - shared content appears immediately."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Share a Specific Item with Another User (Priority: P1)

As a user who wants to collaborate, I want to share a specific recipe, meal plan, or grocery list with another person by entering their email address, so they can immediately see and optionally edit the content I've shared.

**Why this priority**: This is the foundational sharing action. Without the ability to share a single item, no other sharing features have value. It delivers immediate utility for users who want to collaborate on specific content.

**Independent Test**: Can be fully tested by navigating to a recipe, clicking share, entering an email, selecting a permission level, and verifying the recipient sees the shared item in their dashboard.

**Acceptance Scenarios**:

1. **Given** I am viewing one of my recipes, **When** I click a share button, **Then** I see a share dialog where I can enter an email address and choose read-only or read-write permission
2. **Given** I am viewing one of my meal plans, **When** I click a share button, **Then** I see the same share dialog with email and permission options
3. **Given** I am viewing one of my grocery lists, **When** I click a share button, **Then** I see the same share dialog with email and permission options
4. **Given** I have entered a valid email and selected a permission level, **When** I submit the share form, **Then** the content is immediately accessible to the recipient (no acceptance step required)
5. **Given** I enter an email of a registered user, **When** I submit the share, **Then** the shared item appears in that user's dashboard immediately
6. **Given** I enter an email of someone who is not a registered user, **When** I submit the share, **Then** they receive an invitation email prompting them to sign up, and the share is activated once they register
7. **Given** I try to share an item I do not own, **When** I attempt the share action, **Then** I am prevented from sharing (only owners can share)

---

### User Story 2 - Share All Items of a Content Type (Priority: P1)

As a user who wants to share everything with a partner or family member, I want to share all of my recipes, all of my meal plans, or all of my grocery lists with another user, including items I create in the future, so I don't have to share each item individually.

**Why this priority**: This is a core differentiator of the feature. Sharing individual items is useful, but "share all" with auto-inclusion of future items is what makes it practical for households and families.

**Independent Test**: Can be fully tested by enabling "share all recipes" with a user, creating a new recipe, and verifying the recipient automatically sees the new recipe.

**Acceptance Scenarios**:

1. **Given** I am in my sharing settings, **When** I choose to share all recipes with a user, **Then** all my current recipes become accessible to that user
2. **Given** I have shared all recipes with a user, **When** I create a new recipe afterward, **Then** the new recipe is automatically accessible to that user without additional sharing actions
3. **Given** I am in my sharing settings, **When** I choose to share all meal plans with a user, **Then** all my current and future meal plans are accessible to them
4. **Given** I am in my sharing settings, **When** I choose to share all grocery lists with a user, **Then** all my current and future grocery lists are accessible to them
5. **Given** I have shared all items of a type with User A, **When** I also share a specific item of that type with User B, **Then** both shares coexist without conflict (User A sees all, User B sees only the specific item)

---

### User Story 3 - View and Interact with Shared Content (Priority: P2)

As a recipient of shared content, I want to see shared items in my dashboard with clear labels indicating who shared them, and be able to view or edit them according to my permission level, so I can collaborate effectively.

**Why this priority**: Without a clear way for recipients to find and interact with shared content, the sharing feature has no practical value to the recipient. It depends on sharing being set up (P1) but is essential for the overall feature to work.

**Independent Test**: Can be tested by logging in as a recipient, verifying shared items appear in the dashboard, and checking that read-only vs. read-write permissions are correctly enforced.

**Acceptance Scenarios**:

1. **Given** content has been shared with me, **When** I view my dashboard (recipes list, meal plans, or grocery lists), **Then** I see shared items alongside my own items, visually labeled with the owner's name
2. **Given** I have read-only access to a shared recipe, **When** I open that recipe, **Then** I can view all details but cannot edit, delete, or modify the recipe
3. **Given** I have read-write access to a shared grocery list, **When** I open that grocery list, **Then** I can add, check off, or remove items just like the owner can
4. **Given** I have read-write access to a shared meal plan, **When** I open that meal plan, **Then** I can add or remove recipe assignments and notes
5. **Given** I have read-write access to a shared recipe, **When** I open that recipe, **Then** I can edit the recipe details
6. **Given** content has been shared with me, **When** I view the shared item, **Then** I cannot share it further (only the owner can share)
7. **Given** content has been shared with me, **When** I view the shared item, **Then** I cannot delete it (only the owner can delete)

---

### User Story 4 - Manage and Revoke Sharing (Priority: P2)

As a content owner, I want to view all my active shares, change permission levels, and revoke access, so I maintain control over who can see and edit my content.

**Why this priority**: Management and revocation are essential for user control and trust, but secondary to the initial sharing and viewing flows.

**Independent Test**: Can be tested by viewing the sharing management screen, changing a user's permission from read-write to read-only, and verifying the change is enforced; then revoking access entirely and verifying the item disappears from the recipient's dashboard.

**Acceptance Scenarios**:

1. **Given** I have shared items with other users, **When** I navigate to my sharing management screen, **Then** I see a list of all active shares grouped by recipient, showing content type, item name (or "All"), and permission level
2. **Given** I am viewing my active shares, **When** I change a share's permission from read-write to read-only, **Then** the recipient's access is immediately downgraded
3. **Given** I am viewing my active shares, **When** I revoke a share, **Then** the recipient immediately loses access to that item
4. **Given** I have shared all recipes with a user and revoke the "share all" setting, **When** the recipient views their dashboard, **Then** they no longer see any of my recipes (unless individually shared)
5. **Given** I have shared a specific item and also have "share all" enabled for that type with the same user, **When** I revoke the specific item share, **Then** the user still has access through the "share all" setting

---

### User Story 5 - Receive Invitation to Join (Priority: P3)

As a person who doesn't have an account, when someone shares content with my email, I want to receive an invitation email that lets me sign up and immediately see the shared content, so I can start collaborating right away.

**Why this priority**: This is a growth mechanism and enhances usability, but the core sharing feature works without it (for existing users). It can be deferred without blocking the primary use cases.

**Independent Test**: Can be tested by sharing an item with a non-registered email, verifying the invitation email is sent, registering with that email, and confirming the shared content appears immediately.

**Acceptance Scenarios**:

1. **Given** a user shares content with an email that is not registered, **When** the share is created, **Then** an invitation email is sent to that address explaining that someone wants to share content with them
2. **Given** I received an invitation email, **When** I click the link and register with that email address, **Then** I immediately see all content that was shared with my email
3. **Given** content was shared with my email before I registered, **When** I register with a different email address, **Then** I do not see the shared content (shares are tied to the specific email)
4. **Given** a user shares multiple items with the same unregistered email, **When** the recipient registers, **Then** all pending shares are activated at once

---

### Edge Cases

- What happens when a user shares an item with their own email? The system should prevent self-sharing and display an appropriate message.
- What happens when a user tries to share the same item with the same person twice? The system should update the existing share's permission level rather than creating a duplicate.
- What happens when the owner deletes an item that was shared? The shared item should be removed from all recipients' dashboards as well.
- What happens when a "share all" user creates and then deletes an item? The item should follow normal deletion behavior; recipients lose access when the item is deleted.
- What happens when an invited (non-registered) user never signs up? The pending share remains but has no effect until registration. No repeated invitation emails are sent automatically.
- How does sharing interact with the existing anonymous token-based grocery list sharing? They coexist as separate mechanisms. Token-based sharing remains for anonymous/public access; user-to-user sharing is for authenticated collaboration.
- What happens if a recipient already has their own item with the same name? Both items appear in the dashboard; shared items are labeled with the owner's name to distinguish them.
- Can a read-write recipient share content further? No, only the original owner can share content.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST allow content owners to share specific recipes, meal plans, or grocery lists with another user by entering the recipient's email address
- **FR-002**: System MUST support two permission levels for sharing: read-only and read-write
- **FR-003**: System MUST allow owners to share all items of a content type (all recipes, all meal plans, or all grocery lists) with a user
- **FR-004**: When "share all" is enabled, the system MUST automatically include items the owner creates in the future
- **FR-005**: Shared content MUST appear immediately in the recipient's dashboard without requiring acceptance or confirmation
- **FR-006**: Shared items MUST be visually labeled in the recipient's dashboard to indicate the owner
- **FR-007**: Recipients with read-only permission MUST be able to view but not modify shared content
- **FR-008**: Recipients with read-write permission MUST be able to edit shared content (within scope of normal editing for that content type)
- **FR-009**: Recipients MUST NOT be able to delete or share content they do not own, regardless of permission level
- **FR-010**: System MUST allow owners to view all their active shares in a management screen
- **FR-011**: System MUST allow owners to change the permission level of an existing share
- **FR-012**: System MUST allow owners to revoke any share, immediately removing the recipient's access
- **FR-013**: When sharing with an unregistered email, system MUST send an invitation email and activate the share upon registration
- **FR-014**: System MUST prevent a user from sharing content with their own email
- **FR-015**: System MUST prevent duplicate shares; sharing with the same email for the same content should update the existing share's permission
- **FR-016**: System MUST update all existing policies (recipe, meal plan, grocery list) to grant appropriate access based on share permissions
- **FR-017**: Existing anonymous token-based grocery list sharing MUST continue to work independently alongside user-to-user sharing

### Key Entities

- **ContentShare**: Represents a sharing relationship between an owner and a recipient for a specific content type. Key attributes: the owner (who shared), the recipient (who receives access), the content type (recipe, meal plan, or grocery list), the specific item (nullable when sharing all of a type), the permission level (read-only or read-write), and whether this is a "share all" arrangement. When the specific item is null and "share all" is enabled, the share applies to all current and future items of that content type owned by the sharer.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Users can share a specific item with another user in under 30 seconds
- **SC-002**: Users can enable "share all" for a content type in under 30 seconds
- **SC-003**: Shared content appears in the recipient's dashboard immediately after sharing (no page refresh required beyond normal Livewire reactivity)
- **SC-004**: Shared items are visually distinguishable from owned items at a glance in all list views
- **SC-005**: Read-only recipients cannot modify any aspect of shared content (enforced at the policy level, not just UI)
- **SC-006**: Revoking a share removes the recipient's access immediately
- **SC-007**: Non-registered users who receive an invitation and register see all pending shared content upon first login

## Assumptions

- The three shareable content types are: recipes, meal plans, and grocery lists
- Only the content owner can initiate sharing; recipients cannot re-share
- User identity for sharing is tied to email address
- The existing anonymous token-based sharing for grocery lists is a separate feature and will not be modified or replaced
- "Share all" applies per content type, not across all content types at once (e.g., a user chooses to share all recipes separately from sharing all meal plans)
- There is no limit on the number of users content can be shared with
- There is no notification system beyond the initial invitation email for non-registered users (no in-app notifications for new shares to existing users in this iteration)
- Permission levels are simple: read-only or read-write. There is no "admin" or "co-owner" level
