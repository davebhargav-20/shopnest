<?php
require __DIR__ . '/../includes/init.php';
require_admin();

$c = [
    'orders'     => (int)db_val("SELECT COUNT(*) FROM tblorders"),
    'today'      => (int)db_val("SELECT COUNT(*) FROM tblorders WHERE DATE(OrderDate)=CURDATE()"),
    'pending'    => (int)db_val("SELECT COUNT(DISTINCT OrderID) FROM tblorderitems WHERE Status='Pending'"),
    'delivered'  => (int)db_val("SELECT COUNT(*) FROM tblorderitems WHERE Status='Delivered'"),
    'revenue'    => (float)db_val("SELECT COALESCE(SUM(Price*Quantity),0) FROM tblorderitems WHERE Status<>'Cancelled'"),
    'products'   => (int)db_val("SELECT COUNT(*) FROM tblproduct"),
    'categories' => (int)db_val("SELECT COUNT(*) FROM tblcategory"),
    'customers'  => (int)db_val("SELECT COUNT(*) FROM tblcustomer"),
    'sellers'    => (int)db_val("SELECT COUNT(*) FROM tblseller WHERE Status='Active'"),
    'newsellers' => (int)db_val("SELECT COUNT(*) FROM tblseller WHERE Status='Pending'"),
    'enquiries'  => (int)db_val("SELECT COUNT(*) FROM tblenquiry WHERE IsRead=0"),
    'outstock'   => (int)db_val("SELECT COUNT(*) FROM tblproduct WHERE Stock=0"),
];

/* last 6 months revenue */
$months = [];
for ($i = 5; $i >= 0; $i--) $months[date('Y-m', strtotime(date('Y-m-01') . " -$i month"))] = 0;
foreach (db_all("SELECT DATE_FORMAT(o.OrderDate,'%Y-%m') m, SUM(i.Price*i.Quantity) amt FROM tblorderitems i JOIN tblorders o ON o.ID=i.OrderID
                 WHERE i.Status<>'Cancelled' AND o.OrderDate >= DATE_FORMAT(CURDATE() - INTERVAL 5 MONTH, '%Y-%m-01') GROUP BY m") as $r) {
    if (isset($months[$r['m']])) $months[$r['m']] = (float)$r['amt'];
}
$maxM = max(1, max($months));

$recent = db_all("SELECT o.*, c.FullName FROM tblorders o JOIN tblcustomer c ON c.ID=o.CustomerID ORDER BY o.OrderDate DESC LIMIT 6");
$topSellers = db_all("SELECT s.ShopName, SUM(i.Price*i.Quantity) amt, COUNT(*) items FROM tblorderitems i JOIN tblseller s ON s.ID=i.SellerID
                      WHERE i.Status<>'Cancelled' GROUP BY s.ID ORDER BY amt DESC LIMIT 5");

$panelRole = 'admin';
$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/panel-header.php';
?>
<div class="stats">
    <a class="stat" href="all-orders.php"><div class="ic ic-blue"><i class="fa-solid fa-receipt"></i></div><b><?php echo $c['orders']; ?></b><span>Total Orders (<?php echo $c['today']; ?> today)</span></a>
    <a class="stat" href="all-orders.php?status=Pending"><div class="ic ic-amber"><i class="fa-solid fa-hourglass-half"></i></div><b><?php echo $c['pending']; ?></b><span>Orders with Pending Items</span></a>
    <a class="stat" href="between-dates-reports.php"><div class="ic ic-green"><i class="fa-solid fa-indian-rupee-sign"></i></div><b><?php echo money($c['revenue']); ?></b><span>Gross Sales</span></a>
    <a class="stat" href="all-orders.php?status=Delivered"><div class="ic ic-cyan"><i class="fa-solid fa-circle-check"></i></div><b><?php echo $c['delivered']; ?></b><span>Items Delivered</span></a>
    <a class="stat" href="manage-products.php"><div class="ic ic-purple"><i class="fa-solid fa-box"></i></div><b><?php echo $c['products']; ?></b><span>Products (<?php echo $c['outstock']; ?> out of stock)</span></a>
    <a class="stat" href="manage-categories.php"><div class="ic ic-blue"><i class="fa-solid fa-layer-group"></i></div><b><?php echo $c['categories']; ?></b><span>Categories</span></a>
    <a class="stat" href="registered-customers.php"><div class="ic ic-cyan"><i class="fa-solid fa-users"></i></div><b><?php echo $c['customers']; ?></b><span>Registered Customers</span></a>
    <a class="stat" href="manage-sellers.php"><div class="ic ic-green"><i class="fa-solid fa-store"></i></div><b><?php echo $c['sellers']; ?></b><span>Active Sellers</span></a>
    <a class="stat" href="manage-sellers.php?status=Pending"><div class="ic ic-amber"><i class="fa-solid fa-user-clock"></i></div><b><?php echo $c['newsellers']; ?></b><span>Sellers Awaiting Approval</span></a>
    <a class="stat" href="enquiries.php"><div class="ic ic-red"><i class="fa-solid fa-envelope"></i></div><b><?php echo $c['enquiries']; ?></b><span>Unread Enquiries</span></a>
</div>

<div class="grid-2-1">
    <div class="card" style="margin-top:0">
        <div class="card-title">Monthly Sales</div>
        <div class="bar-chart">
            <?php foreach ($months as $m => $amt): ?>
                <div class="bar" title="<?php echo money($amt); ?>">
                    <span><?php echo $amt ? '₹' . number_format($amt / 1000, 1) . 'k' : ''; ?></span>
                    <div style="height:<?php echo round($amt / $maxM * 100); ?>%"></div>
                    <span><?php echo date('M', strtotime($m . '-01')); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card" style="margin-top:0">
        <div class="card-title">Top Sellers</div>
        <?php if (!$topSellers) echo '<p class="muted">No sales yet.</p>'; ?>
        <?php foreach ($topSellers as $t): ?>
            <div class="summary-row small"><span><?php echo e($t['ShopName']); ?> <span class="muted">· <?php echo (int)$t['items']; ?> items</span></span><b><?php echo money($t['amt']); ?></b></div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="card-title">Latest Orders <a href="all-orders.php" class="small">View all</a></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Order #</th><th>Customer</th><th>Date</th><th>Amount</th><th>Payment</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($recent as $o): ?>
                <tr>
                    <td><b><?php echo e($o['OrderNumber']); ?></b></td>
                    <td><?php echo e($o['FullName']); ?></td>
                    <td><?php echo date('d M Y', strtotime($o['OrderDate'])); ?></td>
                    <td><?php echo money($o['TotalAmount']); ?></td>
                    <td><?php echo e($o['PaymentMethod']); ?> <?php echo status_badge($o['PaymentStatus']); ?></td>
                    <td><?php echo status_badge(order_overall_status($o['ID'])); ?></td>
                    <td><a class="btn btn-sm btn-outline" href="order-details.php?id=<?php echo (int)$o['ID']; ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
