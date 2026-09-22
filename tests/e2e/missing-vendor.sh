#!/usr/bin/env bash
#
# Without vendor/ the handbook degrades instead of breaking the site: its screens
# answer 200 with the missing-libraries message, the dashboard still loads and
# System.check reports the error. Moves vendor/ aside in the running dev stack
# and restores it on exit.
#
#   .docker/ stack up, then: tests/e2e/missing-vendor.sh
set -uo pipefail
. "$(dirname "$0")/lib.sh"

EXT=/var/www/html/ext/handbook
JAR="$(mktemp)"
MESSAGE='composer install --no-dev'

app() { # app <shell command> — runs as www-data in the app container
  (cd "$COMPOSE_DIR" && docker compose exec -T -u www-data app bash -c "$1" </dev/null)
}

cleanup() {
  app "[ -d $EXT/vendor.e2e-aside ] && mv $EXT/vendor.e2e-aside $EXT/vendor; cd /var/www/html && cv flush" >/dev/null 2>&1
}
trap 'cleanup; rm -f "$JAR"' EXIT

echo "handbook: degraded behaviour without vendor/"

app "mv $EXT/vendor $EXT/vendor.e2e-aside && cd /var/www/html && cv flush" >/dev/null 2>&1
check "vendor/ moved aside" "$(app "[ ! -e $EXT/vendor ]"; echo $?)"

# One-time authx login link; the session cookie then carries the next request.
# Curl runs on the host: the site's base URL is the host-mapped port.
LOGIN=$(app "cd /var/www/html && cv url -L --user=admin civicrm/handbook 2>/dev/null" | tr -d '"')
BASE=$(printf '%s' "$LOGIN" | sed -E 's#^(https?://[^/]*).*#\1#')
BODY=$(curl -sL -c "$JAR" -b "$JAR" -w '\n%{http_code}' "$LOGIN")
INDEX=$(printf '%s' "$BODY" | tail -n1)
check "handbook index answers 200" "$([ "$INDEX" = 200 ]; echo $?)" "got $INDEX"
check "handbook index shows the missing-libraries message" \
  "$(printf '%s' "$BODY" | grep -qF "$MESSAGE"; echo $?)"

DASHBOARD=$(curl -s -o /dev/null -w '%{http_code}' -b "$JAR" "$BASE/civicrm/dashboard")
check "dashboard still answers 200" "$([ "$DASHBOARD" = 200 ]; echo $?)" "got $DASHBOARD"

CHECK=$(app "cd /var/www/html && cv api4 System.check +w name=handbook_dependencies +s name,severity --out=json 2>/dev/null")
check "System.check reports handbook_dependencies as error" \
  "$(printf '%s' "$CHECK" | python3 -c "
import json, sys
rows = json.load(sys.stdin)
sys.exit(0 if any(r['name'] == 'handbook_dependencies' and r['severity'] == 'error' for r in rows) else 1)"; echo $?)" "$CHECK"

finish
