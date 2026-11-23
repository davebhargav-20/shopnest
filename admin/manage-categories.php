<?php
require __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');
    $name = post('name');
    $icon = preg_match('/^fa-[a-z0-9-]+$/', post('icon')) ? post('icon') : 'fa-tag';
    $id = (int)post('id');

    if ($action === 'add' || $action === 'edit') {
        if ($name === '') {
            flash('Category name is required.', 'error');
        } elseif (db_val("SELECT COUNT(*) FROM tblcategory WHERE CategoryName=? AND ID<>?", [$name, $id])) {
            flash('A category with this name already exists.', 'error');
        } elseif ($action === 'add') {
            db_query("INSERT INTO tblcategory (CategoryName, Description, Icon) VALUES (?,?,?)", [$name, post('description'), $icon]);
            flash('Category added.');
        } else {
            db_query("UPDATE tblcategory SET CategoryName=?, Description=?, Icon=? WHERE ID=?", [$name, post('description'), $icon, $id]);
            flash('Category updated.');
        }
    } elseif ($action === 'delete') {
        if (db_val("SELECT COUNT(*) FROM tblproduct WHERE CategoryID=?", [$id])) {
            flash('This category has products. Move or delete those products first.', 'error');
        } else {
            db_query("DELETE FROM tblcategory WHERE ID=?", [$id]);
            flash('Category deleted.');
        }
    }
    redirect('admin/manage-categories.php');
}

$edit = get('edit') ? db_one("SELECT * FROM tblcategory WHERE ID=?", [(int)get('edit')]) : null;
$cats = db_all("SELECT c.*, COUNT(p.ID) Products FROM tblcategory c LEFT JOIN tblproduct p ON p.CategoryID=c.ID GROUP BY c.ID ORDER BY c.CategoryName");

$panelRole = 'admin';
$pageTitle = 'Manage Categories';
include __DIR__ . '/../includes/panel-header.php';
?>
<div class="grid-2-1" style="grid-template-columns:1fr 2fr">
    <div class="card" style="margin-top:0">
        <h3><?php echo $edit ? 'Edit Category' : 'Add Category'; ?></h3>
        <form method="post">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="<?php echo $edit ? 'edit' : 'add'; ?>">
            <input type="hidden" name="id" value="<?php echo $edit ? (int)$edit['ID'] : 0; ?>">
            <div class="form-group"><label>Category Name</label><input type="text" name="name" maxlength="100" value="<?php echo $edit ? e($edit['CategoryName']) : ''; ?>" required></div>
            <div class="form-group"><label>Description</label><input type="text" name="description" maxlength="255" value="<?php echo $edit ? e($edit['Description']) : ''; ?>"></div>
            <div class="form-group">
                <label>Icon (Font Awesome class)</label>
                <input type="text" name="icon" value="<?php echo $edit ? e($edit['Icon']) : 'fa-tag'; ?>" placeholder="fa-tag">
                <span class="muted small">e.g. fa-mobile-screen, fa-shirt, fa-book, fa-gamepad — see fontawesome.com/icons</span>
            </div>
            <button class="btn btn-primary"><?php echo $edit ? 'Update' : 'Add'; ?> Category</button>
            <?php if ($edit): ?><a href="manage-categories.php" class="btn btn-outline">Cancel</a><?php endif; ?>
        </form>
    </div>
    <div class="card" style="margin-top:0">
        <div class="card-title">All Categories <span class="muted small"><?php echo count($cats); ?></span></div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>#</th><th>Icon</th><th>Name</th><th>Description</th><th>Products</th><th>Created</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($cats as $n => $ct): ?>
                    <tr>
                        <td><?php echo $n + 1; ?></td>
                        <td><i class="fa-solid <?php echo e($ct['Icon']); ?>" style="color:var(--cyan);font-size:1.2rem"></i></td>
                        <td><b><?php echo e($ct['CategoryName']); ?></b></td>
                        <td class="muted small"><?php echo e($ct['Description']); ?></td>
                        <td><?php echo (int)$ct['Products']; ?></td>
                        <td class="small"><?php echo date('d M Y', strtotime($ct['CreationDate'])); ?></td>
                        <td>
                            <div class="flex" style="gap:.4rem;flex-wrap:nowrap">
                                <a href="?edit=<?php echo (int)$ct['ID']; ?>" class="btn btn-sm btn-outline"><i class="fa-solid fa-pen"></i></a>
                                <form method="post" data-confirm="Delete this category?">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)$ct['ID']; ?>">
                                    <button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
