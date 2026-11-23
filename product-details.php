<?php
require __DIR__ . '/includes/init.php';

$id = (int)get('id');
$p = db_one(PRODUCT_SELECT . " AND p.ID = ? GROUP BY p.ID", [$id]);
if (!$p) {
    http_response_code(404);
    $pageTitle = 'Product not found';
    include __DIR__ . '/includes/header.php';
    echo '<div class="container section"><div class="card empty"><i class="fa-solid fa-circle-question"></i>This product is not available. <a href="shop.php">Continue shopping</a></div></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

/* Review submit — only customers who received this product */
$canReview = false;
$myReview = null;
if (is_customer()) {
    $cid = (int)$_SESSION['sncid'];
    $canReview = (bool)db_val("SELECT COUNT(*) FROM tblorderitems i JOIN tblorders o ON o.ID=i.OrderID
                               WHERE o.CustomerID=? AND i.ProductID=? AND i.Status='Delivered'", [$cid, $id]);
    $myReview = db_one("SELECT * FROM tblreview WHERE ProductID=? AND CustomerID=?", [$id, $cid]);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review'])) {
        csrf_check();
        $rating = (int)post('rating');
        $comment = post('comment');
        if (!$canReview) {
            flash('You can review a product only after it has been delivered to you.', 'error');
        } elseif ($rating < 1 || $rating > 5) {
            flash('Please select a star rating.', 'error');
        } else {
            db_query("INSERT INTO tblreview (ProductID, CustomerID, Rating, Comment) VALUES (?,?,?,?)
                      ON DUPLICATE KEY UPDATE Rating=VALUES(Rating), Comment=VALUES(Comment), ReviewDate=NOW()",
                     [$id, $cid, $rating, $comment]);
            flash('Thank you! Your review has been saved.');
        }
        redirect('product-details.php?id=' . $id . '#reviews');
    }
}

$reviews = db_all("SELECT r.*, c.FullName FROM tblreview r JOIN tblcustomer c ON c.ID=r.CustomerID
                   WHERE r.ProductID=? ORDER BY r.ReviewDate DESC", [$id]);
$related = db_all(PRODUCT_SELECT . " AND p.CategoryID=? AND p.ID<>? GROUP BY p.ID ORDER BY RAND() LIMIT 4", [(int)$p['CategoryID'], $id]);
$wishIds = wish_ids();
$off = discount_percent($p['Price'], $p['MRP']);
$inCart = isset($_SESSION['cart'][$id]) ? (int)$_SESSION['cart'][$id] : 0;

$pageTitle = $p['ProductName'];
include __DIR__ . '/includes/header.php';
?>

<div class="container section">
    <div class="breadcrumb">
        <a href="index.php">Home</a> / <a href="shop.php?cat=<?php echo (int)$p['CategoryID']; ?>"><?php echo e($p['CategoryName']); ?></a> / <?php echo e($p['ProductName']); ?>
    </div>

    <div class="pd-grid">
        <div class="pd-image"><img src="<?php echo product_img($p['Image']); ?>" alt="<?php echo e($p['ProductName']); ?>"></div>

        <div class="pd-info">
            <span class="eyebrow"><?php echo e($p['Brand']); ?></span>
            <h1><?php echo e($p['ProductName']); ?></h1>
            <?php if ($p['AvgRating']): ?>
                <div class="mb-2"><?php echo stars($p['AvgRating']); ?> <span class="muted small"><?php echo e($p['AvgRating']); ?> / 5 &middot; <?php echo (int)$p['ReviewCount']; ?> review(s)</span></div>
            <?php endif; ?>

            <div class="price">
                <b><?php echo money($p['Price']); ?></b>
                <?php if ($off > 0): ?><s><?php echo money($p['MRP']); ?></s><span class="off"><?php echo $off; ?>% off</span><?php endif; ?>
            </div>
            <p class="muted small">Inclusive of all taxes. <?php echo $p['Price'] > FREE_SHIPPING_ABOVE ? 'Free delivery.' : 'Free delivery on orders above ' . money(FREE_SHIPPING_ABOVE) . '.'; ?></p>

            <dl class="pd-meta">
                <dt>Availability</dt>
                <dd><?php if ($p['Stock'] > 0): ?><span class="badge badge-green">In stock</span> <?php if ($p['Stock'] <= 5) echo '<span class="muted small">Only ' . (int)$p['Stock'] . ' left!</span>'; ?>
                    <?php else: ?><span class="badge badge-red">Out of stock</span><?php endif; ?></dd>
                <dt>Category</dt><dd><?php echo e($p['CategoryName']); ?></dd>
                <dt>Sold by</dt><dd><i class="fa-solid fa-store muted"></i> <?php echo e($p['ShopName']); ?></dd>
                <dt>Payment</dt><dd>Cash on Delivery / Online</dd>
            </dl>

            <?php if ($p['Stock'] > 0): ?>
                <form method="post" action="cart.php" class="flex">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="pid" value="<?php echo $id; ?>">
                    <input type="number" name="qty" class="qty-input" value="1" min="1" max="<?php echo (int)$p['Stock']; ?>">
                    <button class="btn btn-outline" name="go" value="0"><i class="fa-solid fa-cart-plus"></i> Add to Cart</button>
                    <button class="btn btn-primary" name="go" value="checkout"><i class="fa-solid fa-bolt"></i> Buy Now</button>
                </form>
                <?php if ($inCart): ?><p class="muted small mt-1"><i class="fa-solid fa-circle-check" style="color:var(--green)"></i> <?php echo $inCart; ?> already in your <a href="cart.php">cart</a>.</p><?php endif; ?>
            <?php endif; ?>

            <form method="post" action="wishlist-action.php" class="mt-1">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="pid" value="<?php echo $id; ?>">
                <?php $w = in_array($id, $wishIds, true); ?>
                <input type="hidden" name="action" value="<?php echo $w ? 'remove' : 'add'; ?>">
                <button class="btn btn-sm btn-outline"><i class="fa-<?php echo $w ? 'solid' : 'regular'; ?> fa-heart" style="color:#f43f5e"></i> <?php echo $w ? 'Remove from Wishlist' : 'Add to Wishlist'; ?></button>
            </form>

            <div class="card mt-3">
                <h3 style="font-size:1rem">Product Description</h3>
                <p class="muted mb-0"><?php echo nl2br(e($p['Description'])); ?></p>
            </div>
        </div>
    </div>

    <div class="card mt-3" id="reviews">
        <div class="card-title">Ratings &amp; Reviews <span class="muted small"><?php echo count($reviews); ?> review(s)</span></div>

        <?php if ($canReview): ?>
            <form method="post" class="mb-2">
                <?php echo csrf_field(); ?>
                <label><?php echo $myReview ? 'Update your review' : 'Write a review'; ?></label>
                <div class="star-input">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo ($myReview && (int)$myReview['Rating'] === $i) ? 'checked' : ''; ?>>
                        <label for="star<?php echo $i; ?>"><i class="fa-solid fa-star"></i></label>
                    <?php endfor; ?>
                </div>
                <textarea name="comment" placeholder="Share your experience with this product" class="mt-1"><?php echo $myReview ? e($myReview['Comment']) : ''; ?></textarea>
                <button class="btn btn-primary mt-1" name="review" value="1">Submit Review</button>
            </form>
        <?php elseif (!is_customer()): ?>
            <p class="muted small"><a href="login.php?role=customer&next=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">Login</a> to review products you have purchased.</p>
        <?php else: ?>
            <p class="muted small">You can review this product after it has been delivered to you.</p>
        <?php endif; ?>

        <?php if (!$reviews): ?>
            <p class="muted">No reviews yet.</p>
        <?php endif; ?>
        <?php foreach ($reviews as $r): ?>
            <div class="review">
                <div class="flex between">
                    <b><?php echo e($r['FullName']); ?></b>
                    <span class="muted small"><?php echo date('d M Y', strtotime($r['ReviewDate'])); ?></span>
                </div>
                <?php echo stars($r['Rating']); ?>
                <p class="muted mb-0 mt-1"><?php echo nl2br(e($r['Comment'])); ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($related): ?>
        <div class="section-head mt-3"><h2>Similar Products</h2></div>
        <div class="product-grid">
            <?php foreach ($related as $p) include __DIR__ . '/includes/product-card.php'; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
