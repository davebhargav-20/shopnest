<?php
require __DIR__ . '/../includes/init.php';
$cid = require_customer();

$products = db_all(PRODUCT_SELECT . " AND p.ID IN (SELECT ProductID FROM tblwishlist WHERE CustomerID=?) GROUP BY p.ID ORDER BY p.ProductName", [$cid]);
$wishIds = array_map('intval', array_column($products, 'ID'));

$panelRole = 'customer';
$pageTitle = 'My Wishlist';
include __DIR__ . '/../includes/panel-header.php';
?>
<?php if (!$products): ?>
    <div class="card empty"><i class="fa-regular fa-heart"></i>Your wishlist is empty. Tap the heart on any product to save it here.<br><a href="../shop.php" class="btn btn-primary mt-2">Browse Products</a></div>
<?php else: ?>
    <div class="product-grid">
        <?php foreach ($products as $p) include __DIR__ . '/../includes/product-card.php'; ?>
    </div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
