<?php
/* ------------------------------------------------------------
 * ShopNest bootstrap — include this at the top of every page.
 *   root pages  : require __DIR__ . '/includes/init.php';
 *   panel pages : require __DIR__ . '/../includes/init.php';
 * ------------------------------------------------------------ */
if (session_status() === PHP_SESSION_NONE) {
    $__https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => $__https]);
    session_start();
}
date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/dbconnection.php';

define('SITE_NAME', 'ShopNest');
define('FREE_SHIPPING_ABOVE', 999);   // orders above this amount ship free
define('SHIPPING_CHARGE', 49);
define('PRODUCT_IMG_DIR', __DIR__ . '/../images/products/');
define('SELLER_IMG_DIR', __DIR__ . '/../images/sellers/');
define('CUSTOMER_IMG_DIR', __DIR__ . '/../images/customers/');

/* Base URL of the project, e.g. "/shopnest" on XAMPP, "" on php -S */
$__root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$__doc  = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
$__base = ($__doc !== '' && strpos($__root, $__doc) === 0) ? substr($__root, strlen($__doc)) : '';
define('BASE_URL', rtrim($__base, '/'));

require_once __DIR__ . '/functions.php';
