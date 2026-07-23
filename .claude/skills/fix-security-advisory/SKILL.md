---
name: fix-security-advisory
description: Actuator for the composer-audit security control loop. Fixes the composer package named by scripts/security-loop-next.sh — upgrades it, runs the full suite, and reports pass/fail. Use when asked to run the security loop, fix the next security advisory, or address composer audit findings.
argument-hint: "[package-name]"
allowed-tools: Read, Edit, Bash, Glob, Grep
---

# Fix Security Advisory

Actuator for one iteration of the security control loop. Set point: zero
`composer audit` advisories. This skill fixes exactly one package per run —
that's the loop's increment size, kept small and reviewable.

## Steps

1. If no package name was given, run `scripts/security-loop-next.sh` and use
   its output.
2. Read `.github/agent-memory/security-loop.md`. Apply any standing guidance
   there (accepted-risk exceptions, packages never to touch, past reviewer
   corrections) before acting — if it excludes the selected package, pick the
   next one from the sensor's ranking instead.
3. `composer show {package}` and `composer audit` (full, not `--format=summary`)
   to see the current version, the fixed version range, and every advisory
   for this package.
4. Try `composer update {package} --with-all-dependencies`. If the package is
   a transitive dependency pulled in by a wrapper (e.g. `dompdf/dompdf` via
   `barryvdh/laravel-dompdf`), check whether the wrapper's constraint already
   allows the fixed version. If not, the wrapper needs bumping too — do that
   instead of loosening constraints just to force the transitive version.
5. **Major version bump check.** If the update crosses a major version
   (e.g. `2.x` → `3.x`), stop and report it as needing manual review instead
   of proceeding — a major bump can carry breaking changes that a same-day
   audit fix shouldn't gamble on. Minor/patch bumps that satisfy the advisory
   proceed automatically.
6. Run `vendor/bin/pint --parallel --test` and `vendor/bin/pest`. Both must
   pass. If either fails, do not commit — report the failure instead of
   forcing it through.
7. Re-run `composer audit` and confirm this package's advisories are gone
   (fixing one package can occasionally surface a newly-resolved transitive
   version with its own advisory — note that but don't chase it in this run).
8. Commit on a branch named `security/{package}`, following this repo's PR
   conventions (see `.claude/skills/plan-orchestrate/SKILL.md` for the
   push/PR gate — same rule applies here: never push or open a PR without
   the user's go-ahead).
9. If this run surfaced durable guidance (an exception, a false positive, a
   correction) — not a one-off note — add it to
   `.github/agent-memory/security-loop.md`.

## Response format

End with:

```
## Security fix: {package}

{old version} -> {new version}
Advisories resolved: {n}
Tests: pass|fail
{one line per advisory: CVE/GHSA id + title}

{major-bump-deferred note, if applicable}
```
