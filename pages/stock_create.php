<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

// Fetch categories for dropdown
$cats = $conn->query("SELECT * FROM stock_categories ORDER BY name");

// Fetch strains for dropdown
$strains = $conn->query("SELECT id, strain_code FROM strains ORDER BY strain_code");

ob_start();
?>

<div class="page-title">
    <div>
        <h2>Create Stock Item</h2>
        <p>Add a new product to inventory.</p>
    </div>
</div>

<div class="card">
    <form method="POST" action="/hvf-app/api/stock_create.php" class="form-grid">

        <div class="form-field">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required>
                <?php while ($c = $cats->fetch_assoc()): ?>
                    <option value="<?= h($c['id']) ?>"><?= h($c['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="strain_code">Strain Code</label>
            <select id="strain_code" name="strain_id" required>
                <option value="">Select Strain Code</option>
                <?php while ($s = $strains->fetch_assoc()): ?>
                    <option value="<?= h($s['id']) ?>"><?= h($s['strain_code']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="grow_type">Grow Type</label>
            <select id="grow_type" name="grow_type" required>
                <option value="">Select Grow Type</option>
                <option value="Indoor">Indoor</option>
                <option value="Greenhouse">Greenhouse</option>
                <option value="Outdoor">Outdoor</option>
            </select>
        </div>

        <div class="form-field">
            <label for="product_name">Product Name</label>
            <input type="text" id="product_name" name="product_name" readonly>
        </div>

        <div class="form-field">
            <label for="quantity">Quantity (grams)</label>
            <input type="number" id="quantity" step="0.01" name="quantity" required>
        </div>

        <div class="form-field">
            <label for="selling_price">Selling Price (R)</label>
            <input type="number" id="selling_price" step="0.01" name="selling_price" readonly>
        </div>

        <button type="submit">Create Stock</button>
    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
?>
