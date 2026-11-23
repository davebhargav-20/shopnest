<?php
require __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    db_query("UPDATE tblpage SET PageTitle=?, PageDescription=? WHERE PageType='aboutus'", [post('title'), post('description')]);
    flash('About Us page updated.');
    redirect('admin/about-us.php');
}
$page = get_page('aboutus');
$panelRole = 'admin';
$pageTitle = 'About Us Page';
include __DIR__ . '/../includes/panel-header.php';
?>
<div class="card" style="max-width:860px">
    <form method="post">
        <?php echo csrf_field(); ?>
        <div class="form-group"><label>Page Title</label><input type="text" name="title" value="<?php echo e($page['PageTitle']); ?>" required></div>
        <div class="form-group"><label>Page Description</label><textarea name="description" style="min-height:240px" required><?php echo e($page['PageDescription']); ?></textarea></div>
        <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Update</button>
        <a href="../about.php" target="_blank" class="btn btn-outline">View Page</a>
    </form>
</div>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
