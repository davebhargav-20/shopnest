<?php
/* ============================================================
 * Common helper functions used across the whole project
 * ============================================================ */

/* ---------- Output / URLs ---------- */
function e($str)
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect($path)
{
    header('Location: ' . (preg_match('#^(https?:)?/#', $path) ? $path : url($path)));
    exit;
}

function money($amount)
{
    return '₹' . number_format((float)$amount, 2);
}

function product_img($file)
{
    if ($file && is_file(PRODUCT_IMG_DIR . $file)) {
        return url('images/products/' . $file);
    }
    return url('images/no-image.svg');
}

function discount_percent($price, $mrp)
{
    if ($mrp <= 0 || $price >= $mrp) return 0;
    return (int)round((($mrp - $price) / $mrp) * 100);
}

/* ---------- Database (prepared statements) ----------
 * db_query("SELECT * FROM tblproduct WHERE ID=?", [5]);
 */
function db_query($sql, $params = [])
{
    global $con;
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        error_log('SQL prepare failed: ' . mysqli_error($con) . ' | ' . $sql);
        return false;
    }
    if ($params) {
        $types = '';
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        error_log('SQL execute failed: ' . mysqli_stmt_error($stmt) . ' | ' . $sql);
        return false;
    }
    $res = mysqli_stmt_get_result($stmt);
    return $res === false ? true : $res;
}

function db_all($sql, $params = [])
{
    $res = db_query($sql, $params);
    return ($res instanceof mysqli_result) ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
}

function db_one($sql, $params = [])
{
    $res = db_query($sql, $params);
    return ($res instanceof mysqli_result) ? mysqli_fetch_assoc($res) : null;
}

function db_val($sql, $params = [])
{
    $row = db_one($sql, $params);
    return $row ? reset($row) : null;
}

function db_insert_id()
{
    global $con;
    return mysqli_insert_id($con);
}

function db_affected()
{
    global $con;
    return mysqli_affected_rows($con);
}

/* ---------- Security ---------- */
function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_check()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf']) || !hash_equals(csrf_token(), $_POST['csrf'])) {
            http_response_code(400);
            die('Invalid form submission. Please go back, refresh the page and try again.');
        }
    }
}

/* ---------- Flash messages ---------- */
function flash($msg, $type = 'success')
{
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
}

function show_flash()
{
    if (empty($_SESSION['flash'])) return '';
    $html = '';
    foreach ($_SESSION['flash'] as $f) {
        $icon = $f['type'] === 'error' ? 'fa-circle-exclamation' : ($f['type'] === 'info' ? 'fa-circle-info' : 'fa-circle-check');
        $html .= '<div class="alert alert-' . e($f['type']) . '"><i class="fa-solid ' . $icon . '"></i> ' . e($f['msg']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

/* ---------- Authentication ----------
 * Session keys (like crmsaid / crmscid / crmsuid in CRMS):
 *   snaid = admin id, snsid = seller id, sncid = customer id
 */
function is_customer() { return !empty($_SESSION['sncid']); }
function is_seller()   { return !empty($_SESSION['snsid']); }
function is_admin()    { return !empty($_SESSION['snaid']); }

function require_customer()
{
    if (!is_customer()) {
        flash('Please login as a customer to continue.', 'info');
        redirect('login.php?role=customer&next=' . urlencode($_SERVER['REQUEST_URI']));
    }
    return (int)$_SESSION['sncid'];
}

function require_seller()
{
    if (!is_seller()) redirect('login.php?role=seller');
    $st = db_val("SELECT Status FROM tblseller WHERE ID=?", [(int)$_SESSION['snsid']]);
    if ($st !== 'Active') {
        unset($_SESSION['snsid']);
        flash('Your seller account is not active. Please contact the admin.', 'error');
        redirect('login.php?role=seller');
    }
    return (int)$_SESSION['snsid'];
}

function require_admin()
{
    if (!is_admin()) redirect('login.php?role=admin');
    return (int)$_SESSION['snaid'];
}

function logout_all()
{
    $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
    session_unset();
    session_regenerate_id(true);
    $_SESSION['cart'] = $cart; // keep the guest cart
}

/* ---------- Cart (stored in session: [productId => qty]) ---------- */
function cart_get()
{
    return isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : [];
}

function cart_count()
{
    return array_sum(cart_get());
}

function cart_items()
{
    $cart = cart_get();
    if (!$cart) return [];
    $ids = array_map('intval', array_keys($cart));
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $rows = db_all("SELECT p.*, s.ShopName FROM tblproduct p
                    JOIN tblseller s ON s.ID = p.SellerID
                    WHERE p.ID IN ($in) AND p.Status='Active' AND s.Status='Active'", $ids);
    $items = [];
    foreach ($rows as $r) {
        $qty = min((int)$cart[$r['ID']], (int)$r['Stock']);
        $r['Qty'] = $qty;
        $r['LineTotal'] = $qty * $r['Price'];
        $items[] = $r;
    }
    return $items;
}

function cart_totals($items)
{
    $sub = 0;
    foreach ($items as $i) $sub += $i['LineTotal'];
    $ship = ($sub == 0 || $sub > FREE_SHIPPING_ABOVE) ? 0 : SHIPPING_CHARGE;
    return ['sub' => $sub, 'ship' => $ship, 'total' => $sub + $ship];
}

/* ---------- Orders ---------- */
function order_statuses()
{
    return ['Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled'];
}

function status_badge($status)
{
    $map = ['Pending' => 'amber', 'Confirmed' => 'blue', 'Shipped' => 'purple', 'Delivered' => 'green',
            'Cancelled' => 'red', 'Active' => 'green', 'Inactive' => 'red', 'Blocked' => 'red',
            'Paid' => 'green', 'Refunded' => 'purple', 'Partially Delivered' => 'blue', 'Processing' => 'blue'];
    $c = isset($map[$status]) ? $map[$status] : 'grey';
    return '<span class="badge badge-' . $c . '">' . e($status) . '</span>';
}

/* Overall status of an order, derived from its items */
function order_overall_status($orderId)
{
    $rows = db_all("SELECT Status, COUNT(*) c FROM tblorderitems WHERE OrderID=? GROUP BY Status", [(int)$orderId]);
    $s = [];
    $total = 0;
    foreach ($rows as $r) { $s[$r['Status']] = (int)$r['c']; $total += (int)$r['c']; }
    if (!$total) return 'Pending';
    $cancel = isset($s['Cancelled']) ? $s['Cancelled'] : 0;
    $active = $total - $cancel;
    if ($active === 0) return 'Cancelled';
    foreach (['Pending', 'Confirmed', 'Shipped', 'Delivered'] as $st) {
        if (isset($s[$st]) && $s[$st] === $active) return $st;
    }
    if (!empty($s['Delivered'])) return 'Partially Delivered';
    return 'Processing';
}

function new_order_number()
{
    do {
        $no = 'SN' . date('Ymd') . str_pad((string)random_int(0, 99999), 5, '0', STR_PAD_LEFT);
    } while (db_val("SELECT COUNT(*) FROM tblorders WHERE OrderNumber=?", [$no]));
    return $no;
}

/* ---------- Image upload ----------
 * Returns [filename, null] on success or [null, 'error message']
 */
function upload_image($field, $dir, $required = false)
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, $required ? 'Please choose an image.' : null];
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) return [null, 'Image upload failed (error ' . $f['error'] . ').'];
    if ($f['size'] > 2 * 1024 * 1024) return [null, 'Image must be smaller than 2 MB.'];

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $info = @getimagesize($f['tmp_name']);
    if (!$info || !isset($allowed[$info['mime']])) return [null, 'Only JPG, PNG, GIF or WEBP images are allowed.'];

    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $name = bin2hex(random_bytes(8)) . time() . '.' . $allowed[$info['mime']];
    if (!move_uploaded_file($f['tmp_name'], $dir . $name)) return [null, 'Could not save the uploaded image.'];
    return [$name, null];
}

/* ---------- Misc ---------- */
function get_page($type)
{
    return db_one("SELECT * FROM tblpage WHERE PageType=?", [$type]);
}

function stars($rating)
{
    $html = '<span class="stars">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<i class="fa-' . ($rating >= $i ? 'solid' : ($rating >= $i - 0.5 ? 'solid' : 'regular')) . ' fa-star' . ($rating < $i && $rating >= $i - 0.5 ? '-half-stroke' : '') . '"></i>';
    }
    return $html . '</span>';
}

function post($key, $default = '')
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

function get($key, $default = '')
{
    return isset($_GET[$key]) ? trim((string)$_GET[$key]) : $default;
}

/* Safe internal redirect target (prevents open redirects) */
function safe_next($next, $fallback)
{
    if ($next && $next[0] === '/' && strpos($next, '//') !== 0) return $next;
    return url($fallback);
}

/* ---------- Storefront product query ----------
 * Base SELECT for visible products (active product + active seller) with rating info.
 * Append extra "AND ..." conditions, then " GROUP BY p.ID ..." yourself.
 */
define('PRODUCT_SELECT', "SELECT p.*, c.CategoryName, s.ShopName,
        ROUND(AVG(r.Rating),1) AS AvgRating, COUNT(r.ID) AS ReviewCount
    FROM tblproduct p
    JOIN tblcategory c ON c.ID = p.CategoryID
    JOIN tblseller s   ON s.ID = p.SellerID
    LEFT JOIN tblreview r ON r.ProductID = p.ID
    WHERE p.Status = 'Active' AND s.Status = 'Active'");

function wish_ids()
{
    if (!is_customer()) return [];
    $rows = db_all("SELECT ProductID FROM tblwishlist WHERE CustomerID=?", [(int)$_SESSION['sncid']]);
    return array_map('intval', array_column($rows, 'ProductID'));
}

/* Go back to the previous page on this site, or to $fallback */
function back_or($fallback)
{
    $back = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    if ($back) {
        $u = parse_url($back);
        $bh = (isset($u['host']) ? $u['host'] : '') . (isset($u['port']) ? ':' . $u['port'] : '');
        if ($bh === $host) {
            header('Location: ' . $back);
            exit;
        }
    }
    redirect($fallback);
}

/* Change the status of one order item. Restores stock when an item is cancelled.
 * Returns true if updated. */
function set_item_status($itemId, $status, $remark = null)
{
    if (!in_array($status, order_statuses(), true)) return false;
    $item = db_one("SELECT * FROM tblorderitems WHERE ID=?", [(int)$itemId]);
    if (!$item || $item['Status'] === 'Cancelled' || $item['Status'] === 'Delivered') return false;

    db_query("UPDATE tblorderitems SET Status=?, Remark=COALESCE(?, Remark) WHERE ID=?",
        [$status, ($remark === '' ? null : $remark), (int)$itemId]);

    if ($status === 'Cancelled' && $item['ProductID']) {
        db_query("UPDATE tblproduct SET Stock = Stock + ? WHERE ID=?", [(int)$item['Quantity'], (int)$item['ProductID']]);
    }
    $order = db_one("SELECT * FROM tblorders WHERE ID=?", [(int)$item['OrderID']]);
    if ($status === 'Delivered' && $order['PaymentMethod'] === 'COD' && order_overall_status($order['ID']) === 'Delivered') {
        db_query("UPDATE tblorders SET PaymentStatus='Paid' WHERE ID=?", [(int)$order['ID']]);
    }
    if ($status === 'Cancelled' && $order['PaymentStatus'] === 'Paid' && order_overall_status($order['ID']) === 'Cancelled') {
        db_query("UPDATE tblorders SET PaymentStatus='Refunded' WHERE ID=?", [(int)$order['ID']]);
    }
    return true;
}

/* Read + validate the seller product form. Returns [$data, $errors]. */
function product_from_post()
{
    $d = [
        'ProductName' => post('name'),
        'Brand'       => post('brand'),
        'CategoryID'  => (int)post('category'),
        'Status'      => post('status') === 'Inactive' ? 'Inactive' : 'Active',
        'Price'       => post('price'),
        'MRP'         => post('mrp'),
        'Stock'       => post('stock'),
        'Description' => post('description'),
    ];
    $err = [];
    if ($d['ProductName'] === '') $err[] = 'Product name is required.';
    if (!db_val("SELECT COUNT(*) FROM tblcategory WHERE ID=?", [$d['CategoryID']])) $err[] = 'Please select a valid category.';
    if (!is_numeric($d['Price']) || $d['Price'] <= 0) $err[] = 'Enter a valid selling price.';
    if (!is_numeric($d['MRP']) || $d['MRP'] <= 0) $err[] = 'Enter a valid MRP.';
    if (is_numeric($d['Price']) && is_numeric($d['MRP']) && (float)$d['Price'] > (float)$d['MRP']) $err[] = 'Selling price cannot be more than MRP.';
    if (!ctype_digit((string)$d['Stock'])) $err[] = 'Stock must be a whole number (0 or more).';
    return [$d, $err];
}
