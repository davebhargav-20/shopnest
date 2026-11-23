<?php
require __DIR__ . '/../includes/init.php';
$cid = require_customer();

$id = (int)get('id');
$order = db_one("SELECT * FROM tblorders WHERE ID=? AND CustomerID=?", [$id, $cid]);
if (!$order) {
    flash('Order not found.', 'error');
    redirect('customer/my-orders.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'cancel') {
    csrf_check();
    if (post('all') === '1') {
        $ids = db_all("SELECT ID FROM tblorderitems WHERE OrderID=? AND Status='Pending'", [$id]);
    } else {
        $ids = db_all("SELECT ID FROM tblorderitems WHERE OrderID=? AND ID=? AND Status='Pending'", [$id, (int)post('item_id')]);
    }
    $n = 0;
    foreach ($ids as $r) if (set_item_status($r['ID'], 'Cancelled', 'Cancelled by customer')) $n++;
    flash($n ? "$n item(s) cancelled." : 'Nothing to cancel — items already confirmed by the seller cannot be cancelled.', $n ? 'success' : 'error');
    redirect('customer/order-details.php?id=' . $id);
}

$items = db_all("SELECT i.*, p.Image FROM tblorderitems i LEFT JOIN tblproduct p ON p.ID=i.ProductID WHERE i.OrderID=?", [$id]);
$mode = 'customer';

$panelRole = 'customer';
$pageTitle = 'Order Details';
include __DIR__ . '/../includes/panel-header.php';
?>
<a href="my-orders.php" class="btn btn-outline btn-sm mb-2"><i class="fa-solid fa-arrow-left"></i> Back to orders</a>
<?php include __DIR__ . '/../includes/order-view.php'; ?>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
