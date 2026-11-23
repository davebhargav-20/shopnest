<?php
require __DIR__ . '/../includes/init.php';
require_admin();
$id = (int)get('id');
$c = db_one("SELECT * FROM tblcustomer WHERE ID=?", [$id]);
if (!$c) { flash('Customer not found.', 'error'); redirect('admin/registered-customers.php'); }
$orders = db_all("SELECT * FROM tblorders WHERE CustomerID=? ORDER BY OrderDate DESC", [$id]);

$panelRole = 'admin';
$pageTitle = 'Customer Details';
include __DIR__ . '/../includes/panel-header.php';
?>
<a href="registered-customers.php" class="btn btn-outline btn-sm mb-2"><i class="fa-solid fa-arrow-left"></i> Back to customers</a>
<div class="grid-2-1" style="grid-template-columns:1fr 2fr">
    <div class="card" style="margin-top:0">
        <?php if ($c['Image']): ?><img src="<?php echo url('images/customers/' . $c['Image']); ?>" style="width:90px;height:90px;border-radius:50%;object-fit:cover;margin-bottom:1rem"><?php endif; ?>
        <h3><?php echo e($c['FullName']); ?></h3>
        <dl class="detail-list" style="grid-template-columns:90px 1fr">
            <dt>Email</dt><dd><?php echo e($c['Email']); ?></dd>
            <dt>Mobile</dt><dd><?php echo e($c['MobileNumber']); ?></dd>
            <dt>Gender</dt><dd><?php echo e($c['Gender'] ?: '—'); ?></dd>
            <dt>Address</dt><dd><?php echo e(trim($c['Address'] . ', ' . $c['City'] . ', ' . $c['State'] . ' ' . $c['Pincode'], ', ')); ?></dd>
            <dt>Joined</dt><dd><?php echo date('d M Y', strtotime($c['RegDate'])); ?></dd>
        </dl>
    </div>
    <div class="card" style="margin-top:0">
        <div class="card-title">Orders <span class="muted small"><?php echo count($orders); ?></span></div>
        <?php if (!$orders): ?><p class="muted">No orders yet.</p><?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
            <tbody><?php foreach ($orders as $o): ?>
                <tr><td><b><?php echo e($o['OrderNumber']); ?></b></td><td><?php echo date('d M Y', strtotime($o['OrderDate'])); ?></td>
                    <td><?php echo money($o['TotalAmount']); ?></td><td><?php echo status_badge(order_overall_status($o['ID'])); ?></td>
                    <td><a class="btn btn-sm btn-outline" href="order-details.php?id=<?php echo (int)$o['ID']; ?>">View</a></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
