<?php
require __DIR__ . '/includes/init.php';

$q        = get('q');
$cat      = (int)get('cat', 0);
$min      = get('min') !== '' ? (float)get('min') : null;
$max      = get('max') !== '' ? (float)get('max') : null;
$sort     = get('sort', 'new');
$featured = get('featured') === '1';
$instock  = get('instock') === '1';
$page     = max(1, (int)get('page', 1));
$perPage  = 12;

$where = '';
$params = [];
if ($q !== '') {
    $where .= " AND (p.ProductName LIKE ? OR p.Brand LIKE ? OR p.Description LIKE ? OR c.CategoryName LIKE ?)";
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($cat)            { $where .= " AND p.CategoryID = ?"; $params[] = $cat; }
if ($min !== null)   { $where .= " AND p.Price >= ?"; $params[] = $min; }
if ($max !== null)   { $where .= " AND p.Price <= ?"; $params[] = $max; }
if ($featured)       { $where .= " AND p.IsFeatured = 1"; }
if ($instock)        { $where .= " AND p.Stock > 0"; }

$sorts = [
    'new'        => 'p.ID DESC',
    'price_asc'  => 'p.Price ASC',
    'price_desc' => 'p.Price DESC',
    'discount'   => '(p.MRP - p.Price) / p.MRP DESC',
    'rating'     => 'AvgRating DESC, ReviewCount DESC',
];
$orderBy = isset($sorts[$sort]) ? $sorts[$sort] : $sorts['new'];

$total = (int)db_val("SELECT COUNT(*) FROM (" . PRODUCT_SELECT . $where . " GROUP BY p.ID) t", $params);
$pages = max(1, (int)ceil($total / $perPage));
$page  = min($page, $pages);
$offset = ($page - 1) * $perPage;

$products = db_all(PRODUCT_SELECT . $where . " GROUP BY p.ID ORDER BY $orderBy LIMIT $perPage OFFSET $offset", $params);
$categories = db_all("SELECT c.ID, c.CategoryName, COUNT(p.ID) Total FROM tblcategory c
                      LEFT JOIN tblproduct p ON p.CategoryID=c.ID AND p.Status='Active'
                      AND p.SellerID IN (SELECT ID FROM tblseller WHERE Status='Active')
                      GROUP BY c.ID ORDER BY c.CategoryName");
$catName = '';
foreach ($categories as $c) if ((int)$c['ID'] === $cat) $catName = $c['CategoryName'];
$wishIds = wish_ids();

/* keep current filters when building links */
function shop_link($changes)
{
    $qs = array_merge($_GET, $changes);
    foreach ($qs as $k => $v) if ($v === '' || $v === null) unset($qs[$k]);
    return 'shop.php' . ($qs ? '?' . http_build_query($qs) : '');
}

$pageTitle = $q !== '' ? 'Search: ' . $q : ($catName ?: 'Shop');
include __DIR__ . '/includes/header.php';
?>

<div class="container section">
    <div class="breadcrumb"><a href="index.php">Home</a> / <a href="shop.php">Shop</a><?php if ($catName) echo ' / ' . e($catName); ?></div>

    <div class="shop-layout">
        <aside class="card filters">
            <h4>Categories</h4>
            <div class="filter-list">
                <a href="<?php echo e(shop_link(['cat' => '', 'page' => ''])); ?>" class="<?php echo !$cat ? 'active' : ''; ?>">All Categories</a>
                <?php foreach ($categories as $c): ?>
                    <a href="<?php echo e(shop_link(['cat' => $c['ID'], 'page' => ''])); ?>" class="<?php echo $cat === (int)$c['ID'] ? 'active' : ''; ?>">
                        <?php echo e($c['CategoryName']); ?> <span><?php echo (int)$c['Total']; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <form method="get" action="shop.php">
                <?php if ($q !== '') echo '<input type="hidden" name="q" value="' . e($q) . '">'; ?>
                <?php if ($cat) echo '<input type="hidden" name="cat" value="' . $cat . '">'; ?>
                <input type="hidden" name="sort" value="<?php echo e($sort); ?>">
                <h4>Price (₹)</h4>
                <div class="form-row" style="gap:.5rem">
                    <input type="number" name="min" min="0" placeholder="Min" value="<?php echo $min !== null ? e($min) : ''; ?>">
                    <input type="number" name="max" min="0" placeholder="Max" value="<?php echo $max !== null ? e($max) : ''; ?>">
                </div>
                <h4>Availability</h4>
                <label class="checkbox"><input type="checkbox" name="instock" value="1" <?php echo $instock ? 'checked' : ''; ?>> In stock only</label>
                <label class="checkbox"><input type="checkbox" name="featured" value="1" <?php echo $featured ? 'checked' : ''; ?>> Featured only</label>
                <button class="btn btn-primary btn-block mt-2"><i class="fa-solid fa-filter"></i> Apply Filters</button>
                <a href="shop.php" class="btn btn-outline btn-block mt-1">Clear</a>
            </form>
        </aside>

        <div>
            <div class="toolbar">
                <div>
                    <h2 class="mb-0" style="font-size:1.5rem"><?php echo $q !== '' ? 'Results for "' . e($q) . '"' : e($catName ?: 'All Products'); ?></h2>
                    <span class="muted small"><?php echo $total; ?> product<?php echo $total == 1 ? '' : 's'; ?> found</span>
                </div>
                <form method="get">
                    <?php foreach ($_GET as $k => $v) if ($k !== 'sort' && $k !== 'page' && is_string($v)) echo '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">'; ?>
                    <select name="sort" onchange="this.form.submit()">
                        <option value="new" <?php echo $sort == 'new' ? 'selected' : ''; ?>>Newest first</option>
                        <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="discount" <?php echo $sort == 'discount' ? 'selected' : ''; ?>>Biggest Discount</option>
                        <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Top Rated</option>
                    </select>
                </form>
            </div>

            <?php if (!$products): ?>
                <div class="card empty"><i class="fa-solid fa-box-open"></i>No products match your search. <a href="shop.php">Browse all products</a></div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $p) include __DIR__ . '/includes/product-card.php'; ?>
                </div>
                <?php if ($pages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <?php if ($i === $page): ?><span class="current"><?php echo $i; ?></span>
                            <?php else: ?><a href="<?php echo e(shop_link(['page' => $i])); ?>"><?php echo $i; ?></a><?php endif; ?>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
