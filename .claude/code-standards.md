# Code Standards

## Style Guide
PSR-12 enforced via Laravel Pint

## Linting / Formatting

```bash
# Fix all formatting issues
vendor/bin/pint

# Fix only dirty (changed) files
vendor/bin/pint --dirty
```

Always run `vendor/bin/pint --dirty` before finalizing changes.

## PHP Rules
- Always use curly braces for control structures, even single-line
- Always declare explicit return types on methods and functions
- Use PHP 8 constructor property promotion
- No empty `__construct()` with zero params
- Prefer PHPDoc blocks over inline comments
- Add array shape type definitions in PHPDoc when appropriate
- Enum keys must be TitleCase (e.g., `Monthly`, `BestLake`)

## Laravel Conventions
- Use `php artisan make:` for all new files (models, migrations, components, etc.)
- Pass `--no-interaction` to Artisan commands
- Use Eloquent / `Model::query()` — avoid raw `DB::` queries
- Always eager-load to prevent N+1 queries
- Use Form Request classes for validation (not inline in components)
- Use named routes with `route()` for URL generation
- Use `config('key')` never `env()` outside config files
- Use queued jobs (`ShouldQueue`) for time-consuming operations
- Use Gates/Policies for authorization

## Naming Conventions
- Classes: `PascalCase`
- Methods/variables: `camelCase`, descriptive (e.g., `isRegisteredForDiscounts` not `discount()`)
- Constants: `SCREAMING_SNAKE_CASE`
- Files: match class name
- Test files: `PascalCaseTest.php`

## Livewire / Volt
- All new interactive pages use Livewire Volt (single-file components)
- Livewire components must have a single root element
- Use `wire:model.live` for real-time updates (deferred is default in v3)
- Use `wire:key` in all loops
- Use `$this->dispatch()` (not `emit`)
- Alpine.js is bundled with Livewire — do not import separately

## Frontend
- Use Flux UI components before building custom ones
- Only free Flux components are available (no Pro)
- Tailwind CSS v4 — use `@import "tailwindcss"`, not `@tailwind` directives
- Do not use deprecated Tailwind v3 utilities (see replacements in CLAUDE.md)
- Support dark mode (`dark:`) wherever light mode is styled
- Use `gap-*` for list spacing, not margins

## Pre-commit Checklist
- Run `vendor/bin/pint --dirty`
- Run relevant tests: `php artisan test --filter=...`
- No `env()` calls outside config files
- No `DB::` raw queries
- No inline validation (use Form Requests)
