#!/bin/bash
# Pull-based CD. Run every 2 minutes by systemd (shopnest-deploy.timer) as root.
# 1. latest commit of $REPO@$BRANCH     2. only if the CI run for THAT commit succeeded (fail closed)
# 3. build image from that commit       4. swap containers, health check, automatic rollback
# Freeze deployments:  echo AUTO_DEPLOY=false > /opt/shopnest/auto-deploy.conf
set -uo pipefail
cd /opt/shopnest
set -a; source ./.env; source ./auto-deploy.conf 2>/dev/null; set +a
REPO="${REPO:-davebhargav-20/shopnest}"; BRANCH="${BRANCH:-main}"
LOG=/opt/shopnest/deploy-history.log
say() { echo "$(date -u +%FT%TZ) $*" | tee -a "$LOG"; }
API="https://api.github.com/repos/$REPO"
H=(-H "Accept: application/vnd.github+json" -H "User-Agent: shopnest-pull-deploy")

[ "${AUTO_DEPLOY:-true}" = "false" ] && exit 0                       # frozen (demo / exam)
exec 9>/var/lock/shopnest-deploy.lock; flock -n 9 || exit 0          # one deploy at a time

SHA=$(curl -fsS "${H[@]}" "$API/commits/$BRANCH" | python3 -c 'import sys,json;print(json.load(sys.stdin)["sha"])') || exit 0
CUR=$(cat current.sha 2>/dev/null || true)
[ "$SHA" = "$CUR" ] && exit 0
grep -qx "$SHA" bad.shas 2>/dev/null && exit 0                       # known-bad release, do not retry

# CI gate: a completed, successful workflow run for exactly this commit on this branch
CI=$(curl -fsS "${H[@]}" "$API/actions/runs?head_sha=$SHA&branch=$BRANCH" | python3 -c '
import sys,json
runs=[r for r in json.load(sys.stdin).get("workflow_runs",[]) if r["event"] in ("push","workflow_dispatch")]
print(runs[0]["status"]+":"+str(runs[0]["conclusion"]) if runs else "none")') || exit 0
if [ "$CI" != "completed:success" ]; then
  [ "$(cat last-gate.txt 2>/dev/null)" != "$SHA $CI" ] && say "SKIP ${SHA:0:12}: CI state is '$CI' (not deploying)"
  echo "$SHA $CI" > last-gate.txt; exit 0
fi

TAG=${SHA:0:12}; OLD_TAG=$(grep '^APP_TAG=' .env | cut -d= -f2)
say "BUILD $TAG (CI passed)"
rm -rf "src/$TAG" && mkdir -p "src/$TAG"
curl -fsSL "https://github.com/$REPO/archive/$SHA.tar.gz" | tar xz -C "src/$TAG" --strip-components=1 || { say "FAILED download $TAG"; exit 1; }
if ! docker build -q --build-arg APP_VERSION="$TAG" -t "shopnest:$TAG" "src/$TAG" >/dev/null; then
  say "FAILED build $TAG (kept $OLD_TAG)"; echo "$SHA" >> bad.shas; exit 1
fi
cp "src/$TAG/deploy/pull/docker-compose.prod.yml" docker-compose.prod.yml 2>/dev/null || true
sed -i "s/^APP_TAG=.*/APP_TAG=$TAG/" .env
export $(grep -E '^(AWS_REGION|DB_HOST|DB_USER|DB_PASS|DB_NAME|SITE_ADDRESS|APP_TAG)=' .env | xargs)
docker compose -f docker-compose.prod.yml up -d --remove-orphans app proxy >/dev/null 2>&1
docker compose -f docker-compose.prod.yml restart proxy >/dev/null 2>&1   # Caddy caches upstream addresses

for i in $(seq 1 30); do
  if curl -fs http://localhost/health.php | grep -q "\"version\":\"$TAG\""; then
    echo "$SHA" > current.sha; say "DEPLOYED $TAG (smoke test passed)"
    docker image prune -f --filter "until=168h" >/dev/null 2>&1; rm -rf "src/$TAG"; exit 0
  fi; sleep 4
done
say "UNHEALTHY $TAG -> ROLLING BACK to $OLD_TAG"
echo "$SHA" >> bad.shas
sed -i "s/^APP_TAG=.*/APP_TAG=$OLD_TAG/" .env; export APP_TAG=$OLD_TAG
docker compose -f docker-compose.prod.yml up -d app proxy >/dev/null 2>&1
docker compose -f docker-compose.prod.yml restart proxy >/dev/null 2>&1
for i in $(seq 1 20); do curl -fs http://localhost/health.php >/dev/null && { say "ROLLED BACK to $OLD_TAG (healthy)"; exit 1; }; sleep 3; done
say "ROLLBACK FAILED, manual action needed"; exit 2
