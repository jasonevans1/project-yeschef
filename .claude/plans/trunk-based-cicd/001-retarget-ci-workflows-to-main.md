# Task 001: Retarget CI Workflows to `main` Only

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Remove `develop` as a trigger target from every workflow that currently gates on it, so `main` is the only branch CI runs against. This is the mechanical first step of collapsing to a single trunk — `develop` deploys nowhere, so there is nothing behind it worth keeping a duplicate gate for.

## Context
- Related files: `.github/workflows/tests.yml`, `.github/workflows/lint.yml`, `.github/workflows/e2e.yml`, plus a new test file `tests/Feature/CiWorkflowTargetsTest.php`.
- Do NOT touch `.github/workflows/deploy.yml` (already correctly targets `main` only) or `.github/workflows/security-loop.yml` (unrelated to branch targeting — verified: neither file contains the string `develop`).
- Current trigger block in all three files:
  ```yaml
  on:
    pull_request:
      branches:
        - develop
        - main
  ```
- Target trigger block:
  ```yaml
  on:
    pull_request:
      branches:
        - main
  ```
- Job IDs must NOT change (`ci` in tests.yml, `quality` in lint.yml, `playwright` in e2e.yml) — Task 003 relies on `ci` and `playwright` remaining the exact required-status-check context names GitHub already recognizes for `main`'s branch protection. Renaming a job ID here would silently break required status checks.
- **Test style:** assert on file contents with plain string/regex checks. Do NOT reach for a YAML parser — neither `symfony/yaml` nor `ext-yaml` is a guaranteed dependency of this project (`laravel/framework` and `pestphp/pest` do not pull `symfony/yaml` in transitively here), and adding a dependency just to assert on config is out of scope. Follow the style of `tests/Feature/ComposerConstraintsTest.php`, this repo's existing convention for config-invariant tests: `declare(strict_types=1);`, plain `test('...', function () { ... })`, read files with `file_get_contents(base_path(...))`.
- **Push gotcha:** commits touching `.github/workflows/` require the `workflow` OAuth scope. If the push is rejected with "refusing to allow an OAuth App to create or update workflow", run `gh auth refresh -s workflow` and retry — that is a credential problem, not a code problem.
- **Do NOT open or merge a scratch PR to verify this.** Merging anything into `main` fires `deploy.yml`, which POSTs the Laravel Cloud deploy hook — i.e. it ships that commit to production. The plan's own PR is the place where the retargeted trigger gets observed, and that happens outside this task.

## Requirements (Test Descriptions)
Write these in a new `tests/Feature/CiWorkflowTargetsTest.php`. They are the RED/GREEN surface for this task; the workflow YAML edits are the implementation that turns them green.

- [x] `it has no workflow that references the develop branch` — glob every `*.yml` under `.github/workflows/`, assert none of their contents contain the string `develop`. (This is the plan's actual success criterion, and it is repo-wide, not limited to the three edited files.)
- [x] `it triggers ci workflows only on pull requests targeting main` — for each of `tests.yml`, `lint.yml`, `e2e.yml`, assert the contents match `/on:\s*\n\s*pull_request:\s*\n\s*branches:\s*\n\s*-\s*main\s*\n/` and that no second `- ` entry follows inside the `branches:` block.
- [x] `it preserves the required status check job ids` — assert `tests.yml` contains a `ci:` job key, `e2e.yml` contains a `playwright:` job key, and `lint.yml` contains a `quality:` job key. These are the context names GitHub already has registered on `main`'s branch protection; Task 003 re-sends them verbatim. **This one passes on first run by design** — it is a regression guard against a future rename, not a driver of new code. Note that in Implementation Notes and move on; do not manufacture a failure for it.

## Acceptance Criteria
- All three workflow files trigger only on `pull_request: branches: [main]`.
- No job IDs changed.
- `tests/Feature/CiWorkflowTargetsTest.php` exists and all three tests pass.
- No scratch branch or scratch PR was created, and nothing was merged into `main` by this task.
- `vendor/bin/pint --dirty` run on the new test file.

## Implementation Notes
- Removed the `- develop` line from the `pull_request.branches` list in `tests.yml`, `lint.yml`, and `e2e.yml`; left `deploy.yml` and `security-loop.yml` untouched (confirmed neither references `develop`).
- Job IDs unchanged (`ci`, `quality`, `playwright`).
- `it preserves the required status check job ids` passed on first run by design (regression guard, not a RED/GREEN driver), as anticipated in the task spec.
- Ran `php artisan test tests/Feature/CiWorkflowTargetsTest.php` — all 3 tests pass. Ran `vendor/bin/pint --dirty` — 1 file, no style issues.
- No scratch branch/PR created; stayed on `feature/trunk-based-cicd`; no push performed.
