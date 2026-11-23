<?php
require __DIR__ . '/../includes/init.php';
$cid = require_customer();

$totalOrders = (int)db_val("SELECT COUNT(*) FROM tblorders WHERE CustomerID=?", [$cid]);
$totalSpent  = (float)db_val("SELECT COALESCE(SUM(i.Price*i.Quantity),0) FROM tblorderitems i JOIN tblorders o ON o.ID=i.OrderID
                               WHERE o.CustomerID=? AND i.Status<>'Cancelled'", [$cid]);
$inTransit   = (int)db_val("SELECT COUNT(DISTINCT o.ID) FROM tblorders o JOIN tblorderitems i ON i.OrderID=o.ID
                             WHERE o.CustomerID=? AND i.Status IN ('Pending','Confirmed','Shipped')", [$cid]);
$wishCount   = (int)db_val("SELECT COUNT(*) FROM tblwishlist WHERE CustomerID=?", [$cid]);
$recent = db_all("SELECT * FROM tblorders WHERE CustomerID=? ORDER BY OrderDate DESC LIMIT 5", [$cid]);

$panelRole = 'customer';
$pageTitle = 'My Dashboard';
include __DIR__ . '/../includes/panel-header.php';
?>
<h2 class="mb-2">Hello, <?php echo e($me['Name']); ?> 👋</h2>
<div class="stats">
    <a class="stat" href="my-orders.php"><div class="ic ic-blue"><i class="fa-solid fa-receipt"></i></div><b><?php echo $totalOrders; ?></b><span>Total Orders</span></a>
    <a class="stat" href="my-orders.php"><div class="ic ic-amber"><i class="fa-solid fa-truck-fast"></i></div><b><?php echo $inTransit; ?></b><span>Orders in Progress</span></a>
    <a class="stat" href="wishlist.php"><div class="ic ic-red"><i class="fa-solid fa-heart"></i></div><b><?php echo $wishCount; ?></b><span>Wishlist Items</span></a>
    <div class="stat"><div class="ic ic-green"><i class="fa-solid fa-indian-rupee-sign"></i></div><b><?php echo money($totalSpent); ?></b><span>Total Spent</span></div>
</div>

<div class="card">
    <div class="card-title">Recent Orders <a href="my-orders.php" class="small">View all</a></div>
    <?php if (!$recent): ?>
        <div class="empty"><i class="fa-solid fa-bag-shopping"></i>You haven't placed any orders yet. <a href="../shop.php">Start shopping</a></div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Order #</th><th>Date</th><th>Amount</th><th>Payment</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($recent as $o): ?>
                <tr>
                    <td><b><?php echo e($o['OrderNumber']); ?></b></td>
                    <td><?php echo date('d M Y', strtotime($o['OrderDate'])); ?></td>
                    <td><?php echo money($o['TotalAmount']); ?></td>
                    <td><?php echo e($o['PaymentMethod']); ?></td>
                    <td><?php echo status_badge(order_overall_status($o['ID'])); ?></td>
                    <td><a class="btn btn-sm btn-outline" href="order-details.php?id=<?php echo (int)$o['ID']; ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
