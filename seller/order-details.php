<?php
require __DIR__ . '/../includes/init.php';
$sid = require_seller();
$id = (int)get('id');

/* seller can only open orders that contain their items */
$order = db_one("SELECT o.* FROM tblorders o WHERE o.ID=? AND EXISTS (SELECT 1 FROM tblorderitems i WHERE i.OrderID=o.ID AND i.SellerID=?)", [$id, $sid]);
if (!$order) {
    flash('Order not found.', 'error');
    redirect('seller/orders.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'status') {
    csrf_check();
    $itemId = (int)post('item_id');
    if (db_val("SELECT COUNT(*) FROM tblorderitems WHERE ID=? AND OrderID=? AND SellerID=?", [$itemId, $id, $sid])
        && set_item_status($itemId, post('status'), post('remark'))) {
        flash('Item status updated to ' . post('status') . '.');
    } else {
        flash('Could not update this item.', 'error');
    }
    redirect('seller/order-details.php?id=' . $id);
}

$items = db_all("SELECT i.*, p.Image FROM tblorderitems i LEFT JOIN tblproduct p ON p.ID=i.ProductID WHERE i.OrderID=? AND i.SellerID=?", [$id, $sid]);
$mode = 'seller';

$panelRole = 'seller';
$pageTitle = 'Order Details';
include __DIR__ . '/../includes/panel-header.php';
?>
<a href="orders.php" class="btn btn-outline btn-sm mb-2"><i class="fa-solid fa-arrow-left"></i> Back to orders</a>
<?php include __DIR__ . '/../includes/order-view.php'; ?>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
