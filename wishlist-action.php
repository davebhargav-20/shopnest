<?php
/* Add / remove a product from the logged-in customer's wishlist */
require __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
csrf_check();
if (!is_customer()) {
    flash('Please login as a customer to use your wishlist.', 'info');
    redirect('login.php?role=customer');
}
$cid = (int)$_SESSION['sncid'];
$pid = (int)post('pid');

if (post('action') === 'remove') {
    db_query("DELETE FROM tblwishlist WHERE CustomerID=? AND ProductID=?", [$cid, $pid]);
    flash('Removed from your wishlist.');
} elseif (db_val("SELECT COUNT(*) FROM tblproduct WHERE ID=? AND Status='Active'", [$pid])) {
    db_query("INSERT IGNORE INTO tblwishlist (CustomerID, ProductID) VALUES (?,?)", [$cid, $pid]);
    flash('Added to your wishlist.');
}

back_or('customer/wishlist.php');
