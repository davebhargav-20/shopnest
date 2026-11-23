<?php
require __DIR__ . '/../includes/init.php';
$cid = require_customer();

$orders = db_all("SELECT o.*, (SELECT COUNT(*) FROM tblorderitems i WHERE i.OrderID=o.ID) AS Items
                  FROM tblorders o WHERE o.CustomerID=? ORDER BY o.OrderDate DESC", [$cid]);

$panelRole = 'customer';
$pageTitle = 'My Orders';
include __DIR__ . '/../includes/panel-header.php';
?>
<div class="card">
    <div class="card-title">Order History <span class="muted small"><?php echo count($orders); ?> order(s)</span></div>
    <?php if (!$orders): ?>
        <div class="empty"><i class="fa-solid fa-receipt"></i>No orders yet. <a href="../shop.php">Start shopping</a></div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>#</th><th>Order Number</th><th>Order Date</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $n => $o): ?>
                <tr>
                    <td><?php echo $n + 1; ?></td>
                    <td><b><?php echo e($o['OrderNumber']); ?></b></td>
                    <td><?php echo date('d M Y, h:i A', strtotime($o['OrderDate'])); ?></td>
                    <td><?php echo (int)$o['Items']; ?></td>
                    <td><?php echo money($o['TotalAmount']); ?></td>
                    <td><?php echo e($o['PaymentMethod']); ?> <?php echo status_badge($o['PaymentStatus']); ?></td>
                    <td><?php echo status_badge(order_overall_status($o['ID'])); ?></td>
                    <td><a class="btn btn-sm btn-primary" href="order-details.php?id=<?php echo (int)$o['ID']; ?>"><i class="fa-solid fa-eye"></i> Details</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
