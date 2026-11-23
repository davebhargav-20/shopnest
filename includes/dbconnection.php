<?php
/* ------------------------------------------------------------
 * Database connection — same style as CRMS (mysqli + $con).
 *
 * Local XAMPP: works as-is (root, no password, database ecomdb).
 * Cloud (AWS): the values come from environment variables that the
 * Docker container receives (see docker-compose.prod.yml):
 *   DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME, DB_SSL=1
 * ------------------------------------------------------------ */
function env_or($key, $default)
{
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
}

$DB_HOST = env_or('DB_HOST', 'localhost');
$DB_PORT = (int)env_or('DB_PORT', '3306');
$DB_USER = env_or('DB_USER', 'root');
$DB_PASS = env_or('DB_PASS', '');
$DB_NAME = env_or('DB_NAME', 'ecomdb');
$DB_SSL  = env_or('DB_SSL', '0') === '1';
$DB_CA   = env_or('DB_SSL_CA', '/etc/ssl/certs/rds-global-bundle.pem');

mysqli_report(MYSQLI_REPORT_OFF);
$con = mysqli_init();
mysqli_options($con, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
$flags = 0;
if ($DB_SSL && is_file($DB_CA)) {
    /* encrypted connection to Amazon RDS (TLS, verified against the RDS CA bundle) */
    mysqli_ssl_set($con, null, null, $DB_CA, null, null);
    $flags = MYSQLI_CLIENT_SSL;
}
if (!@mysqli_real_connect($con, $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT, null, $flags)) {
    if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'health.php') {
        $con = null;          // health.php reports the failure itself
    } else {
        http_response_code(503);
        error_log('DB connection failed: ' . mysqli_connect_error());
        die('Service temporarily unavailable. Please try again in a minute.');
    }
} else {
    mysqli_set_charset($con, 'utf8mb4');
}
