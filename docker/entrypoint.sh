#!/bin/sh
# First-run database setup: if the ShopNest tables do not exist yet,
# import ecomdb.sql into the database given by DB_HOST / DB_NAME.
set -e
# Uploaded images live on a host folder/volume. Copy the sample images in
# without overwriting anything that is already there.
cp -an /usr/src/seed-images/. /var/www/html/images/ 2>/dev/null || true
chown -R www-data:www-data /var/www/html/images 2>/dev/null || true
if [ -n "$DB_HOST" ] && [ "${DB_AUTO_IMPORT:-1}" = "1" ]; then
  # Debian's client is the MariaDB client: --ssl-verify-server-cert checks the RDS certificate
  if [ "${DB_SSL:-0}" = "1" ]; then
    SSL_OPT="--ssl --ssl-ca=/etc/ssl/certs/rds-global-bundle.pem --ssl-verify-server-cert"
  else
    SSL_OPT="--skip-ssl"
  fi
  export MYSQL_PWD="$DB_PASS"
  i=0
  until mysql $SSL_OPT -h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USER" -e "SELECT 1" >/dev/null 2>&1; do
    i=$((i+1)); [ $i -ge 30 ] && echo "Database not reachable, starting anyway" && break
    echo "Waiting for database ($i)..."; sleep 2
  done
  if mysql $SSL_OPT -h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USER" -N -e \
       "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME:-ecomdb}' AND table_name='tbladmin'" 2>/dev/null | grep -q '^0$'; then
    echo "Importing ecomdb.sql (first run)..."
    sed "s/\`ecomdb\`/\`${DB_NAME:-ecomdb}\`/g" /var/www/html/ecomdb.sql \
      | mysql $SSL_OPT -h "$DB_HOST" -P "${DB_PORT:-3306}" -u "$DB_USER"
    echo "Database ready."
  fi
  unset MYSQL_PWD
fi
exec docker-php-entrypoint "$@"
