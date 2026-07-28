# Control loop: security

Designed via `design-control-loop` skill, Phase A/B. Phase E (CI) wired in
`.github/workflows/security-loop.yml` after two clean manual runs validated
the actuator's judgment (major-bump gate, dependency-cascade handling).

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
committing.

Interactively, it never pushes or opens a PR without explicit go-ahead. In
CI (`security-loop.yml`), the agent only ever commits locally — pushing and
opening the PR is a separate, deterministic workflow step, not something the
agent does itself. The agent's step gets `ANTHROPIC_API_KEY` only: no
`GH_TOKEN`, and `actions/checkout` runs with `persist-credentials: false`, so
it has no git or GitHub API credential under any permission mode. This is a
deliberate hardening against the risk the `design-control-loop` skill itself
was flagged for (Snyk: Critical) — a privileged CI agent with push access is
a real risk class, not a hypothetical one, so the credential boundary is
enforced structurally, not just by prompt instruction.

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

Bound to one open PR via the `security-loop` label — scheduled runs no-op if
one is already open. Manual `workflow_dispatch` bypasses the check.

## No /iterate

Deliberately not built. `/iterate` feeds PR-comment text to the actuator as
instructions — exactly the pattern behind this skill's Critical risk rating
(anyone who can comment can potentially steer what a privileged CI identity
commits). Revisit only if a concrete need shows up; until then, correcting
the loop means editing the memory file directly.

## Cadence

Weekly (`0 13 * * 1`, Monday 13:00 UTC), plus manual `workflow_dispatch`.
Requires an `ANTHROPIC_API_KEY` repo secret — not yet added as of this
writing.

## Status

31 advisories / 11 packages at first run. After two manual runs (both
merged): 9 advisories / 2 packages remain (`dompdf/dompdf`,
`symfony/yaml`) — the CI schedule's first target.
