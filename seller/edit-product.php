<?php
require __DIR__ . '/../includes/init.php';
$sid = require_seller();
$id = (int)get('id');
$prod = db_one("SELECT * FROM tblproduct WHERE ID=? AND SellerID=?", [$id, $sid]);
if (!$prod) {
    flash('Product not found.', 'error');
    redirect('seller/manage-products.php');
}
$categories = db_all("SELECT ID, CategoryName FROM tblcategory ORDER BY CategoryName");
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    list($d, $errors) = product_from_post();
    $prod = array_merge($prod, $d);
    $img = null;
    if (!$errors) {
        list($img, $err) = upload_image('image', PRODUCT_IMG_DIR);
        if ($err) $errors[] = $err;
    }
    if (!$errors) {
        db_query("UPDATE tblproduct SET CategoryID=?, ProductName=?, Brand=?, Description=?, Price=?, MRP=?, Stock=?, Status=? WHERE ID=? AND SellerID=?",
            [$d['CategoryID'], $d['ProductName'], $d['Brand'], $d['Description'], (float)$d['Price'], (float)$d['MRP'], (int)$d['Stock'], $d['Status'], $id, $sid]);
        if ($img) {
            $old = db_val("SELECT Image FROM tblproduct WHERE ID=?", [$id]);
            db_query("UPDATE tblproduct SET Image=? WHERE ID=?", [$img, $id]);
            if ($old && is_file(PRODUCT_IMG_DIR . $old)) @unlink(PRODUCT_IMG_DIR . $old);
        }
        flash('Product updated successfully.');
        redirect('seller/manage-products.php');
    }
}

$panelRole = 'seller';
$pageTitle = 'Edit Product';
include __DIR__ . '/../includes/panel-header.php';
foreach ($errors as $er) echo '<div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> ' . e($er) . '</div>';
include __DIR__ . '/product-form.php';
include __DIR__ . '/../includes/panel-footer.php';
