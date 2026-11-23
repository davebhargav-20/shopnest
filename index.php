<?php
require __DIR__ . '/includes/init.php';
$pageTitle = 'Online Shopping for Electronics, Fashion & More';

$categories = db_all("SELECT c.*, (SELECT COUNT(*) FROM tblproduct p JOIN tblseller s ON s.ID=p.SellerID
                        WHERE p.CategoryID=c.ID AND p.Status='Active' AND s.Status='Active') AS Total
                      FROM tblcategory c ORDER BY c.CategoryName");
$featured = db_all(PRODUCT_SELECT . " AND p.IsFeatured = 1 GROUP BY p.ID ORDER BY p.CreationDate DESC LIMIT 8");
$latest   = db_all(PRODUCT_SELECT . " GROUP BY p.ID ORDER BY p.ID DESC LIMIT 8");
$deals    = db_all(PRODUCT_SELECT . " AND p.Stock > 0 GROUP BY p.ID ORDER BY (p.MRP - p.Price) / p.MRP DESC LIMIT 4");
$heroImgs = array_slice($featured, 0, 4);

$stats = [
    'products'  => db_val("SELECT COUNT(*) FROM tblproduct WHERE Status='Active'"),
    'sellers'   => db_val("SELECT COUNT(*) FROM tblseller WHERE Status='Active'"),
    'customers' => db_val("SELECT COUNT(*) FROM tblcustomer"),
];
$wishIds = wish_ids();

include __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container">
        <div>
            <span class="eyebrow"><i class="fa-solid fa-bolt"></i> Festive Sale is live</span>
            <h1>Everything you love,<br><span class="gradient-text">delivered to your door.</span></h1>
            <p>Shop mobiles, laptops, fashion, footwear and home essentials from verified sellers. Free shipping on orders above <?php echo money(FREE_SHIPPING_ABOVE); ?>.</p>
            <div class="flex mt-2">
                <a href="shop.php" class="btn btn-primary"><i class="fa-solid fa-bag-shopping"></i> Start Shopping</a>
                <a href="seller/seller-signup.php" class="btn btn-outline"><i class="fa-solid fa-store"></i> Sell on ShopNest</a>
            </div>
            <div class="hero-stats">
                <div><b><?php echo (int)$stats['products']; ?>+</b><span>Products</span></div>
                <div><b><?php echo (int)$stats['sellers']; ?>+</b><span>Verified Sellers</span></div>
                <div><b><?php echo (int)$stats['customers']; ?>+</b><span>Happy Customers</span></div>
            </div>
        </div>
        <div class="hero-art">
            <?php foreach ($heroImgs as $h): ?>
                <a href="product-details.php?id=<?php echo (int)$h['ID']; ?>"><img src="<?php echo product_img($h['Image']); ?>" alt="<?php echo e($h['ProductName']); ?>"></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div><span class="eyebrow">Browse</span><h2>Shop by Category</h2></div>
            <a href="shop.php">View all <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="cat-grid">
            <?php foreach ($categories as $c): ?>
                <a class="cat-card" href="shop.php?cat=<?php echo (int)$c['ID']; ?>">
                    <i class="fa-solid <?php echo e($c['Icon'] ?: 'fa-tag'); ?>"></i>
                    <b><?php echo e($c['CategoryName']); ?></b>
                    <span><?php echo (int)$c['Total']; ?> products</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-head">
            <div><span class="eyebrow">Hand-picked</span><h2>Featured Products</h2></div>
            <a href="shop.php?featured=1">See more <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="product-grid">
            <?php foreach ($featured as $p) include __DIR__ . '/includes/product-card.php'; ?>
        </div>
    </div>
</section>

<?php if ($deals): ?>
<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-head">
            <div><span class="eyebrow">Biggest discounts</span><h2>Deals of the Day</h2></div>
        </div>
        <div class="product-grid">
            <?php foreach ($deals as $p) include __DIR__ . '/includes/product-card.php'; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section" style="padding-top:0">
    <div class="container">
        <div class="section-head">
            <div><span class="eyebrow">Just in</span><h2>New Arrivals</h2></div>
            <a href="shop.php?sort=new">See more <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="product-grid">
            <?php foreach ($latest as $p) include __DIR__ . '/includes/product-card.php'; ?>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0">
    <div class="container">
        <div class="info-grid">
            <div class="card feature"><i class="fa-solid fa-truck-fast"></i><div><b>Free Delivery</b><p class="muted small mb-0">On all orders above <?php echo money(FREE_SHIPPING_ABOVE); ?></p></div></div>
            <div class="card feature" style="margin-top:0"><i class="fa-solid fa-shield-halved"></i><div><b>Secure Payments</b><p class="muted small mb-0">Cash on delivery or online payment</p></div></div>
            <div class="card feature" style="margin-top:0"><i class="fa-solid fa-rotate-left"></i><div><b>Easy Cancellation</b><p class="muted small mb-0">Cancel any time before the seller confirms</p></div></div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
