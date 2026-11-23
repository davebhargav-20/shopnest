<?php
require __DIR__ . '/../includes/init.php';
if (is_seller()) redirect('seller/dashboard.php');

$errors = [];
$done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = post('sellername');
    $shop = post('shopname');
    $email = strtolower(post('email'));
    $mobile = post('mobile');
    $pass = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if ($name === '' || $shop === '') $errors[] = 'Your name and shop name are required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (!preg_match('/^[0-9]{10}$/', $mobile)) $errors[] = 'Enter a valid 10-digit mobile number.';
    if (strlen($pass) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($pass !== (isset($_POST['cpassword']) ? $_POST['cpassword'] : '')) $errors[] = 'Passwords do not match.';
    if (!$errors && db_val("SELECT COUNT(*) FROM tblseller WHERE Email=? OR MobileNumber=?", [$email, $mobile])) {
        $errors[] = 'A seller with this email or mobile number already exists.';
    }
    if (!$errors) {
        list($logo, $err) = upload_image('logo', SELLER_IMG_DIR);
        if ($err) $errors[] = $err;
    }
    if (!$errors) {
        db_query("INSERT INTO tblseller (SellerName, ShopName, Email, MobileNumber, Address, GSTNumber, Logo, Password) VALUES (?,?,?,?,?,?,?,?)",
            [$name, $shop, $email, $mobile, post('address'), post('gst'), $logo, password_hash($pass, PASSWORD_DEFAULT)]);
        $done = true;
    }
}

$pageTitle = 'Become a Seller';
include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
    <div class="card auth-card wide">
        <?php if ($done): ?>
            <div class="text-center">
                <div style="font-size:3rem;color:var(--green)"><i class="fa-solid fa-circle-check"></i></div>
                <h1>Registration Received</h1>
                <p class="muted">Thank you! Your seller account is <b>pending approval</b>. You can login once the admin approves your shop.</p>
                <a href="../login.php?role=seller" class="btn btn-primary mt-2">Go to Seller Login</a>
            </div>
        <?php else: ?>
            <h1 class="text-center">Sell on ShopNest</h1>
            <p class="muted text-center">Register your shop and reach customers across India</p>
            <?php foreach ($errors as $er) echo '<div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> ' . e($er) . '</div>'; ?>
            <form method="post" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="form-row">
                    <div class="form-group"><label>Your Name</label><input type="text" name="sellername" value="<?php echo e(post('sellername')); ?>" required></div>
                    <div class="form-group"><label>Shop / Business Name</label><input type="text" name="shopname" value="<?php echo e(post('shopname')); ?>" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo e(post('email')); ?>" required></div>
                    <div class="form-group"><label>Mobile Number</label><input type="tel" name="mobile" pattern="[0-9]{10}" maxlength="10" value="<?php echo e(post('mobile')); ?>" required></div>
                </div>
                <div class="form-group"><label>Business Address</label><textarea name="address" style="min-height:70px"><?php echo e(post('address')); ?></textarea></div>
                <div class="form-row">
                    <div class="form-group"><label>GST Number (optional)</label><input type="text" name="gst" maxlength="20" value="<?php echo e(post('gst')); ?>"></div>
                    <div class="form-group"><label>Shop Logo (optional)</label><input type="file" name="logo" accept="image/*"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Password</label><input type="password" name="password" minlength="6" required></div>
                    <div class="form-group"><label>Confirm Password</label><input type="password" name="cpassword" minlength="6" required></div>
                </div>
                <button class="btn btn-primary btn-block"><i class="fa-solid fa-store"></i> Register Shop</button>
            </form>
            <p class="text-center muted small mt-2">Already registered? <a href="../login.php?role=seller">Seller login</a></p>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
