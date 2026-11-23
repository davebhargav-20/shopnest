<?php
require __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');
    $cart = cart_get();

    if ($action === 'add') {
        $pid = (int)post('pid');
        $qty = max(1, (int)post('qty', 1));
        $prod = db_one("SELECT p.ID, p.Stock, p.ProductName FROM tblproduct p JOIN tblseller s ON s.ID=p.SellerID
                        WHERE p.ID=? AND p.Status='Active' AND s.Status='Active'", [$pid]);
        if (!$prod) {
            flash('Product not available.', 'error');
        } elseif ($prod['Stock'] <= 0) {
            flash('Sorry, this product is out of stock.', 'error');
        } else {
            $newQty = (isset($cart[$pid]) ? $cart[$pid] : 0) + $qty;
            if ($newQty > $prod['Stock']) {
                $newQty = (int)$prod['Stock'];
                flash('Only ' . $prod['Stock'] . ' unit(s) available — quantity adjusted.', 'info');
            } else {
                flash('"' . $prod['ProductName'] . '" added to your cart.');
            }
            $cart[$pid] = $newQty;
        }
        $_SESSION['cart'] = $cart;
        if (post('go') === 'checkout') redirect('checkout.php');
        back_or('cart.php');
    }

    if ($action === 'update' && isset($_POST['qty']) && is_array($_POST['qty'])) {
        foreach ($_POST['qty'] as $pid => $qty) {
            $pid = (int)$pid;
            $qty = (int)$qty;
            if ($qty <= 0) { unset($cart[$pid]); continue; }
            $stock = (int)db_val("SELECT Stock FROM tblproduct WHERE ID=?", [$pid]);
            $cart[$pid] = min($qty, $stock);
            if ($cart[$pid] <= 0) unset($cart[$pid]);
        }
        $_SESSION['cart'] = $cart;
        flash('Cart updated.');
    }

    if ($action === 'remove') {
        unset($cart[(int)post('pid')]);
        $_SESSION['cart'] = $cart;
        flash('Item removed from cart.');
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        flash('Your cart is now empty.');
    }
    redirect('cart.php');
}

$items = cart_items();
/* drop items that became unavailable */
$valid = [];
foreach ($items as $i) if ($i['Qty'] > 0) $valid[$i['ID']] = $i['Qty'];
if (count($valid) !== count(cart_get())) $_SESSION['cart'] = $valid;
$items = array_values(array_filter($items, function ($i) { return $i['Qty'] > 0; }));
$t = cart_totals($items);

$pageTitle = 'Shopping Cart';
include __DIR__ . '/includes/header.php';
?>

<div class="container section">
    <h1>Shopping Cart</h1>

    <?php if (!$items): ?>
        <div class="card empty"><i class="fa-solid fa-cart-shopping"></i>Your cart is empty.<br><a href="shop.php" class="btn btn-primary mt-2">Start Shopping</a></div>
    <?php else: ?>
    <div class="cart-layout">
        <div class="card">
            <form method="post">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="update">
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th class="text-right">Total</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($items as $i): ?>
                            <tr>
                                <td>
                                    <div class="flex" style="flex-wrap:nowrap">
                                        <img class="thumb" src="<?php echo product_img($i['Image']); ?>" alt="">
                                        <div>
                                            <a href="product-details.php?id=<?php echo (int)$i['ID']; ?>" style="color:var(--text);font-weight:600"><?php echo e($i['ProductName']); ?></a>
                                            <div class="muted small">Sold by <?php echo e($i['ShopName']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo money($i['Price']); ?></td>
                                <td><input type="number" class="qty-input" name="qty[<?php echo (int)$i['ID']; ?>]" value="<?php echo (int)$i['Qty']; ?>" min="0" max="<?php echo (int)$i['Stock']; ?>"></td>
                                <td class="text-right"><b><?php echo money($i['LineTotal']); ?></b></td>
                                <td>
                                    <button class="btn btn-danger btn-sm" form="rm<?php echo (int)$i['ID']; ?>" title="Remove"><i class="fa-solid fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="flex between mt-2">
                    <a href="shop.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Continue Shopping</a>
                    <div class="flex">
                        <button class="btn btn-outline" form="clearcart"><i class="fa-solid fa-xmark"></i> Clear Cart</button>
                        <button class="btn"><i class="fa-solid fa-rotate"></i> Update Cart</button>
                    </div>
                </div>
            </form>
            <?php foreach ($items as $i): ?>
                <form method="post" id="rm<?php echo (int)$i['ID']; ?>"><?php echo csrf_field(); ?><input type="hidden" name="action" value="remove"><input type="hidden" name="pid" value="<?php echo (int)$i['ID']; ?>"></form>
            <?php endforeach; ?>
            <form method="post" id="clearcart" onsubmit="return confirm('Remove all items from cart?')"><?php echo csrf_field(); ?><input type="hidden" name="action" value="clear"></form>
        </div>

        <div class="card" style="margin-top:0">
            <h3>Order Summary</h3>
            <div class="summary-row"><span class="muted">Items (<?php echo array_sum(array_column($items, 'Qty')); ?>)</span><span><?php echo money($t['sub']); ?></span></div>
            <div class="summary-row"><span class="muted">Shipping</span><span><?php echo $t['ship'] ? money($t['ship']) : '<span style="color:var(--green)">FREE</span>'; ?></span></div>
            <?php if ($t['ship']): ?><p class="muted small">Add <?php echo money(FREE_SHIPPING_ABOVE - $t['sub'] + 1); ?> more for free delivery.</p><?php endif; ?>
            <div class="summary-row total"><span>Total</span><span><?php echo money($t['total']); ?></span></div>
            <a href="checkout.php" class="btn btn-primary btn-block mt-2"><i class="fa-solid fa-lock"></i> Proceed to Checkout</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
