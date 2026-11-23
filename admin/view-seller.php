<?php
require __DIR__ . '/../includes/init.php';
require_admin();
$id = (int)get('id');
$s = db_one("SELECT * FROM tblseller WHERE ID=?", [$id]);
if (!$s) { flash('Seller not found.', 'error'); redirect('admin/manage-sellers.php'); }

$stats = db_one("SELECT COUNT(*) items, COALESCE(SUM(Price*Quantity),0) sales, SUM(Status='Delivered') delivered, SUM(Status='Pending') pending
                 FROM tblorderitems WHERE SellerID=? AND Status<>'Cancelled'", [$id]);
$products = db_all("SELECT p.*, c.CategoryName FROM tblproduct p JOIN tblcategory c ON c.ID=p.CategoryID WHERE p.SellerID=? ORDER BY p.ID DESC", [$id]);

$panelRole = 'admin';
$pageTitle = 'Seller Details';
include __DIR__ . '/../includes/panel-header.php';
?>
<a href="manage-sellers.php" class="btn btn-outline btn-sm mb-2"><i class="fa-solid fa-arrow-left"></i> Back to sellers</a>
<div class="grid-2">
    <div class="card" style="margin-top:0">
        <div class="card-title"><?php echo e($s['ShopName']); ?> <?php echo status_badge($s['Status']); ?></div>
        <?php if ($s['Logo']): ?><img src="<?php echo url('images/sellers/' . $s['Logo']); ?>" style="width:80px;height:80px;border-radius:14px;object-fit:cover;margin-bottom:1rem"><?php endif; ?>
        <dl class="detail-list">
            <dt>Owner</dt><dd><?php echo e($s['SellerName']); ?></dd>
            <dt>Email</dt><dd><?php echo e($s['Email']); ?></dd>
            <dt>Mobile</dt><dd><?php echo e($s['MobileNumber']); ?></dd>
            <dt>GST Number</dt><dd><?php echo e($s['GSTNumber'] ?: '—'); ?></dd>
            <dt>Address</dt><dd><?php echo nl2br(e($s['Address'])); ?></dd>
            <dt>Registered</dt><dd><?php echo date('d M Y, h:i A', strtotime($s['RegDate'])); ?></dd>
        </dl>
    </div>
    <div class="stats" style="margin:0;align-content:start">
        <div class="stat"><div class="ic ic-green"><i class="fa-solid fa-indian-rupee-sign"></i></div><b><?php echo money($stats['sales']); ?></b><span>Total Sales</span></div>
        <div class="stat"><div class="ic ic-blue"><i class="fa-solid fa-box"></i></div><b><?php echo count($products); ?></b><span>Products</span></div>
        <div class="stat"><div class="ic ic-cyan"><i class="fa-solid fa-circle-check"></i></div><b><?php echo (int)$stats['delivered']; ?></b><span>Items Delivered</span></div>
        <div class="stat"><div class="ic ic-amber"><i class="fa-solid fa-hourglass-half"></i></div><b><?php echo (int)$stats['pending']; ?></b><span>Pending Items</span></div>
    </div>
</div>
<div class="card">
    <div class="card-title">Products by this seller <a class="small" href="between-dates-reports.php?seller=<?php echo $id; ?>">Sales report</a></div>
    <?php if (!$products): ?><p class="muted">No products listed yet.</p><?php else: ?>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th></tr></thead>
        <tbody><?php foreach ($products as $p): ?>
            <tr><td><div class="flex" style="flex-wrap:nowrap"><img class="thumb" src="<?php echo product_img($p['Image']); ?>"><?php echo e($p['ProductName']); ?></div></td>
                <td><?php echo e($p['CategoryName']); ?></td><td><?php echo money($p['Price']); ?></td><td><?php echo (int)$p['Stock']; ?></td><td><?php echo status_badge($p['Status']); ?></td></tr>
        <?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
