<?php
require __DIR__ . '/includes/init.php';
$page = get_page('contactus');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = post('name');
    $email = post('email');
    $mobile = post('mobile');
    $subject = post('subject');
    $message = post('message');
    if ($name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
    if ($message === '') $errors[] = 'Message is required.';
    if (!$errors) {
        db_query("INSERT INTO tblenquiry (Name, Email, MobileNumber, Subject, Message) VALUES (?,?,?,?,?)",
            [$name, $email, $mobile, $subject, $message]);
        flash('Thanks for contacting us! We will get back to you soon.');
        redirect('contact.php');
    }
}

$pageTitle = 'Contact Us';
include __DIR__ . '/includes/header.php';
?>
<section class="page-banner">
    <div class="container"><span class="eyebrow">Get in touch</span><h1><?php echo e($page ? $page['PageTitle'] : 'Contact Us'); ?></h1></div>
</section>
<div class="container section">
    <div class="grid-2-1" style="grid-template-columns:1fr 1.4fr">
        <div class="card">
            <div class="feature mb-2"><i class="fa-solid fa-location-dot"></i><div><b>Address</b><p class="muted small mb-0"><?php echo e($page['PageDescription']); ?></p></div></div>
            <div class="feature mb-2"><i class="fa-solid fa-envelope"></i><div><b>Email</b><p class="muted small mb-0"><?php echo e($page['Email']); ?></p></div></div>
            <div class="feature"><i class="fa-solid fa-phone"></i><div><b>Phone</b><p class="muted small mb-0"><?php echo e($page['MobileNumber']); ?></p></div></div>
        </div>
        <div class="card" style="margin-top:0">
            <h3>Send us a message</h3>
            <?php foreach ($errors as $er) echo '<div class="alert alert-error">' . e($er) . '</div>'; ?>
            <form method="post">
                <?php echo csrf_field(); ?>
                <div class="form-row">
                    <div class="form-group"><label>Name</label><input type="text" name="name" value="<?php echo e(post('name', isset($_SESSION['fname']) ? $_SESSION['fname'] : '')); ?>" required></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo e(post('email')); ?>" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Mobile</label><input type="tel" name="mobile" value="<?php echo e(post('mobile')); ?>"></div>
                    <div class="form-group"><label>Subject</label><input type="text" name="subject" value="<?php echo e(post('subject')); ?>"></div>
                </div>
                <div class="form-group"><label>Message</label><textarea name="message" required><?php echo e(post('message')); ?></textarea></div>
                <button class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
