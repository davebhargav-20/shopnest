<?php
require __DIR__ . '/../includes/init.php';
$sid = require_seller();

$status = get('status');
$where = "i.SellerID=?";
$params = [$sid];
if (in_array($status, order_statuses(), true)) { $where .= " AND i.Status=?"; $params[] = $status; }
$q = get('q');
if ($q !== '') { $where .= " AND (o.OrderNumber LIKE ? OR o.ShipName LIKE ? OR i.ProductName LIKE ?)"; array_push($params, "%$q%", "%$q%", "%$q%"); }

$rows = db_all("SELECT i.*, o.OrderNumber, o.OrderDate, o.ShipName, o.ShipCity, o.PaymentMethod
                FROM tblorderitems i JOIN tblorders o ON o.ID=i.OrderID WHERE $where ORDER BY o.OrderDate DESC", $params);
$counts = [];
foreach (db_all("SELECT Status, COUNT(*) c FROM tblorderitems WHERE SellerID=? GROUP BY Status", [$sid]) as $c) $counts[$c['Status']] = $c['c'];

$panelRole = 'seller';
$pageTitle = 'Orders';
include __DIR__ . '/../includes/panel-header.php';
?>
<div class="card">
    <div class="card-title">
        <span><?php echo $status ? e($status) . ' Orders' : 'All Orders'; ?> <span class="muted small">(<?php echo count($rows); ?>)</span></span>
        <form method="get" class="inline-form">
            <?php if ($status) echo '<input type="hidden" name="status" value="' . e($status) . '">'; ?>
            <input type="search" name="q" value="<?php echo e($q); ?>" placeholder="Order #, customer, product">
            <button class="btn btn-sm">Search</button>
        </form>
    </div>
    <div class="filter-bar">
        <a href="orders.php" class="<?php echo !$status ? 'active' : ''; ?>">All (<?php echo array_sum($counts); ?>)</a>
        <?php foreach (order_statuses() as $s): ?>
            <a href="?status=<?php echo $s; ?>" class="<?php echo $status === $s ? 'active' : ''; ?>"><?php echo $s; ?> (<?php echo isset($counts[$s]) ? $counts[$s] : 0; ?>)</a>
        <?php endforeach; ?>
    </div>
    <?php if (!$rows): ?><div class="empty"><i class="fa-solid fa-receipt"></i>No orders found.</div><?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Order #</th><th>Date</th><th>Customer</th><th>Product</th><th>Qty</th><th>Amount</th><th>Payment</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><b><?php echo e($r['OrderNumber']); ?></b></td>
                    <td><?php echo date('d M Y', strtotime($r['OrderDate'])); ?></td>
                    <td><?php echo e($r['ShipName']); ?><div class="muted small"><?php echo e($r['ShipCity']); ?></div></td>
                    <td><?php echo e($r['ProductName']); ?></td>
                    <td><?php echo (int)$r['Quantity']; ?></td>
                    <td><?php echo money($r['Price'] * $r['Quantity']); ?></td>
                    <td><?php echo e($r['PaymentMethod']); ?></td>
                    <td><?php echo status_badge($r['Status']); ?></td>
                    <td><a class="btn btn-sm btn-primary" href="order-details.php?id=<?php echo (int)$r['OrderID']; ?>">Manage</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
