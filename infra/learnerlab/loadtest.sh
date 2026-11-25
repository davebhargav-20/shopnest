#!/bin/bash
# Load test on a THROW-AWAY copy: scratch MariaDB loaded from the backup dump + a second app container on :8081.
# Production DB/app are not used. Load generator = ApacheBench on the same host (limitation: no network latency).
set -uo pipefail
cd /opt/shopnest; set -a; . ./.env; set +a
dnf install -y httpd-tools >/dev/null 2>&1
TAG=$(grep '^APP_TAG=' .env | cut -d= -f2)
docker network create lt >/dev/null 2>&1
docker run -d --name lt-db --network lt -e MARIADB_ROOT_PASSWORD=test -e MARIADB_DATABASE=ecomdb mariadb:11 >/dev/null
for i in $(seq 1 40); do docker exec lt-db mariadb -uroot -ptest -e 'select 1' >/dev/null 2>&1 && break; sleep 2; done
KEY=$(aws s3 ls s3://$BACKUP_BUCKET/db/ | sort | tail -1 | awk '{print $4}')
aws s3 cp s3://$BACKUP_BUCKET/db/$KEY /tmp/lt.sql.gz --only-show-errors
zcat /tmp/lt.sql.gz | docker exec -i lt-db mariadb -uroot -ptest ecomdb
docker run -d --name lt-app --network lt -p 8081:80 --memory 512m -e DB_HOST=lt-db -e DB_USER=root -e DB_PASS=test -e DB_NAME=ecomdb -e DB_AUTO_IMPORT=0 shopnest:$TAG >/dev/null
for i in $(seq 1 30); do curl -fs localhost:8081/health.php >/dev/null && break; sleep 2; done
echo "test stack ready; tables: $(docker exec lt-db mariadb -uroot -ptest -N -e 'select count(*) from information_schema.tables where table_schema="ecomdb"')"
for C in 50 100; do
  for U in /index.php /shop.php "/product-details.php?id=1"; do
    vmstat 1 15 > /tmp/vm.txt & 
    ab -q -n 1500 -c $C "http://localhost:8081$U" > /tmp/ab.txt 2>&1
    wait
    CPU=$(awk 'NR>2{u+=$13+$14; n++} END{printf "%.0f", u/n}' /tmp/vm.txt)
    echo "c=$C url=$U | $(grep 'Requests per second' /tmp/ab.txt | awk '{print $4}') req/s | failed=$(grep 'Failed requests' /tmp/ab.txt | awk '{print $3}') non2xx=$(grep 'Non-2xx' /tmp/ab.txt | awk '{print $3}') | p50=$(grep ' 50%' /tmp/ab.txt | awk '{print $2}')ms p95=$(grep ' 95%' /tmp/ab.txt | awk '{print $2}')ms p99=$(grep ' 99%' /tmp/ab.txt | awk '{print $2}')ms | avg CPU busy ${CPU}%"
  done
done
docker rm -f lt-app lt-db >/dev/null 2>&1; docker network rm lt >/dev/null 2>&1; rm -f /tmp/lt.sql.gz
echo "test stack removed; production health: $(curl -s localhost/health.php)"
