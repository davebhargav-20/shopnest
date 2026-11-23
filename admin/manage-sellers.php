<?php
require __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $sid = (int)post('sid');
    $new = post('status');
    if (in_array($new, ['Active', 'Blocked', 'Pending'], true)) {
        db_query("UPDATE tblseller SET Status=? WHERE ID=?", [$new, $sid]);
        flash($new === 'Active' ? 'Seller approved / activated.' : 'Seller status set to ' . $new . '.');
    }
    back_or('admin/manage-sellers.php');
}

$status = get('status');
$where = '1';
$params = [];
if (in_array($status, ['Active', 'Pending', 'Blocked'], true)) { $where = 's.Status=?'; $params[] = $status; }
$sellers = db_all("SELECT s.*, (SELECT COUNT(*) FROM tblproduct p WHERE p.SellerID=s.ID) Products,
                   (SELECT COALESCE(SUM(Price*Quantity),0) FROM tblorderitems i WHERE i.SellerID=s.ID AND i.Status<>'Cancelled') Sales
                   FROM tblseller s WHERE $where ORDER BY FIELD(s.Status,'Pending','Active','Blocked'), s.RegDate DESC", $params);

$panelRole = 'admin';
$pageTitle = 'Sellers';
include __DIR__ . '/../includes/panel-header.php';
?>
<div class="card">
    <div class="card-title">Registered Sellers <span class="muted small"><?php echo count($sellers); ?></span></div>
    <div class="filter-bar">
        <a href="manage-sellers.php" class="<?php echo !$status ? 'active' : ''; ?>">All</a>
        <?php foreach (['Pending', 'Active', 'Blocked'] as $s) echo '<a href="?status=' . $s . '" class="' . ($status === $s ? 'active' : '') . '">' . $s . '</a>'; ?>
    </div>
    <?php if (!$sellers): ?><div class="empty"><i class="fa-solid fa-store-slash"></i>No sellers found.</div><?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>#</th><th>Shop</th><th>Owner</th><th>Contact</th><th>Products</th><th>Sales</th><th>Registered</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($sellers as $n => $s): ?>
                <tr>
                    <td><?php echo $n + 1; ?></td>
                    <td><b><?php echo e($s['ShopName']); ?></b></td>
                    <td><?php echo e($s['SellerName']); ?></td>
                    <td class="small"><?php echo e($s['Email']); ?><br><?php echo e($s['MobileNumber']); ?></td>
                    <td><?php echo (int)$s['Products']; ?></td>
                    <td><?php echo money($s['Sales']); ?></td>
                    <td class="small"><?php echo date('d M Y', strtotime($s['RegDate'])); ?></td>
                    <td><?php echo status_badge($s['Status']); ?></td>
                    <td>
                        <div class="flex" style="gap:.4rem;flex-wrap:nowrap">
                            <a class="btn btn-sm btn-outline" href="view-seller.php?id=<?php echo (int)$s['ID']; ?>"><i class="fa-solid fa-eye"></i></a>
                            <?php if ($s['Status'] !== 'Active'): ?>
                                <form method="post"><?php echo csrf_field(); ?><input type="hidden" name="sid" value="<?php echo (int)$s['ID']; ?>"><input type="hidden" name="status" value="Active">
                                    <button class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i> <?php echo $s['Status'] === 'Pending' ? 'Approve' : 'Unblock'; ?></button></form>
                            <?php endif; ?>
                            <?php if ($s['Status'] !== 'Blocked'): ?>
                                <form method="post" data-confirm="Block this seller? Their products will be hidden from the store."><?php echo csrf_field(); ?><input type="hidden" name="sid" value="<?php echo (int)$s['ID']; ?>"><input type="hidden" name="status" value="Blocked">
                                    <button class="btn btn-sm btn-danger"><i class="fa-solid fa-ban"></i> <?php echo $s['Status'] === 'Pending' ? 'Reject' : 'Block'; ?></button></form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
