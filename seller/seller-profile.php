<?php
require __DIR__ . '/../includes/init.php';
$sid = require_seller();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = post('sellername');
    $shop = post('shopname');
    $mobile = post('mobile');
    if ($name === '' || $shop === '' || !preg_match('/^[0-9]{10}$/', $mobile)) {
        flash('Name, shop name and a valid 10-digit mobile number are required.', 'error');
    } elseif (db_val("SELECT COUNT(*) FROM tblseller WHERE MobileNumber=? AND ID<>?", [$mobile, $sid])) {
        flash('This mobile number is already used by another seller.', 'error');
    } else {
        list($logo, $err) = upload_image('logo', SELLER_IMG_DIR);
        if ($err) {
            flash($err, 'error');
        } else {
            db_query("UPDATE tblseller SET SellerName=?, ShopName=?, MobileNumber=?, Address=?, GSTNumber=? WHERE ID=?",
                [$name, $shop, $mobile, post('address'), post('gst'), $sid]);
            if ($logo) {
                $old = db_val("SELECT Logo FROM tblseller WHERE ID=?", [$sid]);
                if ($old && is_file(SELLER_IMG_DIR . $old)) @unlink(SELLER_IMG_DIR . $old);
                db_query("UPDATE tblseller SET Logo=? WHERE ID=?", [$logo, $sid]);
            }
            flash('Shop profile updated.');
        }
    }
    redirect('seller/seller-profile.php');
}

$s = db_one("SELECT * FROM tblseller WHERE ID=?", [$sid]);
$panelRole = 'seller';
$pageTitle = 'Shop Profile';
include __DIR__ . '/../includes/panel-header.php';
?>
<div class="card" style="max-width:820px">
    <form method="post" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <div class="form-row">
            <div class="form-group"><label>Your Name</label><input type="text" name="sellername" value="<?php echo e($s['SellerName']); ?>" required></div>
            <div class="form-group"><label>Shop Name</label><input type="text" name="shopname" value="<?php echo e($s['ShopName']); ?>" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Email (cannot be changed)</label><input type="email" value="<?php echo e($s['Email']); ?>" disabled></div>
            <div class="form-group"><label>Mobile Number</label><input type="tel" name="mobile" pattern="[0-9]{10}" maxlength="10" value="<?php echo e($s['MobileNumber']); ?>" required></div>
        </div>
        <div class="form-group"><label>Business Address</label><textarea name="address" style="min-height:80px"><?php echo e($s['Address']); ?></textarea></div>
        <div class="form-row">
            <div class="form-group"><label>GST Number</label><input type="text" name="gst" value="<?php echo e($s['GSTNumber']); ?>"></div>
            <div class="form-group"><label>Shop Logo</label><input type="file" name="logo" accept="image/*"></div>
        </div>
        <p class="muted small">Account status: <?php echo status_badge($s['Status']); ?> &middot; Registered on <?php echo date('d M Y', strtotime($s['RegDate'])); ?></p>
        <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
    </form>
</div>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
