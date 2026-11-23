<?php
require __DIR__ . '/includes/init.php';
$page = get_page('aboutus');
$pageTitle = 'About Us';
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
    <div class="container">
        <span class="eyebrow">Who we are</span>
        <h1><?php echo e($page ? $page['PageTitle'] : 'About Us'); ?></h1>
    </div>
</section>
<div class="container section">
    <div class="card" style="max-width:860px;margin:0 auto">
        <p style="font-size:1.05rem"><?php echo nl2br(e($page ? $page['PageDescription'] : '')); ?></p>
    </div>
    <div class="info-grid mt-3">
        <div class="card feature" style="margin-top:0"><i class="fa-solid fa-store"></i><div><b>For Sellers</b><p class="muted small mb-0">Register your shop, list products and manage orders from your own seller dashboard.</p></div></div>
        <div class="card feature" style="margin-top:0"><i class="fa-solid fa-bag-shopping"></i><div><b>For Customers</b><p class="muted small mb-0">Browse, compare, add to cart and track every order from your account.</p></div></div>
        <div class="card feature" style="margin-top:0"><i class="fa-solid fa-user-shield"></i><div><b>Verified by Admin</b><p class="muted small mb-0">Every seller is approved by our admin team before they can sell.</p></div></div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
