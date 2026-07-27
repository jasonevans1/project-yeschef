# Control loop: security

Designed via `design-control-loop` skill, Phase A/B. Manual-run only — no CI
wiring yet (Phase E+ deferred).

## Set point

Zero `composer audit` advisories.

## Sensor

`composer audit --format=json` (or `--locked`, no vendor install needed).
Zero setup — built into Composer.

## Controller

Fused with the sensor: `scripts/security-loop-next.sh` groups advisories by
package, ranks by top severity then advisory count, and prints one package —
the next reviewable increment. Deterministic, not agentic.

## Actuator

Claude Code + `.claude/skills/fix-security-advisory/`. Upgrades the named
package, defers major-version bumps to manual review, requires
`vendor/bin/pint --parallel --test` and `vendor/bin/pest` to pass before
committing. Never pushes or opens a PR without explicit go-ahead.

## Disturbances

New advisories get published against already-pinned versions over time
(the main disturbance — external, not from this codebase). Low risk of
concurrent-edit conflicts (solo-reviewed project).

## Dampener

`.github/workflows/lint.yml` — compares the PR's `composer audit` count
against the base branch's, warns (via `::warning::`, never fails the job) if
the PR would introduce new advisories. Keeps the problem from getting worse
while this loop chips away at the existing 31.

## Memory

`.github/agent-memory/security-loop.md` — standing exceptions (accepted-risk
CVEs with no upgrade path yet, packages to never touch) that should shape
future runs.

## Flow control

Not applicable yet — no scheduling exists (Phase E+). Revisit when this loop
moves to CI: bound to one open PR via a `security-loop` label.

## Status as of first run

31 advisories, 11 packages. First target: `laravel/framework` (3 advisories,
high severity).
