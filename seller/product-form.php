<?php
/* Shared form for add-product.php / edit-product.php. Expects $prod (array) and $categories. */
if (basename($_SERVER['PHP_SELF']) === 'product-form.php') { header('Location: dashboard.php'); exit; }
?>
<form method="post" enctype="multipart/form-data" class="card" style="max-width:900px">
    <?php echo csrf_field(); ?>
    <div class="form-row">
        <div class="form-group"><label>Product Name *</label><input type="text" name="name" maxlength="200" value="<?php echo e($prod['ProductName']); ?>" required></div>
        <div class="form-group"><label>Brand</label><input type="text" name="brand" maxlength="100" value="<?php echo e($prod['Brand']); ?>"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Category *</label>
            <select name="category" required>
                <option value="">Select category</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?php echo (int)$c['ID']; ?>" <?php echo (int)$prod['CategoryID'] === (int)$c['ID'] ? 'selected' : ''; ?>><?php echo e($c['CategoryName']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group"><label>Status</label>
            <select name="status">
                <option <?php echo $prod['Status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                <option <?php echo $prod['Status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
    </div>
    <div class="form-row-3">
        <div class="form-group"><label>Selling Price (₹) *</label><input type="number" step="0.01" min="1" name="price" value="<?php echo e($prod['Price']); ?>" required></div>
        <div class="form-group"><label>MRP (₹) *</label><input type="number" step="0.01" min="1" name="mrp" value="<?php echo e($prod['MRP']); ?>" required></div>
        <div class="form-group"><label>Stock Quantity *</label><input type="number" min="0" name="stock" value="<?php echo e($prod['Stock']); ?>" required></div>
    </div>
    <div class="form-group"><label>Description</label><textarea name="description" style="min-height:140px"><?php echo e($prod['Description']); ?></textarea></div>
    <div class="form-row">
        <div class="form-group">
            <label>Product Image <?php echo empty($prod['ID']) ? '*' : '(leave empty to keep current)'; ?></label>
            <input type="file" name="image" accept="image/*" <?php echo empty($prod['ID']) ? 'required' : ''; ?>>
            <span class="muted small">JPG, PNG, GIF or WEBP — max 2 MB. Square images look best.</span>
        </div>
        <?php if (!empty($prod['Image'])): ?>
            <div><label>Current Image</label><img src="<?php echo product_img($prod['Image']); ?>" style="width:110px;height:110px;object-fit:cover;border-radius:12px;background:#fff"></div>
        <?php endif; ?>
    </div>
    <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?php echo empty($prod['ID']) ? 'Add Product' : 'Save Changes'; ?></button>
    <a href="manage-products.php" class="btn btn-outline">Cancel</a>
</form>
