# Task 001: Update Composer Constraints and Run Update

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Update `composer.json` version constraints to target Laravel 13.x and Livewire 4.x, then run `composer update` to install the new versions. This is the foundation for all subsequent tasks.

## Context
- File to modify: `composer.json`
- Current constraints:
  - `laravel/framework: ^12.0` → `^13.0`
  - `laravel/tinker: ^2.10.1` → `^3.0`
  - `livewire/livewire: ^3.7` → `^4.0`
  - `laravel/boost: ^1.5` → `^2.0` (in require-dev)
- Packages with no constraint change but getting updated to latest patch/minor:
  - `livewire/volt: ^1.7.0` — stays at ^1.7.0; installed 1.10.5 already supports `^4.0`
  - `livewire/flux: ^2.1.1` — stays at ^2.1.1; currently installed 2.12.1, will update to 2.13.2 (latest). No breaking changes between 2.12.x and 2.13.x.
  - `laravel/fortify: ^1.30` — stays
  - `pestphp/pest: ^4.1` — stays

## Requirements (Test Descriptions)
- [x] `it updates laravel/framework constraint to ^13.0 in composer.json`
- [x] `it updates laravel/tinker constraint to ^3.0 in composer.json`
- [x] `it updates livewire/livewire constraint to ^4.0 in composer.json`
- [x] `it updates laravel/boost constraint to ^2.0 in require-dev`
- [x] `it runs composer update successfully with the new constraints`
- [x] `it shows Laravel 13.x version when running php artisan about`
- [x] `it shows Livewire 4.x version when running php artisan about`
- [x] `it updates livewire/flux to 2.13.2 in composer.lock`

## Acceptance Criteria
- All requirements have passing tests
- `composer.json` has updated constraints
- `composer.lock` reflects new installed versions
- `php artisan about` confirms Laravel 13.x
- No composer dependency conflicts

## Implementation Notes
Run: `composer update laravel/framework laravel/tinker livewire/livewire livewire/flux livewire/volt laravel/boost --with-all-dependencies`

Flux 2.x is the latest major version — no constraint change needed in composer.json. The `^2.1.1` constraint already allows updating from 2.12.1 → 2.13.2.

If dependency conflicts occur, check if other packages need constraint loosening.
