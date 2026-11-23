<?php
require __DIR__ . '/../includes/init.php';
$sid = require_seller();
$categories = db_all("SELECT ID, CategoryName FROM tblcategory ORDER BY CategoryName");
$prod = ['ID' => 0, 'ProductName' => '', 'Brand' => '', 'CategoryID' => 0, 'Status' => 'Active', 'Price' => '', 'MRP' => '', 'Stock' => '', 'Description' => '', 'Image' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    list($d, $errors) = product_from_post();
    $prod = array_merge($prod, $d);
    if (!$errors) {
        list($img, $err) = upload_image('image', PRODUCT_IMG_DIR, true);
        if ($err) $errors[] = $err;
    }
    if (!$errors) {
        db_query("INSERT INTO tblproduct (SellerID, CategoryID, ProductName, Brand, Description, Price, MRP, Stock, Image, Status)
                  VALUES (?,?,?,?,?,?,?,?,?,?)",
            [$sid, $d['CategoryID'], $d['ProductName'], $d['Brand'], $d['Description'], (float)$d['Price'], (float)$d['MRP'], (int)$d['Stock'], $img, $d['Status']]);
        flash('Product "' . $d['ProductName'] . '" added successfully.');
        redirect('seller/manage-products.php');
    }
}

$panelRole = 'seller';
$pageTitle = 'Add Product';
include __DIR__ . '/../includes/panel-header.php';
foreach ($errors as $er) echo '<div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> ' . e($er) . '</div>';
include __DIR__ . '/product-form.php';
include __DIR__ . '/../includes/panel-footer.php';
