<?php
require __DIR__ . '/includes/init.php';
if (is_customer()) redirect('customer/dashboard.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = post('fullname');
    $email = strtolower(post('email'));
    $mobile = post('mobile');
    $pass = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $cpass = isset($_POST['cpassword']) ? (string)$_POST['cpassword'] : '';

    if ($name === '') $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (!preg_match('/^[0-9]{10}$/', $mobile)) $errors[] = 'Enter a valid 10-digit mobile number.';
    if (strlen($pass) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($pass !== $cpass) $errors[] = 'Passwords do not match.';
    if (!$errors && db_val("SELECT COUNT(*) FROM tblcustomer WHERE Email=? OR MobileNumber=?", [$email, $mobile])) {
        $errors[] = 'An account with this email or mobile number already exists.';
    }

    if (!$errors) {
        db_query("INSERT INTO tblcustomer (FullName, Email, MobileNumber, Password) VALUES (?,?,?,?)",
            [$name, $email, $mobile, password_hash($pass, PASSWORD_DEFAULT)]);
        logout_all();
        $_SESSION['sncid'] = db_insert_id();
        $_SESSION['fname'] = $name;
        flash('Welcome to ShopNest, ' . $name . '! Your account has been created.');
        redirect(cart_count() ? 'cart.php' : 'index.php');
    }
}

$pageTitle = 'Create Account';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="card auth-card">
        <h1 class="text-center">Create Account</h1>
        <p class="muted text-center">Sign up to shop, track orders and save your wishlist</p>
        <?php foreach ($errors as $er) echo '<div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> ' . e($er) . '</div>'; ?>
        <form method="post">
            <?php echo csrf_field(); ?>
            <div class="form-group"><label>Full Name</label><input type="text" name="fullname" value="<?php echo e(post('fullname')); ?>" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo e(post('email')); ?>" required></div>
            <div class="form-group"><label>Mobile Number</label><input type="tel" name="mobile" pattern="[0-9]{10}" maxlength="10" value="<?php echo e(post('mobile')); ?>" required></div>
            <div class="form-row">
                <div class="form-group"><label>Password</label><input type="password" name="password" minlength="6" required></div>
                <div class="form-group"><label>Confirm Password</label><input type="password" name="cpassword" minlength="6" required></div>
            </div>
            <button class="btn btn-primary btn-block"><i class="fa-solid fa-user-plus"></i> Sign Up</button>
        </form>
        <p class="text-center muted small mt-2">Already have an account? <a href="login.php">Login</a></p>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
