#!/bin/bash
# Nightly backup (cron, 02:30 IST): database dump + uploaded images → S3 (encrypted, 30-day lifecycle)
set -euo pipefail
cd /opt/shopnest && source ./.env
STAMP=$(date +%F-%H%M)
docker compose -f docker-compose.prod.yml exec -T -e MYSQL_PWD="$DB_PASS" app \
  mysqldump --ssl --ssl-ca=/etc/ssl/certs/rds-global-bundle.pem -h "$DB_HOST" -u "$DB_USER" \
  --single-transaction --routines "$DB_NAME" | gzip > /tmp/db-$STAMP.sql.gz
tar czf /tmp/uploads-$STAMP.tgz -C /opt/shopnest uploads
aws s3 cp /tmp/db-$STAMP.sql.gz "s3://$BACKUP_BUCKET/db/" --only-show-errors
aws s3 cp /tmp/uploads-$STAMP.tgz "s3://$BACKUP_BUCKET/uploads/" --only-show-errors
rm -f /tmp/db-$STAMP.sql.gz /tmp/uploads-$STAMP.tgz
echo "Backup $STAMP uploaded to s3://$BACKUP_BUCKET"
