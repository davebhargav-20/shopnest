<?php
require __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pid = (int)post('pid');
    switch (post('action')) {
        case 'toggle_status':
            db_query("UPDATE tblproduct SET Status=IF(Status='Active','Inactive','Active') WHERE ID=?", [$pid]);
            flash('Product status changed.');
            break;
        case 'toggle_featured':
            db_query("UPDATE tblproduct SET IsFeatured=1-IsFeatured WHERE ID=?", [$pid]);
            flash('Featured flag changed.');
            break;
        case 'delete':
            $img = db_val("SELECT Image FROM tblproduct WHERE ID=?", [$pid]);
            db_query("DELETE FROM tblproduct WHERE ID=?", [$pid]);
            if ($img && is_file(PRODUCT_IMG_DIR . $img) && !preg_match('/^p\d+\.svg$/', $img)) @unlink(PRODUCT_IMG_DIR . $img);
            flash('Product deleted.');
            break;
    }
    back_or('admin/manage-products.php');
}

$q = get('q');
$cat = (int)get('cat');
$seller = (int)get('seller');
$where = '1';
$params = [];
if ($q !== '') { $where .= " AND (p.ProductName LIKE ? OR p.Brand LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($cat) { $where .= " AND p.CategoryID=?"; $params[] = $cat; }
if ($seller) { $where .= " AND p.SellerID=?"; $params[] = $seller; }
if (get('status') === 'Inactive') $where .= " AND p.Status='Inactive'";
if (get('status') === 'out') $where .= " AND p.Stock=0";
if (get('status') === 'featured') $where .= " AND p.IsFeatured=1";

$products = db_all("SELECT p.*, c.CategoryName, s.ShopName FROM tblproduct p
                    JOIN tblcategory c ON c.ID=p.CategoryID JOIN tblseller s ON s.ID=p.SellerID
                    WHERE $where ORDER BY p.ID DESC", $params);
$cats = db_all("SELECT ID, CategoryName FROM tblcategory ORDER BY CategoryName");
$sellers = db_all("SELECT ID, ShopName FROM tblseller ORDER BY ShopName");

$panelRole = 'admin';
$pageTitle = 'All Products';
include __DIR__ . '/../includes/panel-header.php';
?>
<div class="card">
    <form method="get" class="flex mb-2" style="align-items:flex-end">
        <div style="flex:1;min-width:180px"><label>Search</label><input type="search" name="q" value="<?php echo e($q); ?>" placeholder="Product or brand"></div>
        <div><label>Category</label><select name="cat"><option value="0">All</option><?php foreach ($cats as $ct) echo '<option value="' . (int)$ct['ID'] . '"' . ($cat === (int)$ct['ID'] ? ' selected' : '') . '>' . e($ct['CategoryName']) . '</option>'; ?></select></div>
        <div><label>Seller</label><select name="seller"><option value="0">All</option><?php foreach ($sellers as $s) echo '<option value="' . (int)$s['ID'] . '"' . ($seller === (int)$s['ID'] ? ' selected' : '') . '>' . e($s['ShopName']) . '</option>'; ?></select></div>
        <div><label>Show</label><select name="status">
            <option value="">All</option>
            <option value="Inactive" <?php echo get('status') === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
            <option value="out" <?php echo get('status') === 'out' ? 'selected' : ''; ?>>Out of stock</option>
            <option value="featured" <?php echo get('status') === 'featured' ? 'selected' : ''; ?>>Featured</option>
        </select></div>
        <button class="btn btn-primary">Filter</button>
    </form>

    <div class="card-title">Products <span class="muted small"><?php echo count($products); ?> found</span></div>
    <?php if (!$products): ?><div class="empty"><i class="fa-solid fa-box-open"></i>No products found.</div><?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Product</th><th>Seller</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Featured</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><div class="flex" style="flex-wrap:nowrap"><img class="thumb" src="<?php echo product_img($p['Image']); ?>" alt=""><div><b><?php echo e($p['ProductName']); ?></b><div class="muted small"><?php echo e($p['Brand']); ?></div></div></div></td>
                    <td class="small"><?php echo e($p['ShopName']); ?></td>
                    <td class="small"><?php echo e($p['CategoryName']); ?></td>
                    <td><?php echo money($p['Price']); ?></td>
                    <td><?php echo $p['Stock'] > 0 ? (int)$p['Stock'] : '<span class="badge badge-red">0</span>'; ?></td>
                    <td>
                        <form method="post"><?php echo csrf_field(); ?><input type="hidden" name="action" value="toggle_status"><input type="hidden" name="pid" value="<?php echo (int)$p['ID']; ?>">
                            <button class="btn btn-sm <?php echo $p['Status'] === 'Active' ? 'btn-success' : 'btn-danger'; ?>" title="Click to toggle"><?php echo e($p['Status']); ?></button></form>
                    </td>
                    <td>
                        <form method="post"><?php echo csrf_field(); ?><input type="hidden" name="action" value="toggle_featured"><input type="hidden" name="pid" value="<?php echo (int)$p['ID']; ?>">
                            <button class="btn btn-sm btn-outline" title="Toggle featured"><i class="fa-<?php echo $p['IsFeatured'] ? 'solid' : 'regular'; ?> fa-star" style="color:var(--amber)"></i></button></form>
                    </td>
                    <td>
                        <div class="flex" style="gap:.4rem;flex-wrap:nowrap">
                            <a class="btn btn-sm btn-outline" href="../product-details.php?id=<?php echo (int)$p['ID']; ?>" target="_blank"><i class="fa-solid fa-eye"></i></a>
                            <form method="post" data-confirm="Delete this product permanently?"><?php echo csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="pid" value="<?php echo (int)$p['ID']; ?>">
                                <button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button></form>
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
