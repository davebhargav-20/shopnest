<?php
/* ------------------------------------------------------------
 * Shared order details view.
 * Expects: $order (tblorders row), $items (tblorderitems rows + Image),
 *          $mode = 'customer' | 'seller' | 'admin'
 * Item status forms POST: action=status, item_id, status, remark
 * Customer cancel forms POST: action=cancel, item_id (or all=1)
 * ------------------------------------------------------------ */
$overall = order_overall_status($order['ID']);
$itemsTotal = 0;
foreach ($items as $it) $itemsTotal += $it['Price'] * $it['Quantity'];
$hasPending = false;
foreach ($items as $it) if ($it['Status'] === 'Pending') $hasPending = true;
$prefix = $mode === 'customer' ? '../' : '../';
?>
<div>
    <div>
        <div class="card">
            <div class="card-title">
                <span>Order <span class="gradient-text"><?php echo e($order['OrderNumber']); ?></span></span>
                <?php echo status_badge($overall); ?>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th><th>Status</th><th><?php echo $mode === 'customer' ? '' : 'Update'; ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td>
                                <div class="flex" style="flex-wrap:nowrap;min-width:220px">
                                    <img class="thumb" src="<?php echo product_img(isset($it['Image']) ? $it['Image'] : ''); ?>" alt="">
                                    <div>
                                        <?php if ($it['ProductID']): ?>
                                            <a href="<?php echo url('product-details.php?id=' . (int)$it['ProductID']); ?>" style="color:var(--text);font-weight:600" target="_blank"><?php echo e($it['ProductName']); ?></a>
                                        <?php else: ?><b><?php echo e($it['ProductName']); ?></b><?php endif; ?>
                                        <?php if ($mode === 'admin'): ?><div class="muted small"><i class="fa-solid fa-store"></i> <?php echo e($it['ShopName']); ?></div><?php endif; ?>
                                        <?php if (!empty($it['Remark'])): ?><div class="muted small"><i class="fa-regular fa-comment"></i> <?php echo e($it['Remark']); ?></div><?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo money($it['Price']); ?></td>
                            <td><?php echo (int)$it['Quantity']; ?></td>
                            <td><?php echo money($it['Price'] * $it['Quantity']); ?></td>
                            <td><?php echo status_badge($it['Status']); ?></td>
                            <td>
                                <?php if ($mode === 'customer'): ?>
                                    <?php if ($it['Status'] === 'Pending'): ?>
                                        <form method="post" data-confirm="Cancel this item?">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="item_id" value="<?php echo (int)$it['ID']; ?>">
                                            <button class="btn btn-danger btn-sm">Cancel</button>
                                        </form>
                                    <?php elseif ($it['Status'] === 'Delivered' && $it['ProductID']): ?>
                                        <a class="btn btn-sm btn-outline" href="<?php echo url('product-details.php?id=' . (int)$it['ProductID'] . '#reviews'); ?>"><i class="fa-solid fa-star"></i> Review</a>
                                    <?php endif; ?>
                                <?php elseif (!in_array($it['Status'], ['Delivered', 'Cancelled'], true)): ?>
                                    <form method="post" class="inline-form" style="min-width:170px">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="status">
                                        <input type="hidden" name="item_id" value="<?php echo (int)$it['ID']; ?>">
                                        <select name="status">
                                            <?php foreach (order_statuses() as $s): ?>
                                                <option <?php echo $s === $it['Status'] ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" name="remark" placeholder="Remark (optional)" maxlength="250" style="width:150px">
                                        <button class="btn btn-sm btn-primary">Save</button>
                                    </form>
                                <?php else: ?>
                                    <span class="muted small">Final</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($mode === 'customer' && $hasPending): ?>
                <form method="post" class="mt-2" data-confirm="Cancel all pending items in this order?">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="all" value="1">
                    <button class="btn btn-danger"><i class="fa-solid fa-ban"></i> Cancel Order</button>
                    <span class="muted small">Items can be cancelled until the seller confirms them.</span>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($mode !== 'seller'): ?>
        <div class="card">
            <h3>Order Tracking</h3>
            <?php
            $steps = ['Pending' => 'Order Placed', 'Confirmed' => 'Confirmed by Seller', 'Shipped' => 'Shipped', 'Delivered' => 'Delivered'];
            $rank = ['Pending' => 0, 'Confirmed' => 1, 'Shipped' => 2, 'Delivered' => 3, 'Partially Delivered' => 3, 'Processing' => 1, 'Cancelled' => -1];
            $cur = isset($rank[$overall]) ? $rank[$overall] : 0;
            ?>
            <?php if ($overall === 'Cancelled'): ?>
                <div class="alert alert-error mb-0"><i class="fa-solid fa-ban"></i> This order has been cancelled.</div>
            <?php else: ?>
                <div class="flex" style="justify-content:space-between;gap:.5rem">
                    <?php $n = 0; foreach ($steps as $k => $label): $done = $n <= $cur; ?>
                        <div class="text-center" style="flex:1;min-width:90px">
                            <div style="width:42px;height:42px;border-radius:50%;margin:0 auto .5rem;display:grid;place-items:center;background:<?php echo $done ? 'linear-gradient(135deg,var(--blue),var(--cyan))' : 'rgba(255,255,255,.06)'; ?>">
                                <i class="fa-solid <?php echo ['fa-receipt', 'fa-check', 'fa-truck', 'fa-house'][$n]; ?>"></i>
                            </div>
                            <div class="small <?php echo $done ? '' : 'muted'; ?>"><?php echo $label; ?></div>
                        </div>
                    <?php $n++; endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="grid-2 mt-3">
        <div class="card" style="margin-top:0">
            <h3>Summary</h3>
            <dl class="detail-list" style="grid-template-columns:110px 1fr">
                <dt>Order Date</dt><dd><?php echo date('d M Y, h:i A', strtotime($order['OrderDate'])); ?></dd>
                <dt>Payment</dt><dd><?php echo e($order['PaymentMethod']); ?> <?php echo status_badge($order['PaymentStatus']); ?></dd>
                <?php if ($order['Notes']): ?><dt>Notes</dt><dd><?php echo e($order['Notes']); ?></dd><?php endif; ?>
            </dl>
            <?php if ($mode === 'seller'): ?>
                <div class="summary-row total"><span>Your items</span><span><?php echo money($itemsTotal); ?></span></div>
            <?php else: ?>
                <div class="summary-row mt-2"><span class="muted">Subtotal</span><span><?php echo money($order['SubTotal']); ?></span></div>
                <div class="summary-row"><span class="muted">Shipping</span><span><?php echo $order['ShippingCharge'] > 0 ? money($order['ShippingCharge']) : 'FREE'; ?></span></div>
                <div class="summary-row total"><span>Total</span><span><?php echo money($order['TotalAmount']); ?></span></div>
            <?php endif; ?>
        </div>
        <div class="card" style="margin-top:0">
            <h3>Delivery Address</h3>
            <p class="mb-0"><b><?php echo e($order['ShipName']); ?></b><br>
                <span class="muted"><?php echo nl2br(e($order['ShipAddress'])); ?><br>
                <?php echo e($order['ShipCity']); ?>, <?php echo e($order['ShipState']); ?> - <?php echo e($order['ShipPincode']); ?><br>
                <i class="fa-solid fa-phone"></i> <?php echo e($order['ShipMobile']); ?></span></p>
        </div>
    </div>
</div>
