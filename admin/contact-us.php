<?php
require __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    db_query("UPDATE tblpage SET PageTitle=?, PageDescription=?, Email=?, MobileNumber=? WHERE PageType='contactus'",
        [post('title'), post('description'), post('email'), preg_replace('/\D/', '', post('mobile'))]);
    flash('Contact Us page updated.');
    redirect('admin/contact-us.php');
}
$page = get_page('contactus');
$panelRole = 'admin';
$pageTitle = 'Contact Us Page';
include __DIR__ . '/../includes/panel-header.php';
?>
<div class="card" style="max-width:860px">
    <form method="post">
        <?php echo csrf_field(); ?>
        <div class="form-group"><label>Page Title</label><input type="text" name="title" value="<?php echo e($page['PageTitle']); ?>" required></div>
        <div class="form-group"><label>Address</label><textarea name="description" required><?php echo e($page['PageDescription']); ?></textarea></div>
        <div class="form-row">
            <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo e($page['Email']); ?>" required></div>
            <div class="form-group"><label>Phone Number</label><input type="text" name="mobile" value="<?php echo e($page['MobileNumber']); ?>" required></div>
        </div>
        <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Update</button>
        <a href="../contact.php" target="_blank" class="btn btn-outline">View Page</a>
    </form>
</div>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
