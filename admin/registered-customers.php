<?php
require __DIR__ . '/../includes/init.php';
require_admin();

$q = get('q');
$where = '1';
$params = [];
if ($q !== '') { $where = "(c.FullName LIKE ? OR c.Email LIKE ? OR c.MobileNumber LIKE ?)"; $params = ["%$q%", "%$q%", "%$q%"]; }
$customers = db_all("SELECT c.*, (SELECT COUNT(*) FROM tblorders o WHERE o.CustomerID=c.ID) Orders,
                     (SELECT COALESCE(SUM(TotalAmount),0) FROM tblorders o WHERE o.CustomerID=c.ID) Spent
                     FROM tblcustomer c WHERE $where ORDER BY c.RegDate DESC", $params);

$panelRole = 'admin';
$pageTitle = 'Registered Customers';
include __DIR__ . '/../includes/panel-header.php';
?>
<div class="card">
    <div class="card-title">
        <span>Customers <span class="muted small">(<?php echo count($customers); ?>)</span></span>
        <form method="get" class="inline-form"><input type="search" name="q" value="<?php echo e($q); ?>" placeholder="Name, email, mobile"><button class="btn btn-sm">Search</button></form>
    </div>
    <?php if (!$customers): ?><div class="empty"><i class="fa-solid fa-users-slash"></i>No customers found.</div><?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Mobile</th><th>City</th><th>Orders</th><th>Spent</th><th>Registered</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($customers as $n => $c): ?>
                <tr>
                    <td><?php echo $n + 1; ?></td>
                    <td><b><?php echo e($c['FullName']); ?></b></td>
                    <td><?php echo e($c['Email']); ?></td>
                    <td><?php echo e($c['MobileNumber']); ?></td>
                    <td><?php echo e($c['City']); ?></td>
                    <td><?php echo (int)$c['Orders']; ?></td>
                    <td><?php echo money($c['Spent']); ?></td>
                    <td class="small"><?php echo date('d M Y', strtotime($c['RegDate'])); ?></td>
                    <td><a class="btn btn-sm btn-outline" href="view-customer.php?id=<?php echo (int)$c['ID']; ?>"><i class="fa-solid fa-eye"></i></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
