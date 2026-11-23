<?php
/* ------------------------------------------------------------
 * Shared dashboard layout for admin / seller / customer panels.
 * Before including set:  $panelRole ('admin'|'seller'|'customer'), $pageTitle
 * ------------------------------------------------------------ */
$active = basename($_SERVER['PHP_SELF']);
$pageTitle = isset($pageTitle) ? $pageTitle : 'Dashboard';

if ($panelRole === 'admin') {
    $me = db_one("SELECT AdminName AS Name, Email FROM tbladmin WHERE ID=?", [(int)$_SESSION['snaid']]);
    $avatar = null;
    $newOrders = (int)db_val("SELECT COUNT(DISTINCT OrderID) FROM tblorderitems WHERE Status='Pending'");
    $pendingSellers = (int)db_val("SELECT COUNT(*) FROM tblseller WHERE Status='Pending'");
    $unread = (int)db_val("SELECT COUNT(*) FROM tblenquiry WHERE IsRead=0");
    $menu = [
        'Main' => [
            ['dashboard.php', 'fa-gauge', 'Dashboard'],
        ],
        'Catalog' => [
            ['manage-categories.php', 'fa-layer-group', 'Categories'],
            ['manage-products.php', 'fa-box', 'All Products'],
        ],
        'Orders' => [
            ['all-orders.php', 'fa-receipt', 'All Orders', ['order-details.php'], $newOrders],
            ['between-dates-reports.php', 'fa-chart-line', 'Sales Report'],
        ],
        'People' => [
            ['manage-sellers.php', 'fa-store', 'Sellers', ['view-seller.php'], $pendingSellers],
            ['registered-customers.php', 'fa-users', 'Customers', ['view-customer.php']],
        ],
        'Website' => [
            ['enquiries.php', 'fa-envelope', 'Enquiries', [], $unread],
            ['about-us.php', 'fa-circle-info', 'About Us Page'],
            ['contact-us.php', 'fa-address-book', 'Contact Us Page'],
        ],
        'Account' => [
            ['admin-profile.php', 'fa-user-gear', 'Profile'],
            ['change-password.php', 'fa-key', 'Change Password'],
            ['logout.php', 'fa-power-off', 'Logout'],
        ],
    ];
    $roleLabel = 'Administrator';
} elseif ($panelRole === 'seller') {
    $me = db_one("SELECT SellerName AS Name, ShopName, Email, Logo FROM tblseller WHERE ID=?", [(int)$_SESSION['snsid']]);
    $avatar = $me['Logo'] ? url('images/sellers/' . $me['Logo']) : null;
    $newOrders = (int)db_val("SELECT COUNT(*) FROM tblorderitems WHERE SellerID=? AND Status='Pending'", [(int)$_SESSION['snsid']]);
    $lowStock = (int)db_val("SELECT COUNT(*) FROM tblproduct WHERE SellerID=? AND Stock<=5", [(int)$_SESSION['snsid']]);
    $menu = [
        'Main' => [
            ['dashboard.php', 'fa-gauge', 'Dashboard'],
        ],
        'Products' => [
            ['add-product.php', 'fa-plus', 'Add Product'],
            ['manage-products.php', 'fa-box', 'Manage Products', ['edit-product.php'], $lowStock],
        ],
        'Orders' => [
            ['orders.php', 'fa-receipt', 'Orders', ['order-details.php'], $newOrders],
            ['sales-report.php', 'fa-chart-line', 'Sales Report'],
        ],
        'Account' => [
            ['seller-profile.php', 'fa-store', 'Shop Profile'],
            ['change-password.php', 'fa-key', 'Change Password'],
            ['logout.php', 'fa-power-off', 'Logout'],
        ],
    ];
    $roleLabel = $me['ShopName'];
} else {
    $me = db_one("SELECT FullName AS Name, Email, Image FROM tblcustomer WHERE ID=?", [(int)$_SESSION['sncid']]);
    $avatar = $me['Image'] ? url('images/customers/' . $me['Image']) : null;
    $menu = [
        'My Account' => [
            ['dashboard.php', 'fa-gauge', 'Dashboard'],
            ['my-orders.php', 'fa-receipt', 'My Orders', ['order-details.php']],
            ['wishlist.php', 'fa-heart', 'Wishlist'],
            ['../cart.php', 'fa-cart-shopping', 'Cart', [], cart_count()],
        ],
        'Settings' => [
            ['profile.php', 'fa-user-pen', 'Profile'],
            ['change-password.php', 'fa-key', 'Change Password'],
            ['logout.php', 'fa-power-off', 'Logout'],
        ],
    ];
    $roleLabel = 'Customer';
}
$initial = strtoupper(substr($me['Name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> | <?php echo SITE_NAME; ?> <?php echo ucfirst($panelRole); ?></title>
    <link rel="stylesheet" href="<?php echo url('assets/vendor/fonts/fonts.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/vendor/fontawesome/css/all.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>
<div class="panel">
    <aside class="sidebar" id="sidebar">
        <div class="logo-box">
            <a href="<?php echo url('index.php'); ?>" class="logo"><span>Shop</span>Nest</a>
            <div class="role-pill"><?php echo ucfirst($panelRole); ?> Panel</div>
        </div>
        <div class="user-box">
            <div class="avatar"><?php echo $avatar ? '<img src="' . e($avatar) . '" alt="">' : e($initial); ?></div>
            <div style="min-width:0"><b><?php echo e($me['Name']); ?></b><span><?php echo e($roleLabel); ?></span></div>
        </div>
        <nav class="side-nav">
            <?php foreach ($menu as $heading => $links): ?>
                <div class="heading"><?php echo e($heading); ?></div>
                <?php foreach ($links as $l):
                    $isActive = $active === basename($l[0]) || (isset($l[3]) && in_array($active, $l[3], true)); ?>
                    <a href="<?php echo e($l[0]); ?>" class="<?php echo $isActive ? 'active' : ''; ?>">
                        <i class="fa-solid <?php echo $l[1]; ?>"></i> <?php echo e($l[2]); ?>
                        <?php if (!empty($l[4])): ?><span class="count"><?php echo (int)$l[4]; ?></span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <main class="panel-main">
        <div class="topbar">
            <div class="flex" style="flex-wrap:nowrap;gap:0">
                <button class="side-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fa-solid fa-bars"></i></button>
                <h1><?php echo e($pageTitle); ?></h1>
            </div>
            <div class="actions">
                <a href="<?php echo url('index.php'); ?>" class="btn btn-outline btn-sm" target="<?php echo $panelRole === 'customer' ? '_self' : '_blank'; ?>"><i class="fa-solid fa-globe"></i> <span>Visit Store</span></a>
                <a href="logout.php" class="btn btn-sm btn-danger" title="Logout"><i class="fa-solid fa-power-off"></i></a>
            </div>
        </div>
        <div class="panel-content">
            <?php echo show_flash(); ?>
