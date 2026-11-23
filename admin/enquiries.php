<?php
require __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)post('id');
    if (post('action') === 'read') {
        db_query("UPDATE tblenquiry SET IsRead=1 WHERE ID=?", [$id]);
        flash('Marked as read.');
    } elseif (post('action') === 'delete') {
        db_query("DELETE FROM tblenquiry WHERE ID=?", [$id]);
        flash('Enquiry deleted.');
    }
    redirect('admin/enquiries.php');
}

$rows = db_all("SELECT * FROM tblenquiry ORDER BY IsRead, EnquiryDate DESC");
$panelRole = 'admin';
$pageTitle = 'Contact Enquiries';
include __DIR__ . '/../includes/panel-header.php';
?>
<?php if (!$rows): ?>
    <div class="card empty"><i class="fa-regular fa-envelope-open"></i>No enquiries yet.</div>
<?php endif; ?>
<?php foreach ($rows as $r): ?>
    <div class="card" style="<?php echo $r['IsRead'] ? 'opacity:.75' : 'border-color:rgba(6,182,212,.4)'; ?>">
        <div class="card-title">
            <span><?php echo e($r['Subject'] ?: '(No subject)'); ?> <?php echo $r['IsRead'] ? '' : '<span class="badge badge-blue">New</span>'; ?></span>
            <span class="muted small"><?php echo date('d M Y, h:i A', strtotime($r['EnquiryDate'])); ?></span>
        </div>
        <p class="muted small mb-1"><i class="fa-solid fa-user"></i> <?php echo e($r['Name']); ?> &middot; <a href="mailto:<?php echo e($r['Email']); ?>"><?php echo e($r['Email']); ?></a> <?php echo $r['MobileNumber'] ? '&middot; ' . e($r['MobileNumber']) : ''; ?></p>
        <p><?php echo nl2br(e($r['Message'])); ?></p>
        <div class="flex">
            <a class="btn btn-sm btn-outline" href="mailto:<?php echo e($r['Email']); ?>?subject=<?php echo rawurlencode('Re: ' . $r['Subject']); ?>"><i class="fa-solid fa-reply"></i> Reply by email</a>
            <?php if (!$r['IsRead']): ?>
                <form method="post"><?php echo csrf_field(); ?><input type="hidden" name="action" value="read"><input type="hidden" name="id" value="<?php echo (int)$r['ID']; ?>"><button class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i> Mark read</button></form>
            <?php endif; ?>
            <form method="post" data-confirm="Delete this enquiry?"><?php echo csrf_field(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)$r['ID']; ?>"><button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button></form>
        </div>
    </div>
<?php endforeach; ?>
<?php include __DIR__ . '/../includes/panel-footer.php'; ?>
