<?php
/* Product card partial — expects $p (product row). Optional $wishIds (array of product IDs). */
$__off = discount_percent($p['Price'], $p['MRP']);
$__wished = isset($wishIds) && in_array((int)$p['ID'], $wishIds, true);
?>
<div class="product-card">
    <?php if ((int)$p['Stock'] <= 0): ?>
        <span class="tag out">Out of stock</span>
    <?php elseif ($__off >= 5): ?>
        <span class="tag"><?php echo $__off; ?>% OFF</span>
    <?php endif; ?>

    <form method="post" action="<?php echo url('wishlist-action.php'); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="pid" value="<?php echo (int)$p['ID']; ?>">
        <input type="hidden" name="action" value="<?php echo $__wished ? 'remove' : 'add'; ?>">
        <button class="wish-btn <?php echo $__wished ? 'on' : ''; ?>" title="Wishlist">
            <i class="fa-<?php echo $__wished ? 'solid' : 'regular'; ?> fa-heart"></i>
        </button>
    </form>

    <a class="img" href="<?php echo url('product-details.php?id=' . (int)$p['ID']); ?>">
        <img src="<?php echo product_img($p['Image']); ?>" alt="<?php echo e($p['ProductName']); ?>" loading="lazy">
    </a>
    <div class="body">
        <span class="brand"><?php echo e($p['Brand']); ?></span>
        <a class="name" href="<?php echo url('product-details.php?id=' . (int)$p['ID']); ?>"><?php echo e($p['ProductName']); ?></a>
        <?php if (!empty($p['AvgRating'])): ?>
            <div><?php echo stars($p['AvgRating']); ?> <span class="muted small">(<?php echo (int)$p['ReviewCount']; ?>)</span></div>
        <?php endif; ?>
        <div class="price">
            <b><?php echo money($p['Price']); ?></b>
            <?php if ($__off > 0): ?><s><?php echo money($p['MRP']); ?></s><?php endif; ?>
        </div>
        <div class="actions">
            <form method="post" action="<?php echo url('cart.php'); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="pid" value="<?php echo (int)$p['ID']; ?>">
                <button class="btn btn-primary btn-sm btn-block" <?php echo (int)$p['Stock'] <= 0 ? 'disabled' : ''; ?>>
                    <i class="fa-solid fa-cart-plus"></i> Add to Cart
                </button>
            </form>
        </div>
    </div>
</div>
