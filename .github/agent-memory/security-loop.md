# Agent Memory: Security Loop

Standing feedback for future `fix-security-advisory` runs. Loaded into the
actuator on every run, after the controller picks a target. Durable guidance
only — not one-off instructions or single-run logs.

## Guidance

- The lockfile was stale coming into this loop: `composer update {package}
  --with-all-dependencies` on `laravel/framework` cascaded into ~30 unrelated
  packages (Symfony components, Guzzle, Carbon, etc.) moving to their latest
  allowed versions, not just the advisory-affected one. That's expected
  composer resolution behavior on a stale lock, not a bug — don't treat a
  wide `composer.lock` diff alone as a reason to abort. Still verify no
  major-version crossings among the cascaded packages (semver major only;
  pre-1.0 packages like `brick/math` moving 0.x→0.x are a judgment call, not
  an automatic stop) and that the full suite passes.

- Composer resolves to the highest version the dependents' constraints allow,
  which routinely overshoots the advisory's minimum fixed version — e.g. the
  `symfony/yaml` advisories said `<8.0.12`, but `composer update` landed
  `v8.1.2` because `laravel/roster` and `laravel/sail` both allow `^8.0`.
  Apply step 5's gate to the version actually installed, not to the advisory's
  fixed range: a minor overshoot inside the same major is fine and needs no
  wrapper bump, but the overshoot is what could cross a major, so read the
  update output rather than assuming you got the minimum patch.

- The CI runner starts with no `.env` and no built frontend assets, so
  `vendor/bin/pest` fails wholesale before it ever exercises the upgraded
  package. Two distinct waves, both environmental, neither a regression:
  ~509 failures from `MissingAppKeyException` (no `.env` at all), then ~86
  from `Vite manifest not found` (no `node_modules`, no `public/build`).
  Bootstrap before trusting a red suite: `cp .env.example .env`,
  `php artisan key:generate`, `npm ci`, `npm run build`. All four targets are
  gitignored, so this never pollutes the commit. Only call step 6 a failure
  once the suite runs against a bootstrapped environment — a red suite on a
  bare checkout says nothing about the upgrade.
