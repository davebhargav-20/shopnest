<?php
/* Shared change-password page body. Set $cpTable and $cpId before including (after the auth check). */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $cur = isset($_POST['current']) ? (string)$_POST['current'] : '';
    $new = isset($_POST['newpass']) ? (string)$_POST['newpass'] : '';
    $conf = isset($_POST['confirm']) ? (string)$_POST['confirm'] : '';
    $hash = db_val("SELECT Password FROM $cpTable WHERE ID=?", [$cpId]);
    if (!password_verify($cur, $hash)) {
        flash('Your current password is incorrect.', 'error');
    } elseif (strlen($new) < 6) {
        flash('New password must be at least 6 characters.', 'error');
    } elseif ($new !== $conf) {
        flash('New password and confirm password do not match.', 'error');
    } else {
        db_query("UPDATE $cpTable SET Password=? WHERE ID=?", [password_hash($new, PASSWORD_DEFAULT), $cpId]);
        flash('Your password has been changed successfully.');
    }
    redirect(basename(dirname($_SERVER['PHP_SELF'])) . '/change-password.php');
}
$pageTitle = 'Change Password';
include __DIR__ . '/panel-header.php';
?>
<div class="card" style="max-width:520px">
    <h3>Change Password</h3>
    <form method="post">
        <?php echo csrf_field(); ?>
        <div class="form-group"><label>Current Password</label><input type="password" name="current" required></div>
        <div class="form-group"><label>New Password</label><input type="password" name="newpass" minlength="6" required></div>
        <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm" minlength="6" required></div>
        <button class="btn btn-primary"><i class="fa-solid fa-key"></i> Update Password</button>
    </form>
</div>
<?php include __DIR__ . '/panel-footer.php'; ?>
