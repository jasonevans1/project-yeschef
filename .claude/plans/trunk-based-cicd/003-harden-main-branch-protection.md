# Task 003: Prepare the `main` Branch Protection Hardening (prepare only — does NOT apply it)

**Status**: completed
**Depends on**: 001
**Retry count**: 0

## Description
Close the actual gap behind "a broken build could deploy to production": `main` currently has no `required_pull_request_reviews` block, so a direct `git push` to `main` bypasses required status checks entirely (checks only gate PR merges in GitHub, never direct pushes) and still triggers `deploy.yml`. Turning on "require a pull request before merging" plus `enforce_admins: true` makes it structurally impossible for unchecked code to reach `main` — no workflow file changes needed.

**This task does not make that change.** It produces the exact, ready-to-run artifacts (the PUT body, the before-state snapshot, and a runbook with apply/verify/rollback commands) so the user can apply it by hand in one command. See "Guardrail" below — this is not optional caution, it is the task's contract.

## Guardrail (read first)
- You are a `tdd-worker` subagent. You have `Bash` but **no way to ask a human anything** — there is no user-interaction tool in your toolset, and `plan-orchestrate` may be running fully unattended (possibly wrapped in a ralph-wiggum loop). So "stop and get confirmation" is not a thing you can do. Do not attempt it, do not simulate it, and above all do not decide to proceed without it.
- **Forbidden in this task, no exceptions:** any `gh api` call with `-X PUT`, `-X POST`, `-X PATCH`, or `-X DELETE`; `gh pr create`; `gh pr merge`; `git push`; `git branch -D`. Read-only `gh api` GETs and local file writes only.
- Do NOT open a scratch PR to "prove the flow works", and never merge anything into `main`. `deploy.yml` triggers on `push: main` and POSTs `secrets.LARAVEL_CLOUD_DEPLOY_HOOK` — merging a throwaway commit deploys it to production.
- The human applies the change afterwards using the runbook you write. That step lives in `_plan.md` under "Manual Steps", outside the TDD worker loop entirely.

## Context
- This is a GitHub repo settings change, not a file in the repo. There is no code diff to review the normal way — the "diff" is the before/after JSON of the protection rule, which is exactly what this task materialises as files.
- Current state as of plan creation (**re-verify with a read-only GET, it may have changed**): `required_status_checks.contexts: ["ci", "playwright"]`, `enforce_admins.enabled: false`, no `required_pull_request_reviews` key, `allow_force_pushes.enabled: false`, `allow_deletions.enabled: false`. `required_status_checks.strict` was never captured — you must read it from the live response.
- **The GET response shape is NOT the PUT request shape.** Do not round-trip the GET body into a PUT; it will 422, or silently strip protections that were only expressed as objects. Concretely:

  | Field | GET returns | PUT expects |
  |---|---|---|
  | `enforce_admins` | `{"url": ..., "enabled": false}` | `true` / `false` |
  | `allow_force_pushes` | `{"enabled": false}` | `false` |
  | `allow_deletions` | `{"enabled": false}` | `false` |
  | `required_status_checks` | includes `url`, `contexts_url`, `checks[]` | only `strict` + `contexts` |
  | `restrictions` | absent when unset | must be present and explicitly `null` |
  | `required_pull_request_reviews` | absent when unset | must be present, object or `null` |

  Any nullable key omitted from the PUT is treated by GitHub as "disable this protection" — omitting `required_status_checks` would delete the very thing this plan is protecting.
- Target PUT body (hand-built in PUT shape, `strict` copied verbatim from the live GET):
  ```json
  {
    "required_status_checks": { "strict": <copy from live GET>, "contexts": ["ci", "playwright"] },
    "enforce_admins": true,
    "required_pull_request_reviews": {
      "dismiss_stale_reviews": false,
      "require_code_owner_reviews": false,
      "required_approving_review_count": 0,
      "require_last_push_approval": false
    },
    "restrictions": null,
    "allow_force_pushes": false,
    "allow_deletions": false
  }
  ```
  `required_approving_review_count: 0` is deliberate — this is solo/agent-driven development, and the point is to require a *PR*, not a reviewer. Do not invent a review-count requirement nobody asked for. `restrictions` must be `null` on a user-owned (non-org) repo.
- **Known consequence to document, not to fix here:** `.github/workflows/security-loop.yml` opens its weekly PR with `GH_TOKEN: ${{ secrets.GITHUB_TOKEN }}`. GitHub does not fire `pull_request` workflow runs for PRs created by `GITHUB_TOKEN`, so those PRs never get a `ci` or `playwright` run — the required contexts stay "Expected" forever and, with `enforce_admins: true`, nobody (including the repo owner) can merge them. The runbook must call this out with the workaround.

## Requirements (Verification Checklist)
No Pest surface — the deliverable is three files plus read-only API reads. Mark each `[x]` when the file exists with the described content.

- [x] Capture the live before-state read-only: `gh api repos/:owner/:repo/branches/main/protection > .claude/plans/trunk-based-cicd/branch-protection.before.json`. If the endpoint 404s (protection removed since planning), record that in Implementation Notes and still produce the PUT body — it is valid either way.
- [x] Write `.claude/plans/trunk-based-cicd/branch-protection.json` containing exactly the PUT body above, with `strict` filled in from the before-state (default to `false` if protection was absent). It must be valid JSON — verify with `jq . <file>`.
- [x] Assert the before-state still lists `["ci", "playwright"]` as `required_status_checks.contexts`. If it does not, do NOT invent new contexts — record the discrepancy in Implementation Notes and flag it in the runbook as a blocker for the manual step.
- [x] Write `.claude/plans/trunk-based-cicd/_manual-steps.md` (leading underscore matters — `plan-orchestrate` parses every non-`_plan.md` `*.md` in the plan directory as a task file, so plan-dir artifacts follow the `_`-prefix convention already used by `_plan.md` and `_devils_advocate.md`) containing, in this order:
  1. A one-paragraph statement of what changes and why (direct pushes to `main` become impossible; deploy can then only ever see checked code).
  2. A rendered before/after table of the four fields that change or are re-asserted (`enforce_admins`, `required_pull_request_reviews`, `required_status_checks.contexts`, `required_status_checks.strict`).
  3. The apply command, verbatim and copy-pasteable:
     `gh api -X PUT repos/:owner/:repo/branches/main/protection --input .claude/plans/trunk-based-cicd/branch-protection.json`
  4. The verify commands: re-GET the endpoint and confirm `required_pull_request_reviews` is present, `enforce_admins.enabled` is `true`, and `required_status_checks.contexts` is still exactly `["ci","playwright"]`.
  5. The direct-push rejection check, with the note that a *rejected* push mutates nothing, so this is safe to run:
     `git fetch origin main && git checkout -B protection-check origin/main && git commit --allow-empty -m "protection check" && git push origin HEAD:main`
     — expect `GH006: Protected branch update failed`. Then clean up locally: `git checkout - && git branch -D protection-check`. Note explicitly that a `non-fast-forward` error means the local ref was stale, NOT that protection is working — re-run from a fresh `origin/main`.
  6. The rollback, as a single command: `gh api -X DELETE repos/:owner/:repo/branches/main/protection/enforce_admins` (drops admin enforcement only, leaves everything else intact). Note that `branch-protection.before.json` is the reference for a fuller restore.
  7. The security-loop caveat from Context, with both workarounds: close-and-reopen the bot PR by hand to fire its checks, or create the PR with a PAT secret instead of `GITHUB_TOKEN`.
  8. A note that this runbook should be run **after** the plan's own PR merges, so this plan's merge is not blocked by rules introduced mid-flight.
- [x] Confirm no mutating command was run by this task: `git log origin/main..HEAD --oneline` shows only this plan's commits, and no `gh api -X` (PUT/POST/PATCH/DELETE) appears anywhere in your executed commands.

## Acceptance Criteria
- `branch-protection.json`, `branch-protection.before.json`, and `_manual-steps.md` all exist under `.claude/plans/trunk-based-cicd/`.
- `branch-protection.json` is valid JSON in GitHub's **PUT** shape (not the GET shape) and preserves `["ci","playwright"]` and the live `strict` value.
- Repo settings are unchanged by this task — the live protection rule is byte-identical to `branch-protection.before.json` when the task finishes.
- No branch was pushed, no PR opened, nothing merged to `main`.

## Implementation Notes
- Re-verified live state via read-only GET on `repos/jasonevans1/project-yeschef/branches/main/protection`: `required_status_checks.strict: false`, `contexts: ["ci", "playwright"]`, `enforce_admins.enabled: false`, no `required_pull_request_reviews` key, `allow_force_pushes.enabled: false`, `allow_deletions.enabled: false`. Matches the context captured at plan creation — no discrepancy to flag.
- No 404 case encountered (protection endpoint returned normally); before-state file is the raw GET response.
- `branch-protection.before.json` and `branch-protection.json` both validated with `jq .`.
- After writing all artifacts, re-fetched the live protection endpoint and diffed it byte-for-byte against `branch-protection.before.json` — identical, confirming this task made no mutating changes.
- `git log origin/main..HEAD --oneline` produced no output (no commits made by this task); no `gh api -X PUT/POST/PATCH/DELETE`, `gh pr create/merge`, `git push`, or `git branch -D` was executed at any point.
- Repo is `jasonevans1/project-yeschef` (both `origin` and `github` remotes point to it); used explicit owner/repo (not `:owner/:repo`) in all commands and in `_manual-steps.md`.
