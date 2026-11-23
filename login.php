<?php
require __DIR__ . '/includes/init.php';

/* Already logged in → go to the right place */
if (is_customer()) redirect('customer/dashboard.php');
if (is_seller())   redirect('seller/dashboard.php');
if (is_admin())    redirect('admin/dashboard.php');

$roles = ['customer', 'seller', 'admin'];
$role = in_array(get('role'), $roles, true) ? get('role') : 'customer';
$next = get('next');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $role = in_array(post('user_role'), $roles, true) ? post('user_role') : 'customer';
    $login = post('login_id');
    $pass = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $next = post('next');

    if ($login === '' || $pass === '') {
        $error = 'Please enter your login details.';
    } elseif ($role === 'admin') {
        $u = db_one("SELECT ID, Password FROM tbladmin WHERE UserName=? OR Email=?", [$login, $login]);
        if ($u && password_verify($pass, $u['Password'])) {
            logout_all();
            $_SESSION['snaid'] = (int)$u['ID'];
            redirect('admin/dashboard.php');
        }
        $error = 'Invalid username or password.';
    } elseif ($role === 'seller') {
        $u = db_one("SELECT ID, Password, Status FROM tblseller WHERE Email=? OR MobileNumber=?", [$login, $login]);
        if ($u && password_verify($pass, $u['Password'])) {
            if ($u['Status'] === 'Pending') {
                $error = 'Your seller account is waiting for admin approval.';
            } elseif ($u['Status'] === 'Blocked') {
                $error = 'Your seller account has been blocked. Please contact support.';
            } else {
                logout_all();
                $_SESSION['snsid'] = (int)$u['ID'];
                redirect('seller/dashboard.php');
            }
        } else {
            $error = 'Invalid email/mobile or password.';
        }
    } else {
        $u = db_one("SELECT ID, FullName, Password FROM tblcustomer WHERE Email=? OR MobileNumber=?", [$login, $login]);
        if ($u && password_verify($pass, $u['Password'])) {
            logout_all();
            $_SESSION['sncid'] = (int)$u['ID'];
            $_SESSION['fname'] = $u['FullName'];
            header('Location: ' . safe_next($next, 'index.php'));
            exit;
        }
        $error = 'Invalid email/mobile or password.';
    }
}

$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="card auth-card">
        <h1 class="text-center">Welcome back</h1>
        <p class="muted text-center">Login to your ShopNest account</p>
        <?php if ($error) echo '<div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> ' . e($error) . '</div>'; ?>

        <form method="post" id="loginForm">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="next" value="<?php echo e($next); ?>">
            <div class="role-tabs">
                <label><input type="radio" name="user_role" value="customer" <?php echo $role === 'customer' ? 'checked' : ''; ?>><i class="fa-solid fa-user"></i> Customer</label>
                <label><input type="radio" name="user_role" value="seller" <?php echo $role === 'seller' ? 'checked' : ''; ?>><i class="fa-solid fa-store"></i> Seller</label>
                <label><input type="radio" name="user_role" value="admin" <?php echo $role === 'admin' ? 'checked' : ''; ?>><i class="fa-solid fa-user-shield"></i> Admin</label>
            </div>
            <div class="form-group">
                <label id="loginLabel"><?php echo $role === 'admin' ? 'Username' : 'Email or Mobile Number'; ?></label>
                <input type="text" name="login_id" value="<?php echo e(post('login_id')); ?>" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="flex between mb-2">
                <span></span>
                <a href="forgot-password.php" class="small" id="forgotLink">Forgot password?</a>
            </div>
            <button class="btn btn-primary btn-block"><i class="fa-solid fa-right-to-bracket"></i> Login</button>
        </form>

        <p class="text-center muted small mt-2" id="signupHint">
            New here? <a href="signup.php">Create a customer account</a> &middot; <a href="seller/seller-signup.php">Become a seller</a>
        </p>
    </div>
</div>
<script>
document.querySelectorAll('input[name=user_role]').forEach(function (r) {
    r.addEventListener('change', function () {
        var admin = this.value === 'admin';
        document.getElementById('loginLabel').textContent = admin ? 'Username' : 'Email or Mobile Number';
        document.getElementById('forgotLink').href = 'forgot-password.php?role=' + this.value;
    });
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
