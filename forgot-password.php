<?php
/* Reset password by verifying registered email + mobile number (customer / seller / admin) */
require __DIR__ . '/includes/init.php';

$roles = ['customer' => 'tblcustomer', 'seller' => 'tblseller', 'admin' => 'tbladmin'];
$role = isset($roles[get('role')]) ? get('role') : 'customer';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $role = isset($roles[post('role')]) ? post('role') : 'customer';
    $table = $roles[$role];
    $email = post('email');
    $mobile = post('mobile');
    $pass = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if (strlen($pass) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($pass !== (isset($_POST['cpassword']) ? $_POST['cpassword'] : '')) {
        $error = 'Passwords do not match.';
    } else {
        $id = db_val("SELECT ID FROM $table WHERE Email=? AND MobileNumber=?", [$email, $mobile]);
        if (!$id) {
            $error = 'No account found with that email and mobile number.';
        } else {
            db_query("UPDATE $table SET Password=? WHERE ID=?", [password_hash($pass, PASSWORD_DEFAULT), (int)$id]);
            flash('Your password has been reset. Please login with the new password.');
            redirect('login.php?role=' . $role);
        }
    }
}

$pageTitle = 'Forgot Password';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="card auth-card">
        <h1 class="text-center">Reset Password</h1>
        <p class="muted text-center">Verify your registered email and mobile number</p>
        <?php if ($error) echo '<div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> ' . e($error) . '</div>'; ?>
        <form method="post">
            <?php echo csrf_field(); ?>
            <div class="role-tabs">
                <?php foreach (array_keys($roles) as $r): ?>
                    <label><input type="radio" name="role" value="<?php echo $r; ?>" <?php echo $role === $r ? 'checked' : ''; ?>> <?php echo ucfirst($r); ?></label>
                <?php endforeach; ?>
            </div>
            <div class="form-group"><label>Registered Email</label><input type="email" name="email" value="<?php echo e(post('email')); ?>" required></div>
            <div class="form-group"><label>Registered Mobile Number</label><input type="tel" name="mobile" value="<?php echo e(post('mobile')); ?>" required></div>
            <div class="form-row">
                <div class="form-group"><label>New Password</label><input type="password" name="password" minlength="6" required></div>
                <div class="form-group"><label>Confirm Password</label><input type="password" name="cpassword" minlength="6" required></div>
            </div>
            <button class="btn btn-primary btn-block">Reset Password</button>
        </form>
        <p class="text-center muted small mt-2"><a href="login.php">Back to login</a></p>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
