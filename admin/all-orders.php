<?php
require __DIR__ . '/../includes/init.php';
require_admin();

$status = get('status');
$q = get('q');
$where = '1';
$params = [];
if (in_array($status, order_statuses(), true)) {
    $where .= " AND EXISTS (SELECT 1 FROM tblorderitems i WHERE i.OrderID=o.ID AND i.Status=?)";
    $params[] = $status;
}
if ($q !== '') {
    $where .= " AND (o.OrderNumber LIKE ? OR c.FullName LIKE ? OR o.ShipMobile LIKE ?)";
    array_push($params, "%$q%", "%$q%", "%$q%");
}
$orders = db_all("SELECT o.*, c.FullName, (SELECT COUNT(*) FROM tblorderitems i WHERE i.OrderID=o.ID) Items
                  FROM tblorders o JOIN tblcustomer c ON c.ID=o.CustomerID WHERE $where ORDER BY o.OrderDate DESC", $params);

$counts = [];
foreach (order_statuses() as $s) $counts[$s] = (int)db_val("SELECT COUNT(DISTINCT OrderID) FROM tblorderitems WHERE Status=?", [$s]);

$panelRole = 'admin';
$pageTitle = 'All Orders';
include __DIR__ . '/../includes/panel-header.php';
?>
<div class="card">
    <div class="card-title">
        <span><?php echo $status ? 'Orders with ' . e($status) . ' items' : 'All Orders'; ?> <span class="muted small">(<?php echo count($orders); ?>)</span></span>
        <form method="get" class="inline-form">
            <?php if ($status) echo '<input type="hidden" name="status" value="' . e($status) . '">'; ?>
            <input type="search" name="q" value="<?php echo e($q); ?>" placeholder="Order #, customer, mobile">
            <button class="btn btn-sm">Search</button>
        </form>
    </div>
    <div class="filter-bar">
        <a href="all-orders.php" class="<?php echo !$status ? 'active' : ''; ?>">All</a>
        <?php foreach ($counts as $s => $n): ?>
            <a href="?status=<?php echo $s; ?>" class="<?php echo $status === $s ? 'active' : ''; ?>"><?php echo $s; ?> (<?php echo $n; ?>)</a>
        <?php endforeach; ?>
    </div>
    <?php if (!$orders): ?><div class="empty"><i class="fa-solid fa-receipt"></i>No orders found.</div><?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>#</th><th>Order #</th><th>Customer</th><th>Date</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($orders as $n => $o): ?>
                <tr>
                    <td><?php echo $n + 1; ?></td>
                    <td><b><?php echo e($o['OrderNumber']); ?></b></td>
                    <td><?php echo e($o['FullName']); ?><div class="muted small"><?php echo e($o['ShipCity']); ?></div></td>
                    <td><?php echo date('d M Y, h:i A', strtotime($o['OrderDate'])); ?></td>
                    <td><?php echo (int)$o['Items']; ?></td>
                    <td><?php echo money($o['TotalAmount']); ?></td>
                    <td><?php echo e($o['PaymentMethod']); ?> <?php echo status_badge($o['PaymentStatus']); ?></td>
                    <td><?php echo status_badge(order_overall_status($o['ID'])); ?></td>
                    <td><a class="btn btn-sm btn-primary" href="order-details.php?id=<?php echo (int)$o['ID']; ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
