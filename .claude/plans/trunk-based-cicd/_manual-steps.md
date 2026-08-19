# Manual Steps: Harden `main` Branch Protection

This runbook is not run by `plan-orchestrate`. It is a manual, human-run step — see `_plan.md` "Manual Steps". Run it **after** this plan's own PR has merged, so the plan's merge is not blocked by rules introduced mid-flight.

## What changes and why

Right now `main` requires the `ci` and `playwright` status checks to pass before a *pull request* can merge, but nothing stops a direct `git push origin main` — that push bypasses status checks entirely (GitHub only gates PR merges, never direct pushes) and still triggers `deploy.yml`, which can ship a broken build to production. Enabling "require a pull request before merging" (via a non-null `required_pull_request_reviews` block) plus `enforce_admins: true` closes that gap: every change to `main`, including from the repo owner, must go through a PR and its required checks. No workflow file changes are needed.

## Before / after

| Field | Before | After |
|---|---|---|
| `enforce_admins.enabled` | `false` | `true` |
| `required_pull_request_reviews` | absent (not required) | present: 0 required approvals, no stale-review dismissal, no code-owner requirement, no last-push-approval requirement — requires a PR to exist, not a reviewer to approve it |
| `required_status_checks.contexts` | `["ci", "playwright"]` | `["ci", "playwright"]` (unchanged, re-asserted) |
| `required_status_checks.strict` | `false` | `false` (unchanged, re-asserted) |

## 1. Apply

```bash
gh api -X PUT repos/jasonevans1/project-yeschef/branches/main/protection --input .claude/plans/trunk-based-cicd/branch-protection.json
```

## 2. Verify

```bash
gh api repos/jasonevans1/project-yeschef/branches/main/protection | jq '{
  required_pull_request_reviews,
  enforce_admins: .enforce_admins.enabled,
  contexts: .required_status_checks.contexts
}'
```

Confirm: `required_pull_request_reviews` is present (not `null`), `enforce_admins` is `true`, and `contexts` is exactly `["ci", "playwright"]`.

## 3. Confirm direct pushes are rejected

A *rejected* push mutates nothing on `main`, so this is safe to run:

```bash
git fetch origin main && git checkout -B protection-check origin/main && git commit --allow-empty -m "protection check" && git push origin HEAD:main
```

Expect: `! [remote rejected] ... GH006: Protected branch update failed`.

Then clean up locally:

```bash
git checkout - && git branch -D protection-check
```

Note: if you instead see a `non-fast-forward` / `fetch first` error, that means your local `origin/main` ref was stale — it is **not** evidence that protection is working. Re-run the check from a freshly fetched `origin/main`.

## 4. Rollback (if needed)

Drop admin enforcement only, leaving everything else intact:

```bash
gh api -X DELETE repos/jasonevans1/project-yeschef/branches/main/protection/enforce_admins
```

For a fuller restore to the exact prior state, use `.claude/plans/trunk-based-cicd/branch-protection.before.json` as the reference (note: it is in GET shape, not PUT shape — re-derive a PUT body from it the same way `branch-protection.json` was derived, per the field-shape table in `003-harden-main-branch-protection.md`).

## 5. Known caveat: `security-loop.yml`'s weekly PR

`.github/workflows/security-loop.yml` opens its weekly PR using `GH_TOKEN: ${{ secrets.GITHUB_TOKEN }}`. GitHub does not fire `pull_request`-triggered workflow runs (including `ci` and `playwright`) for PRs opened by the default `GITHUB_TOKEN`. Once `enforce_admins: true` is active, that PR's required contexts will stay "Expected" forever and nobody — including the repo owner — will be able to merge it through normal means.

Two workarounds, pick one:

- **Close and reopen the bot PR by hand.** Reopening a PR does trigger `pull_request` events, so `ci` and `playwright` will run and the PR becomes mergeable normally.
- **Have `security-loop.yml` open its PR with a personal access token (PAT) stored as a secret, instead of `GITHUB_TOKEN`.** PRs opened with a PAT do trigger `pull_request` workflow runs. This requires editing `security-loop.yml` and adding the PAT secret — out of scope for this plan.
