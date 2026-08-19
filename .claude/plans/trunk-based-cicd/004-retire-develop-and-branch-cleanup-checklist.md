# Task 004: Prepare `develop` Retirement + Branch Cleanup Checklist (prepare only — deletes nothing)

**Status**: completed
**Depends on**: 001
**Retry count**: 0

## Description
Produce everything needed to retire the now-unreferenced `develop` branch (confirmed with the user to deploy nowhere and, after Task 001, no longer targeted by any workflow), plus an explicit checklist of every other pre-existing long-lived branch so the user can decide — by hand — what to merge, abandon, or delete.

**This task deletes nothing.** The `develop` deletion command goes into the checklist document for the user to run; the actual deletion is a manual step in `_plan.md`. See "Guardrail".

## Guardrail (read first)
- You are a `tdd-worker` subagent with `Bash` but **no way to ask a human anything** — there is no user-interaction tool in your toolset and `plan-orchestrate` may be running fully unattended. "Present the command and wait for confirmation" is not executable here. Do not attempt it, and do not proceed without it either.
- **Forbidden in this task, no exceptions:** `git push` (in any form, especially `--delete`), `git branch -d` / `-D` against `develop`, `gh api -X DELETE`, `gh pr merge`, `gh pr close`. Read-only `git` and `gh` queries plus local file writes only.
- Every recommendation you write is advice for the user. You act on none of it.

## Context
- Deliverable: `.claude/plans/trunk-based-cicd/_branch-cleanup-checklist.md`. No application code is touched. (Leading underscore matters: `plan-orchestrate` parses every non-`_plan.md` `*.md` in the plan directory as a task file, so plan-dir artifacts follow the `_`-prefix convention already used by `_plan.md` and `_devils_advocate.md` to stay out of the task graph.)
- Branch inventory at plan creation time — **re-derive it, do not trust this list**, more branches may have appeared: `001-build-an-application`, `001-grocery-item-lookup`, `001-meal-plan-drawer`, `002-update-the-spec`, `003-merge-the-2`, `004-rebrand-header`, `005-delete-grocery-list`, `006-import-recipe`, `007-format-ingredient-quantities`, `008-recipe-ingredient-checkboxes`, `009-recipe-servings-multiplier`, `010-meal-plan-notes`, `011-share-content`, `012-grocery-list-categories`, `ci/security-loop-schedule`, `feat/fraction-quantity-display`, `feat/grocery-category-migration`, `feat/grocery-list-item-exclusions`, `feat/import-recipe-edit-before-save`, `feat/ingredient-category-resolution`, `feat/meal-plan-mobile-layout`, `feat/pwa-home-screen-icon`, `feat/recipe-link-on-grocery-item`, `feat/recipe-source-url-display`, `feature/grocery-list-archive-remove`, `feature/secure-recipe-import`, `feature/torch-visual-refresh`, `fix-recipe-mobile-issues`, `fix/grocery-autocomplete-item-name`, `install-claude-skills-agents`, `upgrade/laravel-13-livewire-4`. (`feature/recipe-import-paste-html` is active in-progress work per recent commits — mark it "leave alone — active work" rather than recommending an action.)
- **Use remote refs, not local ones.** Nearly all of these exist only as `origin/*` in a typical clone, so `git branch --merged main` (local-only) would leave the "merged?" column blank or wrong for most rows. Correct commands:
  - `git fetch --prune origin` first, so deleted-upstream branches don't appear as live rows.
  - Inventory: `git branch -r --format='%(refname:short)'`
  - Merged status: `git branch -r --merged origin/main --format='%(refname:short)'` (set-intersect against the inventory)
  - Last commit date: `git for-each-ref --sort=-committerdate --format='%(refname:short) %(committerdate:short)' refs/remotes/origin` — one command for all branches, not one `git log` per branch.
  - PRs: **one** call, joined locally — `gh pr list --state all --limit 200 --json number,headRefName,state,url`. Do not run `gh pr list --head <branch>` ~30 times.
- Many numbered spec branches (`001-…` through `012-…`) correspond to already-shipped specs in `specs/` and completed plans in `.claude/plans/`. Cross-check whether the work landed on `main` before recommending deletion — "merged into origin/main" is the strong signal; "a plan directory of the same name exists and its status is completed" is a supporting one.

## Requirements (Verification Checklist)
No Pest surface — this is a repo-hygiene report. Mark each `[x]` when done.

- [x] `git fetch --prune origin` run, and the inventory re-derived from remote refs (not from the stale list above).
- [x] Check for open PRs targeting `develop`: `gh pr list --base develop --state open`. Record the result verbatim in the checklist — if any exist, the checklist must say **do not delete `develop` yet** and list them. (Result: `[]` — none open.)
- [x] Write `.claude/plans/trunk-based-cicd/_branch-cleanup-checklist.md` opening with a "Retire `develop`" section containing: the open-PR check result, the exact commands for the user to run (`git push origin --delete develop` and, if a local copy exists, `git branch -d develop`), and the post-deletion verification (`gh api repos/:owner/:repo/branches/develop` should 404). State plainly that this task did not run them.
- [x] The same file then contains a plain markdown table with one row per remote branch: branch name | merged into `origin/main`? (yes/no) | open/closed PR (link or none) | last commit date | recommended action (`merge`, `rebase onto main and open PR`, `abandon/delete — superseded`, or `leave alone — active work`).
- [x] The table includes a row for every branch found by the re-derived inventory, and the commands used to derive it are recorded at the bottom of the file so the report is reproducible.
- [x] The file ends with a note that, after this plan lands, PRs based on stale branches only get CI feedback when retargeted at `main` — anything worth keeping needs a rebase.
- [x] Confirm nothing was deleted: `git branch -r` still lists `origin/develop`, and no `git push` appears in your executed commands.

## Acceptance Criteria
- `.claude/plans/trunk-based-cicd/_branch-cleanup-checklist.md` exists, covers every branch from the freshly-derived remote inventory, and recommends actions without performing any.
- `origin/develop` still exists when this task finishes — its deletion is a manual step for the user.
- No branch was deleted, pushed, merged, or closed by this task.

## Implementation Notes

Read-only task, no Pest surface (report deliverable only). No test to write/run — verified via the acceptance-criteria git/gh commands listed below instead.

- Deliverable written: `.claude/plans/trunk-based-cicd/_branch-cleanup-checklist.md`.
- `git branch -r` (25 real branches once the `origin/HEAD` symref is excluded, including `origin/main` and `origin/develop`) cross-referenced against `git branch -r --merged origin/main` and a single `gh pr list --state all --limit 200 --json number,headRefName,state,url` call. Two rows needed spot-check ancestry beyond the bulk merged-list (`git merge-base --is-ancestor <ref> origin/main`): `origin/011-share-content` (confirmed merged, two merged PRs #7/#8) and `origin/security/dompdf/dompdf-30310950804` (confirmed **not** merged — superseded by `origin/security/dompdf-dompdf-30321561611`, PR #27, which did land).
- `gh pr list --base develop --state open` returned `[]` — no open PRs target `develop`, recorded verbatim in the checklist's "Retire `develop`" section.
- One open PR overall: `security/symfony-yaml-30828850279` (#28) — marked "leave alone — active work" rather than given a deletion recommendation, per the allowed action vocabulary.
- `feature/recipe-import-paste-html` has no PR and the newest commit of any branch (2026-08-17) — marked "leave alone — active work" per the task's explicit instruction, not scored against the merged-branch criteria.
- All numbered spec branches from the plan's original stale inventory (`001-…` through `010-…`, `012-…`) and `feature/grocery-list-archive-remove` no longer exist as remote refs — already deleted upstream before this task ran; noted in the checklist rather than given table rows, since the requirement is to cover the *re-derived* inventory, not the stale one.
- Verified no destructive action occurred: `origin/develop` still present in `git branch -r` after the task, and no `git push`/`git branch -d|-D`/`gh api -X DELETE`/`gh pr merge`/`gh pr close` command was run at any point (only read-only `git`/`gh` queries plus one `Write` for the report file).
