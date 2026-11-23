# ShopNest web container: PHP 8.3 + Apache
FROM php:8.3-apache

# PHP extensions + MySQL client (for the first-run schema import) + curl (health checks)
RUN apt-get update \
 && apt-get install -y --no-install-recommends default-mysql-client curl ca-certificates \
 && docker-php-ext-install mysqli \
 && rm -rf /var/lib/apt/lists/*

# Amazon RDS certificate bundle, used when DB_SSL=1 (encrypted DB connection)
RUN curl -fsSL https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem \
      -o /etc/ssl/certs/rds-global-bundle.pem

# Production PHP settings + Apache security headers
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php-shopnest.ini   $PHP_INI_DIR/conf.d/shopnest.ini
COPY docker/apache-security.conf /etc/apache2/conf-enabled/security-shopnest.conf
RUN a2enmod headers rewrite remoteip

# Application code
COPY --chown=www-data:www-data . /var/www/html/
RUN rm -rf /var/www/html/infra /var/www/html/.github /var/www/html/tests /var/www/html/deploy \
           /var/www/html/docker /var/www/html/Dockerfile /var/www/html/docker-compose*.yml \
 && mkdir -p /var/www/html/images/products /var/www/html/images/sellers /var/www/html/images/customers \
 && chown -R www-data:www-data /var/www/html/images \
 && cp -a /var/www/html/images /usr/src/seed-images

COPY docker/entrypoint.sh /usr/local/bin/shopnest-entrypoint
RUN chmod +x /usr/local/bin/shopnest-entrypoint

ARG APP_VERSION=dev
ENV APP_VERSION=${APP_VERSION}

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
  CMD curl -fs http://localhost/health.php || exit 1

ENTRYPOINT ["shopnest-entrypoint"]
CMD ["apache2-foreground"]
