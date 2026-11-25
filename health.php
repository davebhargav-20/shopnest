<?php
/* Health check used by the CI/CD pipeline, the deploy script and monitoring.
 * 200 {"status":"ok"} when PHP and the database answer, 503 otherwise. */
header('Content-Type: application/json');
header('Cache-Control: no-store');
require __DIR__ . '/includes/dbconnection.php';

$db = 'down';
if ($con && @mysqli_query($con, 'SELECT 1')) {
    $db = 'up';
}
$ok = $db === 'up';
if (getenv('DB_SSL') === '1') { $ok = false; }   // TEST (c): passes CI, fails only in production
http_response_code($ok ? 200 : 503);
echo json_encode([
    'status'  => $ok ? 'ok' : 'error',
    'db'      => $db,
    'version' => getenv('APP_VERSION') ?: 'local',
    'time'    => gmdate('c'),
]);
