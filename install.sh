#!/usr/bin/env bash
set -euo pipefail

seed=false
if [[ $# == 1 && $1 == --seed ]]; then
    seed=true
elif (( $# > 0 )); then
    printf 'Usage: %s [--seed]\n' "${0##*/}" >&2
    exit 2
fi

cd -- "$(dirname -- "${BASH_SOURCE[0]}")"

if [[ ! -e .env ]]; then
    cp .env.example .env
fi

ddev start
ddev exec composer install --no-interaction
ddev exec php artisan config:clear --no-interaction

if grep -qx 'APP_KEY=' .env; then
    ddev exec php artisan key:generate --no-interaction
fi

ddev exec php artisan migrate --no-interaction
ddev exec npm ci
ddev exec npm run build

if [[ "$seed" == true ]]; then
    ddev exec php artisan db:seed --no-interaction
fi

app_url=$(ddev exec printenv DDEV_PRIMARY_URL)
printf '\nYesChef is ready at %s\n' "$app_url"
