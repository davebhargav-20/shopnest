<?php
require __DIR__ . '/../includes/init.php';
require_admin();
$id = (int)get('id');
$order = db_one("SELECT o.*, c.FullName, c.Email, c.MobileNumber FROM tblorders o JOIN tblcustomer c ON c.ID=o.CustomerID WHERE o.ID=?", [$id]);
if (!$order) {
    flash('Order not found.', 'error');
    redirect('admin/all-orders.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (post('action') === 'status') {
        $itemId = (int)post('item_id');
        if (db_val("SELECT COUNT(*) FROM tblorderitems WHERE ID=? AND OrderID=?", [$itemId, $id]) && set_item_status($itemId, post('status'), post('remark'))) {
            flash('Item status updated.');
        } else {
            flash('Could not update this item.', 'error');
        }
    } elseif (post('action') === 'payment' && in_array(post('payment'), ['Pending', 'Paid', 'Refunded'], true)) {
        db_query("UPDATE tblorders SET PaymentStatus=? WHERE ID=?", [post('payment'), $id]);
        flash('Payment status updated.');
    }
    redirect('admin/order-details.php?id=' . $id);
}

$items = db_all("SELECT i.*, p.Image, s.ShopName FROM tblorderitems i LEFT JOIN tblproduct p ON p.ID=i.ProductID
                 JOIN tblseller s ON s.ID=i.SellerID WHERE i.OrderID=?", [$id]);
$mode = 'admin';

$panelRole = 'admin';
$pageTitle = 'Order Details';
include __DIR__ . '/../includes/panel-header.php';
?>
<div class="flex between mb-2">
    <a href="all-orders.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Back to orders</a>
    <form method="post" class="inline-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="payment">
        <label class="mb-0">Payment status</label>
        <select name="payment"><?php foreach (['Pending', 'Paid', 'Refunded'] as $ps) echo '<option' . ($order['PaymentStatus'] === $ps ? ' selected' : '') . '>' . $ps . '</option>'; ?></select>
        <button class="btn btn-sm">Update</button>
    </form>
</div>
<div class="card mb-2" style="padding:1rem 1.5rem">
    <span class="muted small">Customer:</span> <a href="view-customer.php?id=<?php echo (int)$order['CustomerID']; ?>"><b><?php echo e($order['FullName']); ?></b></a>
    <span class="muted small">&middot; <?php echo e($order['Email']); ?> &middot; <?php echo e($order['MobileNumber']); ?></span>
</div>
<?php include __DIR__ . '/../includes/order-view.php'; ?>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
