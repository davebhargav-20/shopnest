#!/bin/bash
# Zero-touch deploy with automatic rollback. Run by the CI/CD pipeline through AWS SSM:
#   /opt/shopnest/deploy.sh <image-tag>
set -euo pipefail
cd /opt/shopnest
NEW_TAG="${1:?usage: deploy.sh <image-tag>}"
OLD_TAG="$(grep '^APP_TAG=' .env | cut -d= -f2 || true)"
source ./.env

echo "Logging in to ECR..."
aws ecr get-login-password --region "$AWS_REGION" \
  | docker login --username AWS --password-stdin "${ECR_IMAGE%%/*}"

set_tag() { sed -i "s/^APP_TAG=.*/APP_TAG=$1/" .env; }

echo "Deploying $NEW_TAG (previous: ${OLD_TAG:-none})"
set_tag "$NEW_TAG"
docker compose -f docker-compose.prod.yml pull app
docker compose -f docker-compose.prod.yml up -d --remove-orphans

for i in $(seq 1 30); do
  if docker compose -f docker-compose.prod.yml exec -T app curl -fs http://localhost/health.php >/dev/null; then
    echo "Health check passed — $NEW_TAG is live."
    echo "$(date -u +%FT%TZ) deployed $NEW_TAG" >> deploy-history.log
    docker image prune -af --filter "until=168h" >/dev/null || true
    exit 0
  fi
  sleep 4
done

echo "Health check FAILED — rolling back to ${OLD_TAG}"
if [ -n "$OLD_TAG" ] && [ "$OLD_TAG" != "$NEW_TAG" ]; then
  set_tag "$OLD_TAG"
  docker compose -f docker-compose.prod.yml up -d
  echo "$(date -u +%FT%TZ) FAILED $NEW_TAG, rolled back to $OLD_TAG" >> deploy-history.log
fi
exit 1
