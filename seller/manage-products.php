<?php
require __DIR__ . '/../includes/init.php';
$sid = require_seller();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pid = (int)post('pid');
    if (post('action') === 'delete') {
        $p = db_one("SELECT Image FROM tblproduct WHERE ID=? AND SellerID=?", [$pid, $sid]);
        if ($p) {
            db_query("DELETE FROM tblproduct WHERE ID=? AND SellerID=?", [$pid, $sid]);
            if ($p['Image'] && is_file(PRODUCT_IMG_DIR . $p['Image']) && !preg_match('/^p\d+\.svg$/', $p['Image'])) @unlink(PRODUCT_IMG_DIR . $p['Image']);
            flash('Product deleted. Past orders keep their details.');
        }
    } elseif (post('action') === 'stock') {
        db_query("UPDATE tblproduct SET Stock=? WHERE ID=? AND SellerID=?", [max(0, (int)post('stock')), $pid, $sid]);
        flash('Stock updated.');
    }
    back_or('seller/manage-products.php');
}

$q = get('q');
$where = "p.SellerID=?";
$params = [$sid];
if ($q !== '') { $where .= " AND (p.ProductName LIKE ? OR p.Brand LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
if (get('stock') === 'out') $where .= " AND p.Stock=0";
if (get('stock') === 'low') $where .= " AND p.Stock<=5";
$products = db_all("SELECT p.*, c.CategoryName,
                    (SELECT COALESCE(SUM(Quantity),0) FROM tblorderitems i WHERE i.ProductID=p.ID AND i.Status<>'Cancelled') AS Sold
                    FROM tblproduct p JOIN tblcategory c ON c.ID=p.CategoryID WHERE $where ORDER BY p.ID DESC", $params);

$panelRole = 'seller';
$pageTitle = 'Manage Products';
include __DIR__ . '/../includes/panel-header.php';
?>
<div class="card">
    <div class="card-title">
        <span>My Products <span class="muted small">(<?php echo count($products); ?>)</span></span>
        <div class="flex">
            <form method="get" class="inline-form"><input type="search" name="q" value="<?php echo e($q); ?>" placeholder="Search products"><button class="btn btn-sm">Search</button></form>
            <a href="add-product.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Add Product</a>
        </div>
    </div>
    <div class="filter-bar">
        <a href="manage-products.php" class="<?php echo get('stock') === '' ? 'active' : ''; ?>">All</a>
        <a href="?stock=low" class="<?php echo get('stock') === 'low' ? 'active' : ''; ?>">Low stock (≤5)</a>
        <a href="?stock=out" class="<?php echo get('stock') === 'out' ? 'active' : ''; ?>">Out of stock</a>
    </div>
    <?php if (!$products): ?>
        <div class="empty"><i class="fa-solid fa-box-open"></i>No products found. <a href="add-product.php">Add your first product</a></div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Sold</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td>
                        <div class="flex" style="flex-wrap:nowrap">
                            <img class="thumb" src="<?php echo product_img($p['Image']); ?>" alt="">
                            <div><b><?php echo e($p['ProductName']); ?></b><div class="muted small"><?php echo e($p['Brand']); ?><?php echo $p['IsFeatured'] ? ' · <span style="color:var(--amber)">★ Featured</span>' : ''; ?></div></div>
                        </div>
                    </td>
                    <td><?php echo e($p['CategoryName']); ?></td>
                    <td><?php echo money($p['Price']); ?><div class="muted small"><s><?php echo money($p['MRP']); ?></s></div></td>
                    <td>
                        <form method="post" class="inline-form">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="stock"><input type="hidden" name="pid" value="<?php echo (int)$p['ID']; ?>">
                            <input type="number" name="stock" min="0" value="<?php echo (int)$p['Stock']; ?>" style="width:80px">
                            <button class="btn btn-sm" title="Update stock"><i class="fa-solid fa-check"></i></button>
                        </form>
                    </td>
                    <td><?php echo (int)$p['Sold']; ?></td>
                    <td><?php echo status_badge($p['Status']); ?></td>
                    <td>
                        <div class="flex" style="gap:.4rem;flex-wrap:nowrap">
                            <a class="btn btn-sm btn-outline" href="../product-details.php?id=<?php echo (int)$p['ID']; ?>" target="_blank" title="View"><i class="fa-solid fa-eye"></i></a>
                            <a class="btn btn-sm btn-outline" href="edit-product.php?id=<?php echo (int)$p['ID']; ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <form method="post" data-confirm="Delete this product permanently?">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete"><input type="hidden" name="pid" value="<?php echo (int)$p['ID']; ?>">
                                <button class="btn btn-sm btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
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
