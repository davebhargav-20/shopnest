<?php
require __DIR__ . '/../includes/init.php';
$sid = require_seller();

$products  = (int)db_val("SELECT COUNT(*) FROM tblproduct WHERE SellerID=?", [$sid]);
$outStock  = (int)db_val("SELECT COUNT(*) FROM tblproduct WHERE SellerID=? AND Stock=0", [$sid]);
$pending   = (int)db_val("SELECT COUNT(*) FROM tblorderitems WHERE SellerID=? AND Status='Pending'", [$sid]);
$shipped   = (int)db_val("SELECT COUNT(*) FROM tblorderitems WHERE SellerID=? AND Status IN ('Confirmed','Shipped')", [$sid]);
$delivered = (int)db_val("SELECT COUNT(*) FROM tblorderitems WHERE SellerID=? AND Status='Delivered'", [$sid]);
$revenue   = (float)db_val("SELECT COALESCE(SUM(Price*Quantity),0) FROM tblorderitems WHERE SellerID=? AND Status='Delivered'", [$sid]);

/* last 7 days sales */
$days = [];
for ($i = 6; $i >= 0; $i--) $days[date('Y-m-d', strtotime("-$i day"))] = 0;
$rows = db_all("SELECT DATE(o.OrderDate) d, SUM(i.Price*i.Quantity) amt FROM tblorderitems i JOIN tblorders o ON o.ID=i.OrderID
                WHERE i.SellerID=? AND i.Status<>'Cancelled' AND o.OrderDate >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(o.OrderDate)", [$sid]);
foreach ($rows as $r) $days[$r['d']] = (float)$r['amt'];
$maxDay = max(1, max($days));

$recent = db_all("SELECT i.*, o.OrderNumber, o.OrderDate, o.ShipName FROM tblorderitems i JOIN tblorders o ON o.ID=i.OrderID
                  WHERE i.SellerID=? ORDER BY o.OrderDate DESC LIMIT 6", [$sid]);
$low = db_all("SELECT ID, ProductName, Stock FROM tblproduct WHERE SellerID=? AND Stock<=5 ORDER BY Stock LIMIT 6", [$sid]);

$panelRole = 'seller';
$pageTitle = 'Seller Dashboard';
include __DIR__ . '/../includes/panel-header.php';
?>
<div class="stats">
    <a class="stat" href="manage-products.php"><div class="ic ic-blue"><i class="fa-solid fa-box"></i></div><b><?php echo $products; ?></b><span>Total Products</span></a>
    <a class="stat" href="orders.php?status=Pending"><div class="ic ic-amber"><i class="fa-solid fa-hourglass-half"></i></div><b><?php echo $pending; ?></b><span>New Orders</span></a>
    <a class="stat" href="orders.php?status=Shipped"><div class="ic ic-purple"><i class="fa-solid fa-truck"></i></div><b><?php echo $shipped; ?></b><span>Confirmed / Shipped</span></a>
    <a class="stat" href="orders.php?status=Delivered"><div class="ic ic-green"><i class="fa-solid fa-circle-check"></i></div><b><?php echo $delivered; ?></b><span>Delivered</span></a>
    <a class="stat" href="sales-report.php"><div class="ic ic-cyan"><i class="fa-solid fa-indian-rupee-sign"></i></div><b><?php echo money($revenue); ?></b><span>Revenue (delivered)</span></a>
    <a class="stat" href="manage-products.php?stock=out"><div class="ic ic-red"><i class="fa-solid fa-triangle-exclamation"></i></div><b><?php echo $outStock; ?></b><span>Out of Stock</span></a>
</div>

<div class="grid-2-1">
    <div class="card" style="margin-top:0">
        <div class="card-title">Sales — last 7 days</div>
        <div class="bar-chart">
            <?php foreach ($days as $d => $amt): ?>
                <div class="bar" title="<?php echo money($amt); ?>">
                    <span><?php echo $amt ? '₹' . number_format($amt / 1000, 1) . 'k' : ''; ?></span>
                    <div style="height:<?php echo round($amt / $maxDay * 100); ?>%"></div>
                    <span><?php echo date('D', strtotime($d)); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card" style="margin-top:0">
        <div class="card-title">Low Stock <a href="manage-products.php" class="small">Manage</a></div>
        <?php if (!$low): ?><p class="muted">All products are well stocked.</p><?php endif; ?>
        <?php foreach ($low as $l): ?>
            <div class="summary-row small"><a href="edit-product.php?id=<?php echo (int)$l['ID']; ?>" style="color:var(--text)"><?php echo e($l['ProductName']); ?></a><?php echo $l['Stock'] ? '<span class="badge badge-amber">' . (int)$l['Stock'] . ' left</span>' : '<span class="badge badge-red">Out</span>'; ?></div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="card-title">Recent Orders <a href="orders.php" class="small">View all</a></div>
    <?php if (!$recent): ?><div class="empty"><i class="fa-solid fa-receipt"></i>No orders yet.</div><?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Order #</th><th>Date</th><th>Customer</th><th>Product</th><th>Qty</th><th>Amount</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($recent as $r): ?>
                <tr>
                    <td><b><?php echo e($r['OrderNumber']); ?></b></td>
                    <td><?php echo date('d M Y', strtotime($r['OrderDate'])); ?></td>
                    <td><?php echo e($r['ShipName']); ?></td>
                    <td><?php echo e($r['ProductName']); ?></td>
                    <td><?php echo (int)$r['Quantity']; ?></td>
                    <td><?php echo money($r['Price'] * $r['Quantity']); ?></td>
                    <td><?php echo status_badge($r['Status']); ?></td>
                    <td><a class="btn btn-sm btn-outline" href="order-details.php?id=<?php echo (int)$r['OrderID']; ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
