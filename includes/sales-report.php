<?php
/* ------------------------------------------------------------
 * Between-dates sales report (like CRMS between-dates-reports).
 * Set $reportSeller = seller ID (seller panel) or null (admin, all sellers)
 * ------------------------------------------------------------ */
$from = get('from', date('Y-m-01'));
$to   = get('to', date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d');
$st = get('st', 'all');   // all | delivered

$where = "DATE(o.OrderDate) BETWEEN ? AND ? AND i.Status<>'Cancelled'";
$params = [$from, $to];
if ($st === 'delivered') $where .= " AND i.Status='Delivered'";
if ($reportSeller) { $where .= " AND i.SellerID=?"; $params[] = (int)$reportSeller; }

$rows = db_all("SELECT i.*, o.OrderNumber, o.OrderDate, o.ShipName, s.ShopName FROM tblorderitems i
                JOIN tblorders o ON o.ID=i.OrderID JOIN tblseller s ON s.ID=i.SellerID
                WHERE $where ORDER BY o.OrderDate", $params);
$byProduct = db_all("SELECT i.ProductName, SUM(i.Quantity) qty, SUM(i.Price*i.Quantity) amt FROM tblorderitems i
                     JOIN tblorders o ON o.ID=i.OrderID WHERE $where GROUP BY i.ProductName ORDER BY amt DESC LIMIT 10", $params);
$total = 0; $units = 0; $orders = [];
foreach ($rows as $r) { $total += $r['Price'] * $r['Quantity']; $units += $r['Quantity']; $orders[$r['OrderID']] = 1; }

$pageTitle = 'Sales Report';
include __DIR__ . '/panel-header.php';
?>
<div class="card">
    <form method="get" class="flex" style="align-items:flex-end">
        <div><label>From Date</label><input type="date" name="from" value="<?php echo e($from); ?>"></div>
        <div><label>To Date</label><input type="date" name="to" value="<?php echo e($to); ?>"></div>
        <div><label>Include</label>
            <select name="st">
                <option value="all" <?php echo $st === 'all' ? 'selected' : ''; ?>>All non-cancelled items</option>
                <option value="delivered" <?php echo $st === 'delivered' ? 'selected' : ''; ?>>Delivered only</option>
            </select>
        </div>
        <button class="btn btn-primary"><i class="fa-solid fa-magnifying-glass-chart"></i> Generate</button>
        <button type="button" class="btn btn-outline" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
    </form>
</div>

<div class="stats mt-3">
    <div class="stat"><div class="ic ic-green"><i class="fa-solid fa-indian-rupee-sign"></i></div><b><?php echo money($total); ?></b><span>Sales Amount</span></div>
    <div class="stat"><div class="ic ic-blue"><i class="fa-solid fa-receipt"></i></div><b><?php echo count($orders); ?></b><span>Orders</span></div>
    <div class="stat"><div class="ic ic-purple"><i class="fa-solid fa-cubes"></i></div><b><?php echo (int)$units; ?></b><span>Units Sold</span></div>
</div>

<div class="grid-2-1">
    <div class="card" style="margin-top:0">
        <div class="card-title">Sales from <?php echo date('d M Y', strtotime($from)); ?> to <?php echo date('d M Y', strtotime($to)); ?></div>
        <?php if (!$rows): ?><div class="empty"><i class="fa-solid fa-chart-simple"></i>No sales in this period.</div><?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>#</th><th>Date</th><th>Order #</th><th>Product</th><?php if (!$reportSeller) echo '<th>Seller</th>'; ?><th>Qty</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $n => $r): ?>
                    <tr>
                        <td><?php echo $n + 1; ?></td>
                        <td><?php echo date('d M Y', strtotime($r['OrderDate'])); ?></td>
                        <td><a href="order-details.php?id=<?php echo (int)$r['OrderID']; ?>"><?php echo e($r['OrderNumber']); ?></a></td>
                        <td><?php echo e($r['ProductName']); ?></td>
                        <?php if (!$reportSeller): ?><td><?php echo e($r['ShopName']); ?></td><?php endif; ?>
                        <td><?php echo (int)$r['Quantity']; ?></td>
                        <td><?php echo money($r['Price'] * $r['Quantity']); ?></td>
                        <td><?php echo status_badge($r['Status']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr><td colspan="<?php echo $reportSeller ? 5 : 6; ?>" class="text-right"><b>Total</b></td><td colspan="2"><b><?php echo money($total); ?></b></td></tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <div class="card" style="margin-top:0">
        <div class="card-title">Top Products</div>
        <?php if (!$byProduct) echo '<p class="muted">No data.</p>'; ?>
        <?php foreach ($byProduct as $b): ?>
            <div class="summary-row small"><span><?php echo e($b['ProductName']); ?> <span class="muted">× <?php echo (int)$b['qty']; ?></span></span><b><?php echo money($b['amt']); ?></b></div>
        <?php endforeach; ?>
    </div>
</div>
<?php include __DIR__ . '/panel-footer.php'; ?>
