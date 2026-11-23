<?php
$__contact = get_page('contactus');
$__cats = db_all("SELECT ID, CategoryName FROM tblcategory ORDER BY CategoryName LIMIT 6");
?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <a href="<?php echo url('index.php'); ?>" class="logo" style="display:inline-block;padding:0"><span>Shop</span>Nest</a>
                <p class="muted small mt-1">A multi-vendor marketplace built with PHP &amp; MySQL. Trusted sellers, secure checkout and doorstep delivery.</p>
            </div>
            <div>
                <h4>Categories</h4>
                <?php foreach ($__cats as $c): ?>
                    <a href="<?php echo url('shop.php?cat=' . $c['ID']); ?>"><?php echo e($c['CategoryName']); ?></a>
                <?php endforeach; ?>
            </div>
            <div>
                <h4>Quick Links</h4>
                <a href="<?php echo url('shop.php'); ?>">All Products</a>
                <a href="<?php echo url('cart.php'); ?>">My Cart</a>
                <a href="<?php echo url('customer/my-orders.php'); ?>">Track Orders</a>
                <a href="<?php echo url('seller/seller-signup.php'); ?>">Become a Seller</a>
                <a href="<?php echo url('login.php?role=admin'); ?>">Admin Login</a>
            </div>
            <div>
                <h4>Contact</h4>
                <?php if ($__contact): ?>
                    <p class="muted small"><i class="fa-solid fa-location-dot"></i> <?php echo e($__contact['PageDescription']); ?></p>
                    <p class="muted small"><i class="fa-solid fa-envelope"></i> <?php echo e($__contact['Email']); ?></p>
                    <p class="muted small"><i class="fa-solid fa-phone"></i> <?php echo e($__contact['MobileNumber']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="copyright">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</div>
    </div>
</footer>
</body>
</html>
