# Research: Header Rebranding

**Date**: 2025-11-25
**Feature**: Rebrand Application Header
**Phase**: 0 - Research & Technical Decisions

## Overview

This document consolidates research findings for rebranding the application header from "Laravel Starter Kit" to "Project Table Top", including logo design considerations, SVG best practices for web, dark mode implementation patterns, and Blade component update strategies.

## Research Areas

### 1. Logo Design for "Project Table Top"

**Decision**: Create a simple, geometric SVG logo that represents tabletop gaming elements

**Rationale**:
- SVG format provides resolution independence and perfect scaling for responsive design
- Inline SVG allows easy color manipulation via CSS for dark mode support
- Simple geometric shapes load fast and scale well from mobile (16px) to desktop (48px+)
- The name "Project Table Top" suggests board games, D&D, tabletop RPGs - logo should evoke this theme

**Design Approach**:
- Use geometric shapes that suggest a tabletop view: dice, game board grid, or table surface
- Single-color design with `currentColor` fill enables automatic theme adaptation
- Maintain 1:1 aspect ratio for consistent sizing in square containers
- Provide clear visual distinction from the previous Laravel "L" logo

**Alternatives Considered**:
- **PNG/WebP raster images**: Rejected because they require multiple sizes for responsive design and cannot be styled via CSS
- **Icon font**: Rejected due to accessibility concerns and inability to create custom brand artwork
- **Complex multi-color SVG**: Rejected due to complexity in maintaining dark mode variants

### 2. SVG Implementation Best Practices

**Decision**: Use inline SVG with `currentColor` and Tailwind classes for styling

**Rationale**:
- Inline SVG in Blade components allows direct manipulation via Tailwind classes
- `currentColor` automatically inherits text color from parent, simplifying dark mode
- No additional HTTP requests compared to external SVG files
- Can pass attributes dynamically via Blade component (`{{ $attributes }}`)

**Implementation Pattern**:
```blade
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" {{ $attributes }}>
    <path fill="currentColor" d="..." />
</svg>
```

**Usage in Components**:
```blade
<x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
```

**Alternatives Considered**:
- **External SVG file with `<img>` tag**: Rejected because it cannot be styled via CSS
- **SVG sprite sheet**: Rejected due to unnecessary complexity for a single logo
- **Base64 encoded data URI**: Rejected due to readability and maintenance concerns

### 3. Dark Mode Support for Logos

**Decision**: Use Tailwind's `dark:` variant with `currentColor` for automatic adaptation

**Rationale**:
- Tailwind CSS 4.x provides robust dark mode support via `dark:` prefix
- Application already uses `class="dark"` on `<html>` tag for dark mode
- Using `currentColor` means logo inherits appropriate text color automatically
- No JavaScript required for theme switching

**Implementation Strategy**:
- Logo SVG uses `fill="currentColor"` for all paths
- Parent container applies text color: `text-white dark:text-black`
- Flux components already handle dark mode for backgrounds and borders
- Ensure logo remains visible on both `bg-zinc-50` (light) and `bg-zinc-900` (dark) backgrounds

**Color Contrast Requirements**:
- Light mode: Dark logo on light background (e.g., black on zinc-50)
- Dark mode: Light logo on dark background (e.g., white on zinc-900)
- Must maintain WCAG AA contrast ratio (4.5:1 minimum)

**Alternatives Considered**:
- **Two separate SVG files (light/dark)**: Rejected due to maintenance burden and additional HTTP requests
- **CSS filter for inversion**: Rejected because it doesn't work well with colored backgrounds
- **JavaScript theme detection**: Rejected because Tailwind + `currentColor` handles this automatically

### 4. Blade Component Update Strategy

**Decision**: Modify existing `app-logo.blade.php` and `app-logo-icon.blade.php` components only

**Rationale**:
- Centralized logo components are already used consistently throughout the application
- Single update point ensures brand consistency across all pages
- No need to modify individual page templates
- Existing component structure already supports attributes and Tailwind classes

**Components to Update**:

1. **`app-logo-icon.blade.php`**: Replace SVG paths with new logo design
2. **`app-logo.blade.php`**: Change text from "Laravel Starter Kit" to "Project Table Top"
3. **`layouts/app/header.blade.php`**: Remove search, repository, and documentation nav items (lines 31-53)
4. **`layouts/app/header.blade.php`**: Remove repository and documentation from mobile sidebar (lines 127-135)
5. **`partials/head.blade.php`**: Already uses `config('app.name')` - verify config value is updated

**Files NOT Requiring Changes**:
- Layout structure files (already reference components)
- Individual page templates (inherit from layouts)
- Flux component library (vendor files)

**Alternatives Considered**:
- **Create new components and deprecate old ones**: Rejected due to unnecessary complexity and migration effort
- **Hardcode logo in layout**: Rejected because it violates component-driven development principle
- **Use configuration for logo HTML**: Rejected due to poor maintainability

### 5. Page Title Strategy

**Decision**: Update `config/app.php` name value to "Project Table Top"

**Rationale**:
- `partials/head.blade.php` already uses `{{ $title ?? config('app.name') }}`
- Single configuration change updates all page titles application-wide
- Pages can still override with custom `$title` when needed
- Follows Laravel convention for application naming

**Configuration Change**:
```php
// config/app.php
'name' => env('APP_NAME', 'Project Table Top'),
```

**Environment Variable** (optional):
```env
APP_NAME="Project Table Top"
```

**Alternatives Considered**:
- **Update each layout/page individually**: Rejected due to maintenance burden and error-prone approach
- **Use translation files for branding**: Rejected because brand name is not translated
- **Store in database**: Rejected due to unnecessary complexity for static brand name

### 6. Responsive Logo Sizing

**Decision**: Use Tailwind size utilities with breakpoint variants where needed

**Rationale**:
- Existing implementation uses `size-8` (32px) for logo container and `size-5` (20px) for icon
- Tailwind's responsive utilities (e.g., `size-8 lg:size-10`) allow mobile/desktop variations
- Square aspect ratio maintained via `aspect-square` class
- Flux components handle responsive navbar collapse automatically

**Sizing Strategy**:
- **Mobile (< 1024px)**: `size-8` container, `size-5` icon (current implementation)
- **Desktop (>= 1024px)**: Same sizes work well, no changes needed
- **Logo must remain visible when navbar collapses to sidebar on mobile**

**Alternatives Considered**:
- **CSS media queries**: Rejected because Tailwind utilities are more maintainable
- **JavaScript-based responsive sizing**: Rejected due to unnecessary complexity
- **Different logo variants per breakpoint**: Rejected because single SVG scales perfectly

## Testing Strategy

### Pest Feature Tests

**What to Test**:
1. Header component renders "Project Table Top" text
2. Page title includes "Project Table Top" (or custom title)
3. Search, repository, and documentation links are NOT present
4. Logo component renders without errors

**Test Structure**:
```php
it('displays Project Table Top branding in header')
it('shows Project Table Top in page title')
it('does not show removed navigation links')
it('renders logo component successfully')
```

### Playwright E2E Tests

**What to Test**:
1. Visual verification: Header shows new branding on desktop and mobile
2. Logo is visible and properly sized across breakpoints (320px, 768px, 1024px, 1920px)
3. Dark mode toggle shows logo with appropriate contrast
4. Search, repository, documentation links are absent from DOM
5. Brand consistency across all main pages (dashboard, recipes, meal plans, grocery lists)

**Test Structure**:
```typescript
test('header displays Project Table Top branding')
test('logo is visible in light and dark mode')
test('logo scales correctly on mobile and desktop')
test('removed links are not present in header')
test('branding is consistent across all pages')
```

## Implementation Checklist

- [ ] Design new logo SVG for "Project Table Top" theme
- [ ] Update `app-logo-icon.blade.php` with new SVG
- [ ] Update `app-logo.blade.php` to change text to "Project Table Top"
- [ ] Remove search, repository, documentation links from desktop header
- [ ] Remove repository, documentation links from mobile sidebar
- [ ] Update `config/app.php` name to "Project Table Top"
- [ ] Write Pest feature tests (TDD - tests first!)
- [ ] Write Playwright E2E tests
- [ ] Run tests and verify all pass
- [ ] Visual QA in DDEV environment (light and dark mode)
- [ ] Run Laravel Pint for code formatting

## Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Logo not visible in dark mode | High - unusable UI | Test dark mode explicitly, use `currentColor` pattern, verify contrast ratios |
| Broken responsive layout | Medium - poor mobile UX | Test across breakpoints (320px, 768px, 1024px+), use Flux responsive utilities |
| Cached old branding in browser | Low - user confusion | Clear browser cache during testing, Vite HMR handles updates in dev |
| Missed logo reference in auth pages | Medium - inconsistent branding | Search codebase for all `<x-app-logo` usages, test authenticated and unauthenticated pages |
| Page title not updated everywhere | Low - inconsistent SEO/bookmarks | Verify `config('app.name')` is used consistently, test multiple page types |

## Related Documentation

- [Tailwind CSS v4 Dark Mode](https://tailwindcss.com/docs/dark-mode) - Dark mode implementation patterns
- [SVG Best Practices](https://css-tricks.com/svg-properties-and-css/) - Using currentColor and CSS styling
- [Livewire Flux Components](https://flux.laravel.com) - Navbar, header, sidebar component documentation
- [Laravel Blade Components](https://laravel.com/docs/12.x/blade#components) - Component patterns and attribute passing
- [WCAG Contrast Requirements](https://www.w3.org/WAI/WCAG21/Understanding/contrast-minimum.html) - Accessibility contrast ratios

## Conclusion

All technical decisions have been made and no further clarifications are needed. The implementation approach is straightforward:

1. Design a simple geometric SVG logo using `currentColor`
2. Update two logo components and one header layout file
3. Remove three navigation items from header and sidebar
4. Update application config for page titles
5. Write comprehensive tests (Pest + Playwright) before implementation

This approach maintains all constitutional principles, requires no database changes, and can be completed with minimal risk of breaking existing functionality.
