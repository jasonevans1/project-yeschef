#!/usr/bin/env bash
# Control loop sensor+controller (fused — manual-run loop, no CI yet).
# Set point: zero composer audit advisories.
# Sensor: `composer audit`. Controller: highest-severity package first,
# ties broken by advisory count. Prints the next package to fix; the
# actuator is the fix-security-advisory skill, run interactively.
set -euo pipefail
cd "$(dirname "$0")/.."

json=$(composer audit --format=json 2>/dev/null || true)
total=$(echo "$json" | jq '[.advisories[][]] | length')

if [[ "$total" -eq 0 ]]; then
  echo "Set point reached — 0 advisories."
  exit 0
fi

echo "Gap: $total advisories across $(echo "$json" | jq '.advisories | length') packages."
echo

echo "$json" | jq -r '
  def rank: {"critical":0,"high":1,"medium":2,"low":3};
  def rankof: rank[.] // 4;
  .advisories
  | to_entries
  | map({
      package: .key,
      count: (.value | length),
      top_severity: (.value | map(.severity // "unknown") | sort_by(rankof) | .[0])
    })
  | sort_by([(.top_severity | rankof), -.count])
  | .[0]
  | "Next: \(.package)  (\(.count) advisories, top severity: \(.top_severity))"
'

echo
echo "Run the fix-security-advisory skill against that package."
