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

- The CI runner starts with no `.env` and no built frontend assets, so
  `vendor/bin/pest` fails en masse for reasons unrelated to the package under
  test — first "No application encryption key has been specified" (~509
  failures), then "Vite manifest not found" (~86 failures). Before treating a
  suite failure as caused by the upgrade, make sure the environment is
  prepared: `cp .env.example .env && php artisan key:generate` and
  `npm ci && npm run build`. A green run on this repo is ~1005 passed with 19
  skipped (browser-test placeholders); the skips are expected, not a
  regression.
