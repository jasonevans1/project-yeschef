# Plan: Trunk-Based CI/CD Redesign

## Created
2026-08-18

## Status
completed

## Objective
Collapse this repo's branching model onto a single trunk (`main`) with GitHub-native guarantees that nothing unchecked reaches production, so short-lived HCF plan branches (opened per plan, merged same session) replace the current mix of a permanent `develop` branch and long-lived feature branches.

## Related Issues
none

## Discovery Notes
- Confirmed with user: `develop` deploys nowhere — it exists only as a second PR-gate target, with no distinct environment behind it. Safe to retire once workflows no longer reference it.
- `tests.yml`, `lint.yml`, `e2e.yml` all currently trigger on `pull_request` to `[develop, main]`. `deploy.yml` triggers independently on `push: main` with no dependency on the other workflows.
- Branch protection on `main` (checked via `gh api repos/:owner/:repo/branches/main/protection`) requires status checks `ci` (tests.yml) and `playwright` (e2e.yml), has `enforce_admins: false`, and — critically — has **no `required_pull_request_reviews` block**, meaning "require a pull request before merging" is not actually turned on. Required status checks only gate PR merges in GitHub; they do not block a direct `git push` to the branch. So today, a direct push to `main` (by anyone with write access) bypasses `tests.yml`/`e2e.yml` entirely and still triggers `deploy.yml`. This is the real gap behind "deploy could ship a broken build" — not workflow ordering.
- `develop` has no branch protection at all (404 on the protection endpoint).
- No feature-flag package installed (`laravel/pennant` absent from `composer.json`). Needed because short-lived plan branches merging straight to `main` mean incomplete work must be dark-launchable rather than hidden on a branch.
- Repo currently has ~30 long-lived branches beyond `develop` (numbered spec branches, feat/*, fix/*, upgrade/laravel-13-livewire-4, etc.) that predate this redesign. They are not part of this plan's automated scope — deleting branches is destructive and each one may hold unmerged work worth reviewing.
- This plan covers `project-yeschef` (the current app's repo) only. The planned Laravel 13 rewrite repo is a separate, later workstream and reuses this same pattern once it exists.

## Scope

### In Scope
- Retarget `tests.yml`, `lint.yml`, `e2e.yml` to trigger on `main` only (drop `develop`), guarded by a config-invariant Pest test.
- **Prepare** the hardening of `main` branch protection so it structurally requires a passing PR (ci + playwright) before any commit can land — closing the direct-push gap, with `enforce_admins: true` so there's no bypass. This is what actually guarantees `deploy.yml` never fires on unchecked code, without touching `deploy.yml`'s trigger or restructuring workflow files. The tasks produce the exact PUT body and a runbook; **the user applies it by hand** (see Manual Steps).
- Install `laravel/pennant` and verify it boots (minimal Pest test), so incomplete work can be dark-launched once it lands on `main` via short-lived plan branches.
- **Prepare** the deletion of the `develop` branch (confirmed safe — deploys nowhere): the command and its pre-checks land in the cleanup checklist; the user runs it.
- Produce an explicit manual-decision checklist for the ~30 pre-existing long-lived branches (merge / abandon / delete per branch) — the user acts on this by hand, it is not auto-executed.

### Out of Scope
- The Laravel 13 rewrite repo and its CI/CD (separate future plan).
- **Any mutating GitHub or git operation performed by a task.** Applying the protection rule and deleting `develop` are Manual Steps run by the user; tasks only prepare them. Deleting or merging any branch other than `develop` is listed for the user's manual decision only.
- Fixing the security-loop bot-PR consequence (see Risks) — documented, not actioned.
- Building actual feature flags with Pennant (only installing and proving it boots).
- Changing `lint.yml`'s (`quality` job) status from optional to required — not requested; noted as a possible follow-up, not actioned here.
- Any merge-queue tooling, new GitHub Apps, or third-party CI services.

## Success Criteria

### Automated (tasks 001-004, run by plan-orchestrate)
- [ ] `tests.yml`, `lint.yml`, `e2e.yml` trigger only on PRs targeting `main`; no reference to `develop` remains anywhere in `.github/workflows/`, asserted by `tests/Feature/CiWorkflowTargetsTest.php`.
- [ ] `laravel/pennant` is installed, its migration is published and applied, its service provider boots, and passing Pest tests prove it.
- [ ] `.claude/plans/trunk-based-cicd/branch-protection.json` (a valid GitHub **PUT**-shaped body), `branch-protection.before.json` (live snapshot), and `_manual-steps.md` (apply / verify / rollback runbook) exist.
- [ ] `.claude/plans/trunk-based-cicd/_branch-cleanup-checklist.md` exists, enumerates every remote branch with merged status, PR link, last-commit date and a recommended action, and carries the `develop` deletion command with its pre-checks.
- [ ] Nothing was mutated on GitHub by any task: repo settings unchanged, `origin/develop` still present, no scratch PR opened, nothing merged to `main`.
- [ ] All tests passing.
- [ ] Code follows project standards (`vendor/bin/pint --dirty`).

### Manual (user, after the plan's PR merges — see Manual Steps)
- [ ] `main` branch protection requires a PR with passing `ci` and `playwright` checks, and applies to admins (`enforce_admins: true`).
- [ ] A direct `git push` to `main` is rejected with `GH006: Protected branch update failed` (a rejected push mutates nothing, so this check is safe to run for real).
- [ ] `develop` branch is deleted.

## Task Overview
| Task | Description | Depends On | Status |
|------|-------------|------------|--------|
| 001 | Retarget CI workflows to `main` only (+ invariant test) | - | completed |
| 002 | Install and verify `laravel/pennant` | - | completed |
| 003 | Prepare `main` branch-protection hardening (PUT body + runbook; applies nothing) | 001 | completed |
| 004 | Prepare `develop` retirement + stale-branch cleanup checklist (deletes nothing) | 001 | completed |

## Manual Steps (human-run — NOT executed by plan-orchestrate)
Run these **after** the plan's own PR merges, so this plan's merge isn't blocked by rules introduced mid-flight. Tasks 003 and 004 exist only to prepare them.

1. **Harden `main` protection** — follow `.claude/plans/trunk-based-cicd/_manual-steps.md`: review the before/after table, run the single `gh api -X PUT … --input branch-protection.json`, verify, then run the direct-push rejection check. Rollback is one command (`gh api -X DELETE …/protection/enforce_admins`).
2. **Retire `develop`** — follow the "Retire `develop`" section at the top of `.claude/plans/trunk-based-cicd/_branch-cleanup-checklist.md`: confirm no open PRs target it, then `git push origin --delete develop`, then confirm the 404.
3. **Work through the branch table** in that same checklist at your own pace. Nothing there is urgent or automated.

## Architecture Notes
- No new workflow files and no `workflow_run` chaining: `deploy.yml` keeps its existing `push: main` trigger untouched. The guarantee that deploy only ever sees checked code comes entirely from branch protection making it impossible for an unchecked commit to reach `main` in the first place — a GitHub-native setting, not application code.
- **Why 003/004 prepare rather than apply:** `tdd-worker` subagents have `Read, Write, Edit, Bash, Glob, Grep` and no user-interaction tool, and `plan-orchestrate` is "fully autonomous after invocation" (optionally wrapped in a ralph-wiggum loop with no human attached). A task that says "stop and wait for confirmation" therefore either dead-ends at `TASK_FAILED` → `blocked`, or gets rationalised away and the worker mutates shared repo settings unattended. Splitting into autonomous preparation + a human-run runbook is the only shape that works with this executor. Both tasks carry an explicit forbidden-verb list (`gh api -X PUT/POST/PATCH/DELETE`, `git push`, `gh pr create/merge`) instead of a confirmation prompt.
- **Never merge a scratch PR as a verification step.** `deploy.yml` fires on `push: main` and POSTs the Laravel Cloud deploy hook, so merging a throwaway commit ships it to production. The plan's own PR is the only place the retargeted triggers get observed; the direct-push check is safe precisely because a *rejected* push mutates nothing.
- `laravel/pennant` install follows the same pattern as any other Composer dependency addition in this codebase: `composer require`, publish + run its migration (its default store is `database`, not `array`), confirm via a boot-smoke Pest test — no custom scaffolding beyond that.

## Risks & Mitigations
- **Hardening branch protection could lock the user out of quick direct pushes they currently rely on.** Mitigation: the change is applied by hand from a runbook, never by a worker; `enforce_admins: true` reverts with one `gh api -X DELETE …/protection/enforce_admins` call.
- **`enforce_admins: true` + required checks makes the weekly security-loop PRs unmergeable.** `security-loop.yml` opens its PR with `secrets.GITHUB_TOKEN`, and GitHub does not fire `pull_request` workflow runs for PRs created by that token — so `ci`/`playwright` never report on them, the required contexts sit "Expected" forever, and with admin enforcement on, nobody can override. Mitigation: documented in the runbook with two workarounds — close-and-reopen the bot PR by hand to fire its checks (zero setup), or switch `security-loop.yml` to a PAT secret for `git push`/`gh pr create` (durable fix, needs a token with `repo` + `workflow` scope). Not actioned in this plan.
- **A naive branch-protection update can silently strip existing protections.** The GET response shape differs from the PUT request shape (`enforce_admins` object vs bool, `restrictions` must be explicitly `null`, omitted nullable keys mean "disable"). Mitigation: Task 003 hand-builds the PUT body in the correct shape and snapshots the before-state to `branch-protection.before.json`.
- **Deleting `develop` could orphan in-flight work if anything currently targets it.** Mitigation: Task 004 records the `gh pr list --base develop --state open` result in the checklist and blocks the recommendation if any PR is open; the user runs the deletion.
- **`laravel/pennant` may have no release compatible with `laravel/framework ^13.0`.** Mitigation: Task 002 bans `-W`, `--ignore-platform-reqs` and pinned-older constraints (any of which can cascade a framework downgrade across the lockfile) and fails the task cleanly with `composer why-not` output instead.
- **Retargeting workflows to `main`-only means PRs opened against stale branches (e.g., anything still based on `develop`) stop getting CI feedback.** Mitigation: this is expected once `develop` is retired — call it out in the cleanup checklist so the user rebases any branch they intend to keep.
