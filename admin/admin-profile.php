<?php
require __DIR__ . '/../includes/init.php';
$aid = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = post('adminname');
    $email = post('email');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('Please enter a name and a valid email.', 'error');
    } else {
        db_query("UPDATE tbladmin SET AdminName=?, MobileNumber=?, Email=? WHERE ID=?",
            [$name, preg_replace('/\D/', '', post('mobile')), $email, $aid]);
        flash('Profile updated.');
    }
    redirect('admin/admin-profile.php');
}
$a = db_one("SELECT * FROM tbladmin WHERE ID=?", [$aid]);
$panelRole = 'admin';
$pageTitle = 'Admin Profile';
include __DIR__ . '/../includes/panel-header.php';
?>
<div class="card" style="max-width:640px">
    <form method="post">
        <?php echo csrf_field(); ?>
        <div class="form-group"><label>Admin Name</label><input type="text" name="adminname" value="<?php echo e($a['AdminName']); ?>" required></div>
        <div class="form-group"><label>Username (cannot be changed)</label><input type="text" value="<?php echo e($a['UserName']); ?>" disabled></div>
        <div class="form-row">
            <div class="form-group"><label>Mobile Number</label><input type="text" name="mobile" maxlength="10" value="<?php echo e($a['MobileNumber']); ?>"></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo e($a['Email']); ?>" required></div>
        </div>
        <p class="muted small">Admin since <?php echo date('d M Y', strtotime($a['AdminRegdate'])); ?></p>
        <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Update Profile</button>
    </form>
</div>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
