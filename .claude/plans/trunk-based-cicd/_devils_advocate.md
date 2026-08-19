# Devil's Advocate Review: trunk-based-cicd

Reviewed against `.claude/skills/plan-orchestrate/SKILL.md`, `.claude/agents/tdd-worker.md`, the five real
workflow files in `.github/workflows/`, `composer.json`, `phpunit.xml`, and `tests/Pest.php`.

## Critical (Must fix before building)

### C1 — Tasks 003 and 004 cannot "pause for user confirmation"; the executor has no mechanism for it
**Tasks: 003, 004, _plan.md Architecture Notes**

`.claude/agents/tdd-worker.md` declares `tools: Read, Write, Edit, Bash, Glob, Grep`. There is no
user-interaction tool. `plan-orchestrate` states "Fully autonomous after invocation" and Step 0 optionally
wraps the whole run in `/ralph-wiggum:loop` — i.e. it may run with no human attached at all. A worker told
to "stop before the `gh api` PATCH call and wait for a go-ahead in that session" has exactly two reachable
outcomes:

1. It treats the requirement as unsatisfiable → `TASK_FAILED` → 3 retries → `blocked`. Because 004 depended
   on 003, both tasks then dead-end and the plan reports `TASKS_BLOCKED: [003, 004]`.
2. It rationalises the instruction away and runs `gh api -X PUT .../protection` and
   `git push origin --delete develop` unattended — an irreversible-ish admin change to shared repo settings
   plus a remote branch deletion, made by a subagent, with no human in the loop.

Neither is acceptable. There is also a second-order hazard in outcome 2: `enforce_admins: true` applied
mid-run changes the rules for the plan's own PR while the orchestrator is still working.

**Fix applied:** 003 and 004 are now fully autonomous *preparation* tasks. They perform only read-only
`gh api` GET / `git`/`gh pr list` queries and write files (`_manual-steps.md`, `branch-protection.json`,
`branch-protection.before.json`, `_branch-cleanup-checklist.md`). Both carry an explicit
"do not run any mutating command" guardrail listing the forbidden verbs. The actual `PUT` and the actual
`develop` deletion moved to a new `## Manual Steps (human-run — NOT executed by plan-orchestrate)` section
in `_plan.md`, to be run by the user after the plan's PR merges.

### C2 — "Merge the scratch PR to confirm the flow works" would trigger a real production deploy
**Task: 003 (requirement 4), 001 (requirement 3)**

`.github/workflows/deploy.yml` triggers on `push: main` and POSTs `secrets.LARAVEL_CLOUD_DEPLOY_HOOK`.
*Any* merge to `main` — including merging a throwaway "touch a scratch file" PR created purely to prove
branch protection works — ships that commit to Laravel Cloud production. Task 003 asked a worker to do
exactly that ("confirm it can be merged once `ci` and `playwright` are green").

**Fix applied:** all scratch-PR-and-merge verification removed from 001 and 003. The plan's own PR is the
observation point for CI triggering; the direct-push rejection check moved to the manual runbook as a
push-only check (rejected push mutates nothing).

### C3 — The described branch-protection update recipe will 422 or silently strip settings
**Task: 003 (Context, "read the current full object first, modify only the two fields, then PUT the whole
thing back")**

The GET response shape for `/branches/{branch}/protection` is not the PUT request shape:

| Field | GET returns | PUT expects |
|---|---|---|
| `enforce_admins` | `{"url": ..., "enabled": false}` | `true` / `false` |
| `allow_force_pushes` | `{"enabled": false}` | `false` |
| `allow_deletions` | `{"enabled": false}` | `false` |
| `required_status_checks` | includes `url`, `contexts_url`, `checks[]` | only `strict` + `contexts` (or `checks`) |
| `restrictions` | absent when unset | must be present and explicitly `null` |
| `required_pull_request_reviews` | absent when unset | must be present, object or `null` |

Round-tripping the GET body into a PUT fails validation, and any nullable key *omitted* from the PUT is
treated as "disable this protection" — so a naive edit can silently drop `required_status_checks`, which is
the opposite of this plan's goal.

**Fix applied:** 003 now specifies the exact hand-built PUT body, requires `strict` to be copied from the
live GET (its current value was never captured during planning), and requires `restrictions: null`.

### C4 — Pennant's default store is `database`, not `array`, and it needs a migration
**Task: 002 (Context line: "`array` in-memory by default, no migration required")**

`config/pennant.php` ships `'default' => env('PENNANT_STORE', 'database')`. Requirement 3
(`Feature::define(...)` then `Feature::active(...)`) resolves through the default store, which writes to a
`features` table. `tests/Pest.php` applies `RefreshDatabase` to the whole `Feature` suite against
`:memory:` SQLite, so with no published/committed migration the test dies with
`no such table: features`. The stated-but-wrong premise pushes a worker toward the one fix the acceptance
criteria forbid (switching the default driver to `array` in `config/pennant.php`).

**Fix applied:** 002 now requires publishing the package's config + migration and running `php artisan
migrate`, states the real default store, and adds the migration file to the deliverables.

### C5 — No guardrail if `laravel/pennant` has no Laravel 13-compatible release
**Task: 002**

`composer.json` pins `laravel/framework: ^13.0`. If the installed Pennant tag does not yet allow
`illuminate/* ^13`, `composer require laravel/pennant` aborts. The task's acceptance criterion assumes
success and gives the worker no failure path, so a worker under retry pressure may reach for
`-W` / `--ignore-platform-reqs` / an explicit older constraint — any of which can cascade a framework
downgrade across the entire lockfile, breaking a repo that just finished a Laravel 13 upgrade.

**Fix applied:** explicit banned-flags list plus a `TASK_FAILED` escape hatch carrying `composer why-not`
output.

## Important (Should fix before building)

### I1 — `enforce_admins: true` + required checks makes every security-loop PR permanently unmergeable
**Task: 003, _plan.md Risks**

`.github/workflows/security-loop.yml` opens its PR with `GH_TOKEN: ${{ secrets.GITHUB_TOKEN }}`
(line 130, `gh pr create` at line 141). GitHub deliberately does not fire `pull_request` workflow runs for
PRs created by `GITHUB_TOKEN` (recursion guard). Those PRs therefore never get a `ci` or `playwright`
check run at all — the required contexts sit permanently "Expected", and with `enforce_admins: true` even
the repo owner cannot merge or override. The weekly security control loop silently stops being able to
land anything.

**Fix applied:** documented in `_plan.md` Risks and in the manual runbook, with the cheap workaround
(close + reopen the bot PR by hand to fire the checks) and the durable one (create the PR with a PAT
stored as a secret).

### I2 — Task 001's scratch-PR verification proves nothing
**Task: 001 (requirement 3)**

A branch cut from `main` does not contain the retargeted trigger, and for `pull_request` events GitHub
evaluates the workflow from the PR's merge ref. Worse: both the old (`[develop, main]`) and new (`[main]`)
filters match a PR targeting `main`, so the observed result is identical either way. The check cannot
distinguish pass from fail.

**Fix applied:** replaced with a static assertion test plus "observe the plan's own PR" (which does contain
the change).

### I3 — Task 001 has no RED/GREEN surface, so the TDD worker will invent one
**Task: 001**

`tdd-worker.md` is strict: "For EACH unchecked requirement… write a failing test… NEVER write
implementation code before a failing test exists." Requirements phrased as `grep` invocations give the
worker nothing to fail, and it will improvise — plausibly a Pest test that shells out to `gh`, which then
fails inside `tests.yml` where no `gh` credentials exist.

**Fix applied:** 001 now specifies two concrete Pest tests in `tests/Feature/CiWorkflowTargetsTest.php`
(no `develop` anywhere under `.github/workflows/`; the `ci`/`playwright`/`quality` job IDs still exist),
matching the existing `tests/Feature/ComposerConstraintsTest.php` convention for config-invariant tests.
Deliberately string/regex based — there is no YAML parser guaranteed present in this dependency set
(`symfony/yaml` is not a transitive requirement of `laravel/framework` or `pestphp/pest` here).

### I4 — Dependency `004 → 003` is wrong and dangerous
**Tasks: 003, 004, _plan.md task table**

Deleting `develop` has no technical dependency on branch protection of `main`. The real prerequisite is
001 (no workflow targets `develop` any more). Under the original chain, 003 blocking (which C1 shows was
the likely outcome) also killed 004.

**Fix applied:** 004 now depends on 001. 003 keeps its dependency on 001 so the captured
`required_status_checks.contexts` cannot go stale against a renamed job.

### I5 — Pushing `.github/workflows/` changes requires the `workflow` OAuth scope
**Task: 001**

If the local remote is HTTPS with a `gh`-issued credential lacking the `workflow` scope, the push is
rejected with "refusing to allow an OAuth App to create or update workflow" — a confusing failure at the
very end of an otherwise-green task.

**Fix applied:** noted in 001 with the one-line remedy (`gh auth refresh -s workflow`).

### I6 — Task 004's merge detection uses local refs for remote-only branches
**Task: 004**

`git branch --merged main` only considers local branches; nearly all ~30 enumerated branches exist only as
`origin/*` in a typical clone, so the "merged?" column would be blank or wrong for most rows. Also, one
`gh pr list --head <branch>` per branch is ~30 sequential API round trips.

**Fix applied:** 004 now specifies `git fetch --prune` + `git branch -r --merged origin/main`, and a single
`gh pr list --state all --json number,headRefName,state,url --limit 200` joined locally.

### I8 — Plan-directory artifacts could be re-parsed as task files
**Tasks: 003, 004**

`plan-orchestrate` Step 1 reads `.claude/plans/{plan-name}/*.md` and parses *every* file that isn't
`_plan.md` as a task. Tasks 003/004 create new `.md` files inside that same directory *during* execution,
so a ralph-wiggum restart mid-plan would re-parse `manual-steps.md` / `branch-cleanup-checklist.md` as
task files with no number and no status.

**Fix applied:** artifacts follow the existing `_`-prefix convention (`_manual-steps.md`,
`_branch-cleanup-checklist.md`), matching `_plan.md` / `_devils_advocate.md`, with a note in both task
files explaining why the underscore is load-bearing.

### I7 — Success criterion 2 in `_plan.md` is not verifiable as written
**File: `_plan.md`**

"A direct `git push` to `main` (simulated against a disposable protection-check, not actually forced
against this repo's real `main` during testing)" describes a test that does not exist and cannot be run by
a worker. **Fix applied:** reworded to a manual-runbook step (attempt the push, expect `GH006`, nothing is
mutated when it is rejected), and the criterion now says so.

## Minor (Nice to address)

- **`lint.yml`'s `quality` job can never fail.** It runs `vendor/bin/pint` (auto-fix mode, exit 0) and the
  `Commit Changes` step is commented out (lines 47-54). It is a no-op gate today. Out of scope per the
  plan, but if `quality` is ever promoted to a required status check it would rubber-stamp anything.
  `vendor/bin/pint --test` is the one-word fix.
- **`grep -c "main"` in old 001 requirement 2** counted lines containing the substring "main", which is not
  "one `branches:` entry" — removed during the rewrite anyway.
- **`deploy.yml` keeps `workflow_dispatch`**, an unprotected manual deploy path. It deploys `main` HEAD,
  which after hardening is always checked code, so this is fine — just noting it is not covered by branch
  protection.
- **Task 002 acceptance says run the full suite.** `php artisan test` includes `tests/Browser/` (Pest 4
  browser tests) which need a local browser; `--testsuite=Unit,Feature` is the faster regression signal
  locally. CI runs the full `./vendor/bin/pest` anyway.
- **`.env.example` gets no `PENNANT_STORE` entry** — unnecessary, the config default covers it.

## Questions for the Team

1. **Does Laravel Cloud run `php artisan migrate --force` on deploy?** If not, the Pennant `features` table
   never gets created in production. Harmless while zero flags are defined, but it becomes a production
   incident the first time a flag is resolved. Worth confirming before the first real flag ships.
2. **`required_status_checks.strict`** (require branches to be up to date before merging) — its current
   value was never captured during planning. Task 003 now copies it verbatim, but if it is `true`, every
   merge after another merge requires a rebase, which is noticeable friction for a solo trunk workflow. Do
   you want it explicitly set to `false`?
3. **Should the manual runbook be applied before or after this plan's own PR merges?** The tasks assume
   after (so the plan's merge is not blocked by rules created mid-flight). Applying it before is better
   dogfooding but means this plan's PR must itself go green on `ci` + `playwright` first.
4. **Should `quality` (lint) become a required check?** Explicitly out of scope in the plan, but it is the
   obvious follow-up — and per the Minor note above, it needs `pint --test` first to mean anything.
5. **Long-term security-loop fix (I1):** add a PAT secret so bot PRs get CI, or accept manual
   close/reopen? The former needs a token with `repo` + `workflow` scope stored in repo secrets.
