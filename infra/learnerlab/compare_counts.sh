#!/bin/bash
cd /opt/shopnest; set -a; . ./.env; set +a
RESTORED="$1"
S='--ssl --ssl-ca=/etc/ssl/certs/rds-global-bundle.pem'
q() { docker exec -e MYSQL_PWD="$DB_PASS" shopnest-app-1 mysql $S -h "$1" -u admin -N -e "$2"; }
for H in "$DB_HOST" "$RESTORED"; do
  echo "== $H"
  for t in $(q "$H" "select table_name from information_schema.tables where table_schema='ecomdb' order by 1"); do
    echo "$t $(q "$H" "select count(*) from ecomdb.$t")"
  done
done
