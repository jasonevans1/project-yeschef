# Data Model: Header Rebranding

**Date**: 2025-11-25
**Feature**: Rebrand Application Header
**Phase**: 1 - Design

## Summary

This feature involves **no data model changes**. All modifications are to presentation layer components (Blade templates and SVG assets) and application configuration.

## Rationale

The header rebranding feature is purely a UI/presentation change that involves:

1. **Static template updates**: Changing text and markup in Blade component files
2. **Asset replacement**: Swapping logo SVG artwork
3. **Configuration update**: Modifying the application name in `config/app.php`
4. **Template removal**: Deleting navigation elements from header layout

None of these changes require:
- Database schema modifications
- New Eloquent models
- Data migrations
- Relationships or associations
- Data validation rules
- State management beyond what already exists

## Related Entities (Reference Only)

While no new entities are created, the feature interacts with existing application entities:

### User (Existing)
- **Relationship to feature**: Users see the rebranded header when authenticated
- **No changes required**: User model remains unchanged
- **Context**: Header displays user profile dropdown (existing functionality preserved)

### Configuration (Existing)
- **File**: `config/app.php`
- **Change**: Update `'name'` value from current name to "Project Table Top"
- **Type**: Static configuration, not a database entity
- **Impact**: Used by `config('app.name')` in page title rendering

## Component Structure (Presentational Only)

The following components are modified but do not represent data entities:

### App Logo Component
- **File**: `resources/views/components/app-logo.blade.php`
- **Type**: Blade presentation component
- **Purpose**: Displays brand text and logo icon
- **Change**: Text content update only

### App Logo Icon Component
- **File**: `resources/views/components/app-logo-icon.blade.php`
- **Type**: Blade presentation component (SVG)
- **Purpose**: Renders logo graphic
- **Change**: SVG artwork replacement

### Header Layout Component
- **File**: `resources/views/components/layouts/app/header.blade.php`
- **Type**: Blade layout component
- **Purpose**: Structures header navigation
- **Change**: Removal of navigation items (search, repository, documentation)

## Conclusion

**No data model document is required for this feature.** This file serves as documentation that the absence of a data model is intentional and appropriate for a presentation-layer-only change.

For implementation details, refer to:
- [research.md](./research.md) - Technical decisions and implementation patterns
- [quickstart.md](./quickstart.md) - Developer guide for making the changes
- [spec.md](./spec.md) - Original feature specification
