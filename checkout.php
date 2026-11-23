<?php
require __DIR__ . '/includes/init.php';
$cid = require_customer();

$items = cart_items();
$items = array_values(array_filter($items, function ($i) { return $i['Qty'] > 0; }));
if (!$items) {
    flash('Your cart is empty.', 'info');
    redirect('cart.php');
}
$t = cart_totals($items);
$me = db_one("SELECT * FROM tblcustomer WHERE ID=?", [$cid]);
$errors = [];

$f = [
    'name'    => post('name', $me['FullName']),
    'mobile'  => post('mobile', (string)$me['MobileNumber']),
    'address' => post('address', (string)$me['Address']),
    'city'    => post('city', (string)$me['City']),
    'state'   => post('state', (string)$me['State']),
    'pincode' => post('pincode', (string)$me['Pincode']),
    'payment' => post('payment', 'COD'),
    'notes'   => post('notes'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if ($f['name'] === '') $errors[] = 'Full name is required.';
    if (!preg_match('/^[0-9]{10}$/', $f['mobile'])) $errors[] = 'Enter a valid 10-digit mobile number.';
    if ($f['address'] === '') $errors[] = 'Address is required.';
    if ($f['city'] === '' || $f['state'] === '') $errors[] = 'City and state are required.';
    if (!preg_match('/^[0-9]{6}$/', $f['pincode'])) $errors[] = 'Enter a valid 6-digit pincode.';
    if (!in_array($f['payment'], ['COD', 'Online'], true)) $errors[] = 'Choose a payment method.';

    if (!$errors) {
        mysqli_begin_transaction($con);
        try {
            $sub = 0;
            $lines = [];
            foreach (cart_get() as $pid => $qty) {
                $row = db_one("SELECT p.ID, p.SellerID, p.ProductName, p.Price, p.Stock FROM tblproduct p
                               JOIN tblseller s ON s.ID=p.SellerID
                               WHERE p.ID=? AND p.Status='Active' AND s.Status='Active' FOR UPDATE", [(int)$pid]);
                if (!$row) continue;
                if ($row['Stock'] < $qty) {
                    throw new Exception('Only ' . $row['Stock'] . ' unit(s) of "' . $row['ProductName'] . '" are left. Please update your cart.');
                }
                $sub += $row['Price'] * $qty;
                $lines[] = [$row, (int)$qty];
            }
            if (!$lines) throw new Exception('Items in your cart are no longer available.');

            $ship = $sub > FREE_SHIPPING_ABOVE ? 0 : SHIPPING_CHARGE;
            $orderNo = new_order_number();
            $payStatus = $f['payment'] === 'Online' ? 'Paid' : 'Pending';   // Online = demo payment

            $ok = db_query("INSERT INTO tblorders (OrderNumber, CustomerID, ShipName, ShipMobile, ShipAddress, ShipCity, ShipState, ShipPincode,
                            PaymentMethod, PaymentStatus, SubTotal, ShippingCharge, TotalAmount, Notes)
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [$orderNo, $cid, $f['name'], $f['mobile'], $f['address'], $f['city'], $f['state'], $f['pincode'],
                 $f['payment'], $payStatus, (float)$sub, (float)$ship, (float)($sub + $ship), $f['notes']]);
            if (!$ok) throw new Exception('Could not place the order. Please try again.');
            $orderId = db_insert_id();

            foreach ($lines as $l) {
                list($row, $qty) = $l;
                db_query("INSERT INTO tblorderitems (OrderID, ProductID, SellerID, ProductName, Price, Quantity) VALUES (?,?,?,?,?,?)",
                    [$orderId, (int)$row['ID'], (int)$row['SellerID'], $row['ProductName'], (float)$row['Price'], $qty]);
                db_query("UPDATE tblproduct SET Stock = Stock - ? WHERE ID=?", [$qty, (int)$row['ID']]);
            }

            /* save address to profile if empty */
            if (!$me['Address']) {
                db_query("UPDATE tblcustomer SET Address=?, City=?, State=?, Pincode=? WHERE ID=?",
                    [$f['address'], $f['city'], $f['state'], $f['pincode'], $cid]);
            }

            mysqli_commit($con);
            $_SESSION['cart'] = [];
            redirect('order-success.php?no=' . urlencode($orderNo));
        } catch (Exception $ex) {
            mysqli_rollback($con);
            $errors[] = $ex->getMessage();
        }
    }
}

$pageTitle = 'Checkout';
include __DIR__ . '/includes/header.php';
?>

<div class="container section">
    <h1>Checkout</h1>
    <?php foreach ($errors as $er) echo '<div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> ' . e($er) . '</div>'; ?>

    <form method="post" class="cart-layout">
        <?php echo csrf_field(); ?>
        <div>
            <div class="card">
                <h3><i class="fa-solid fa-location-dot" style="color:var(--cyan)"></i> Delivery Address</h3>
                <div class="form-row">
                    <div class="form-group"><label>Full Name</label><input type="text" name="name" value="<?php echo e($f['name']); ?>" required></div>
                    <div class="form-group"><label>Mobile Number</label><input type="tel" name="mobile" pattern="[0-9]{10}" maxlength="10" value="<?php echo e($f['mobile']); ?>" required></div>
                </div>
                <div class="form-group"><label>Address (House no, Street, Area)</label><textarea name="address" required style="min-height:80px"><?php echo e($f['address']); ?></textarea></div>
                <div class="form-row-3">
                    <div class="form-group"><label>City</label><input type="text" name="city" value="<?php echo e($f['city']); ?>" required></div>
                    <div class="form-group"><label>State</label><input type="text" name="state" value="<?php echo e($f['state']); ?>" required></div>
                    <div class="form-group"><label>Pincode</label><input type="text" name="pincode" pattern="[0-9]{6}" maxlength="6" value="<?php echo e($f['pincode']); ?>" required></div>
                </div>
                <div class="form-group mb-0"><label>Order Notes (optional)</label><input type="text" name="notes" maxlength="250" value="<?php echo e($f['notes']); ?>" placeholder="e.g. Call before delivery"></div>
            </div>

            <div class="card">
                <h3><i class="fa-solid fa-wallet" style="color:var(--cyan)"></i> Payment Method</h3>
                <label class="pay-option"><input type="radio" name="payment" value="COD" <?php echo $f['payment'] === 'COD' ? 'checked' : ''; ?>> <i class="fa-solid fa-money-bill-wave"></i> Cash on Delivery</label>
                <label class="pay-option"><input type="radio" name="payment" value="Online" <?php echo $f['payment'] === 'Online' ? 'checked' : ''; ?>> <i class="fa-solid fa-credit-card"></i> Online Payment (UPI / Card) <span class="badge badge-grey">Demo</span></label>
            </div>
        </div>

        <div class="card" style="margin-top:0">
            <h3>Your Order</h3>
            <?php foreach ($items as $i): ?>
                <div class="summary-row small">
                    <span><?php echo e($i['ProductName']); ?> <span class="muted">× <?php echo (int)$i['Qty']; ?></span></span>
                    <span><?php echo money($i['LineTotal']); ?></span>
                </div>
            <?php endforeach; ?>
            <div class="summary-row" style="border-top:1px solid var(--border);margin-top:.5rem"><span class="muted">Subtotal</span><span><?php echo money($t['sub']); ?></span></div>
            <div class="summary-row"><span class="muted">Shipping</span><span><?php echo $t['ship'] ? money($t['ship']) : 'FREE'; ?></span></div>
            <div class="summary-row total"><span>Total</span><span><?php echo money($t['total']); ?></span></div>
            <button class="btn btn-primary btn-block mt-2"><i class="fa-solid fa-check"></i> Place Order</button>
            <a href="cart.php" class="btn btn-outline btn-block mt-1">Back to Cart</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
