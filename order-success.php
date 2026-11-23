<?php
require __DIR__ . '/includes/init.php';
$cid = require_customer();
$order = db_one("SELECT * FROM tblorders WHERE OrderNumber=? AND CustomerID=?", [get('no'), $cid]);
if (!$order) redirect('customer/my-orders.php');

$pageTitle = 'Order Placed';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="card auth-card text-center">
        <div style="font-size:3.5rem;color:var(--green)"><i class="fa-solid fa-circle-check"></i></div>
        <h1>Order Placed!</h1>
        <p class="muted">Thank you for shopping with us. Your order number is</p>
        <h2 class="gradient-text"><?php echo e($order['OrderNumber']); ?></h2>
        <p class="muted small">Total <b style="color:#fff"><?php echo money($order['TotalAmount']); ?></b> &middot;
            <?php echo $order['PaymentMethod'] === 'COD' ? 'Pay on delivery' : 'Payment received'; ?></p>
        <div class="flex mt-2" style="justify-content:center">
            <a class="btn btn-primary" href="customer/order-details.php?id=<?php echo (int)$order['ID']; ?>"><i class="fa-solid fa-truck"></i> Track Order</a>
            <a class="btn btn-outline" href="shop.php">Continue Shopping</a>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
