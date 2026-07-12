<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

ob_start();

$catsForForm = $conn->query("SELECT id, name FROM stock_categories ORDER BY name");
$stock = $conn->query("
    SELECT s.id, s.strain_code, s.product_name, s.grow_type, s.quantity, s.selling_price, s.category_id, s.category_id AS category_name
    FROM stock s
    ORDER BY s.strain_code, s.product_name
");

// Get all strains for form (including grow_type for proper matching)
$strainsForForm = $conn->query("SELECT id, strain_code, product_name, default_price, grow_type FROM strains ORDER BY strain_code, grow_type");
$strainsData = [];
while ($str = $strainsForForm->fetch_assoc()) {
    $strainsData[] = $str;
}
?>

<div class="page-title">
    <div>
        <h2>Stock Management</h2>
        <p>Add and manage your stock inventory.</p>
    </div>
</div>

<!-- ADD STOCK ITEM -->
<div class="card">
    <h3>Add Stock Item</h3>
    <form method="POST" action="/hvf-app/api/stock_create.php" class="form-grid">

        <div class="form-field">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required>
                <option value="">Select Category</option>
                <?php 
                $catsForForm->data_seek(0);
                while ($c = $catsForForm->fetch_assoc()): ?>
                    <option value="<?= h($c['id']) ?>"><?= h($c['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="strain_code">Strain Code</label>
            <select id="strain_code" name="strain_id" required onchange="updateProductDetails()">
                <option value="">Select Strain Code</option>
                <?php
                // Show every strain variant so user can pick the exact strain+grow_type
                foreach ($strainsData as $s):
                    $display = h($s['strain_code']) . ' - ' . h($s['grow_type']);
                ?>
                    <option value="<?= h($s['id']) ?>" data-product="<?= h($s['product_name']) ?>" data-price="<?= h($s['default_price']) ?>" data-grow="<?= h($s['grow_type']) ?>"><?= $display ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="grow_type">Grow Type</label>
            <select id="grow_type" name="grow_type" required onchange="updateProductDetails()">
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
            <input type="number" id="quantity" step="0.01" name="quantity" placeholder="0.00" required>
        </div>

        <div class="form-field">
            <label for="selling_price">Selling Price (R)</label>
            <input type="number" id="selling_price" step="0.01" name="selling_price" readonly>
        </div>

        <button type="submit">Add Stock</button>
    </form>
</div>

<script>
function updateProductDetails() {
    const strainSelect = document.getElementById('strain_code');
    const selected = strainSelect.options[strainSelect.selectedIndex];
    const productInput = document.getElementById('product_name');
    const priceInput = document.getElementById('selling_price');
    const growSelect = document.getElementById('grow_type');

    if (selected && selected.value) {
        const product = selected.getAttribute('data-product');
        const price = selected.getAttribute('data-price');
        const grow = selected.getAttribute('data-grow');

        productInput.value = product || '';
        priceInput.value = price || '';
        if (grow) {
            growSelect.value = grow;
            growSelect.disabled = true; // lock grow type to strain variant
        } else {
            growSelect.disabled = false;
        }
    } else {
        productInput.value = '';
        priceInput.value = '';
        growSelect.disabled = false;
    }
}

// Make sure grow select is enabled on load
window.addEventListener('load', function() {
    const growSelect = document.getElementById('grow_type');
    if (growSelect) growSelect.disabled = false;
});
</script>

<div class="card">
    <h3>Stock List</h3>
    <table>
        <tr><th>Strain Code</th><th>Product Name</th><th>Grow Type</th><th>Quantity (g)</th><th>Category</th><th>Price/g</th><th>Actions</th></tr>
        <?php while ($s = $stock->fetch_assoc()): ?>
            <tr>
                <td><?= h($s['strain_code']) ?></td>
                <td><?= h($s['product_name']) ?></td>
                <td><?= h($s['grow_type']) ?></td>
                <td><?= number_format((float)$s['quantity'], 2) ?></td>
                <td><?= h($s['category_name'] ?? 'Unassigned') ?></td>
                <td><?= money($s['selling_price']) ?></td>
                <td class="actions">
                    <a href="/hvf-app/pages/stock_edit.php?id=<?= h($s['id']) ?>">Edit</a>
                    <a href="/hvf-app/api/stock_delete.php?id=<?= h($s['id']) ?>" onclick="return confirm('Delete this stock?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
?>
