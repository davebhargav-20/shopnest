<?php
/* Storefront header — set $pageTitle before including */
$active = basename($_SERVER['PHP_SELF']);
$pageTitle = isset($pageTitle) ? $pageTitle : 'Online Shopping';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo url('assets/vendor/fonts/fonts.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/vendor/fontawesome/css/all.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="<?php echo url('index.php'); ?>" class="logo"><span>Shop</span>Nest</a>

        <form class="header-search" action="<?php echo url('shop.php'); ?>" method="get">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" name="q" placeholder="Search products, brands..." value="<?php echo e(get('q')); ?>">
        </form>

        <button class="menu-toggle" onclick="document.querySelector('.main-nav').classList.toggle('open')" aria-label="Menu">
            <i class="fa-solid fa-bars"></i>
        </button>

        <nav class="main-nav">
            <a href="<?php echo url('index.php'); ?>" class="<?php echo $active == 'index.php' ? 'active' : ''; ?>">Home</a>
            <a href="<?php echo url('shop.php'); ?>" class="<?php echo in_array($active, ['shop.php', 'product-details.php']) ? 'active' : ''; ?>">Shop</a>
            <a href="<?php echo url('about.php'); ?>" class="<?php echo $active == 'about.php' ? 'active' : ''; ?>">About</a>
            <a href="<?php echo url('contact.php'); ?>" class="<?php echo $active == 'contact.php' ? 'active' : ''; ?>">Contact</a>
            <?php if (is_customer()): ?>
                <a href="<?php echo url('customer/wishlist.php'); ?>" class="nav-icon" title="Wishlist"><i class="fa-regular fa-heart"></i></a>
            <?php endif; ?>
            <a href="<?php echo url('cart.php'); ?>" class="nav-icon <?php echo $active == 'cart.php' ? 'active' : ''; ?>" title="Cart">
                <i class="fa-solid fa-cart-shopping"></i>
                <?php if (cart_count() > 0): ?><span class="cart-count"><?php echo cart_count(); ?></span><?php endif; ?>
            </a>
            <?php if (is_customer()): ?>
                <a href="<?php echo url('customer/dashboard.php'); ?>" title="My Account"><i class="fa-regular fa-user"></i> Account</a>
            <?php elseif (is_seller()): ?>
                <a href="<?php echo url('seller/dashboard.php'); ?>"><i class="fa-solid fa-store"></i> Seller Panel</a>
            <?php elseif (is_admin()): ?>
                <a href="<?php echo url('admin/dashboard.php'); ?>"><i class="fa-solid fa-gauge"></i> Admin</a>
            <?php else: ?>
                <a href="<?php echo url('login.php'); ?>" class="btn btn-primary btn-sm" style="color:#fff">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php $__flash = show_flash(); if ($__flash): ?>
    <div class="container mt-2"><?php echo $__flash; ?></div>
<?php endif; ?>
