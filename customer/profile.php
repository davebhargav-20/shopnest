<?php
require __DIR__ . '/../includes/init.php';
$cid = require_customer();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = post('fullname');
    $mobile = post('mobile');
    if ($name === '' || !preg_match('/^[0-9]{10}$/', $mobile)) {
        flash('Please enter your name and a valid 10-digit mobile number.', 'error');
    } elseif (db_val("SELECT COUNT(*) FROM tblcustomer WHERE MobileNumber=? AND ID<>?", [$mobile, $cid])) {
        flash('This mobile number is already used by another account.', 'error');
    } else {
        list($img, $err) = upload_image('image', CUSTOMER_IMG_DIR);
        if ($err) {
            flash($err, 'error');
        } else {
            db_query("UPDATE tblcustomer SET FullName=?, MobileNumber=?, Gender=?, Address=?, City=?, State=?, Pincode=? WHERE ID=?",
                [$name, $mobile, post('gender'), post('address'), post('city'), post('state'), post('pincode'), $cid]);
            if ($img) {
                $old = db_val("SELECT Image FROM tblcustomer WHERE ID=?", [$cid]);
                if ($old && is_file(CUSTOMER_IMG_DIR . $old)) @unlink(CUSTOMER_IMG_DIR . $old);
                db_query("UPDATE tblcustomer SET Image=? WHERE ID=?", [$img, $cid]);
            }
            $_SESSION['fname'] = $name;
            flash('Profile updated successfully.');
        }
    }
    redirect('customer/profile.php');
}

$u = db_one("SELECT * FROM tblcustomer WHERE ID=?", [$cid]);
$panelRole = 'customer';
$pageTitle = 'My Profile';
include __DIR__ . '/../includes/panel-header.php';
?>
<div class="card" style="max-width:820px">
    <form method="post" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="form-row">
            <div class="form-group"><label>Full Name</label><input type="text" name="fullname" value="<?php echo e($u['FullName']); ?>" required></div>
            <div class="form-group"><label>Email (cannot be changed)</label><input type="email" value="<?php echo e($u['Email']); ?>" disabled></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Mobile Number</label><input type="tel" name="mobile" pattern="[0-9]{10}" maxlength="10" value="<?php echo e($u['MobileNumber']); ?>" required></div>
            <div class="form-group"><label>Gender</label>
                <select name="gender">
                    <option value="">Select</option>
                    <?php foreach (['Male', 'Female', 'Other'] as $g): ?><option <?php echo $u['Gender'] === $g ? 'selected' : ''; ?>><?php echo $g; ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group"><label>Default Delivery Address</label><textarea name="address" style="min-height:80px"><?php echo e($u['Address']); ?></textarea></div>
        <div class="form-row-3">
            <div class="form-group"><label>City</label><input type="text" name="city" value="<?php echo e($u['City']); ?>"></div>
            <div class="form-group"><label>State</label><input type="text" name="state" value="<?php echo e($u['State']); ?>"></div>
            <div class="form-group"><label>Pincode</label><input type="text" name="pincode" maxlength="6" value="<?php echo e($u['Pincode']); ?>"></div>
        </div>
        <div class="form-group"><label>Profile Photo (JPG/PNG, max 2 MB)</label><input type="file" name="image" accept="image/*"></div>
        <p class="muted small">Member since <?php echo date('d M Y', strtotime($u['RegDate'])); ?></p>
        <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
    </form>
</div>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
