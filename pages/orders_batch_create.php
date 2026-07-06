<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

ob_start();

$clients = $conn->query("SELECT * FROM clients ORDER BY client_name ASC");
$stockItems = $conn->query("\n    SELECT s.id, s.product_name, s.strain_code, s.quantity, s.selling_price, s.category_id AS category_name\n    FROM stock s\n    ORDER BY s.strain_code, s.product_name\n");

// Convert stock items to array for JavaScript
$stockArray = [];
while ($s = $stockItems->fetch_assoc()) {
    $stockArray[] = $s;
}
?>

<div class="page-title">
    <div>
        <h2>Create Batch Order</h2>
        <p>Add multiple items to one client at once</p>
    </div>
    <a class="button" href="/hvf-app/pages/orders.php">Back to Orders</a>
</div>

<div class="card">
    <h3>Batch Order Creation</h3>
    <form id="batchOrderForm" method="POST" action="/hvf-app/api/orders_batch_create.php">
        <div class="form-row">
            <div>
                <label for="client_id">Select Client *</label>
                <select id="client_id" name="client_id" required>
                    <option value="">-- Select Client --</option>
                    <?php 
                    $clients->data_seek(0);
                    while ($c = $clients->fetch_assoc()): 
                    ?>
                        <option value="<?= h($c['id']) ?>"><?= h($c['client_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label for="order_date">Order Date *</label>
                <input type="date" id="order_date" name="order_date" value="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <div id="lineItemsContainer" style="margin-top: 20px;">
            <h4>Order Items</h4>
            <table id="lineItemsTable" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #ccc;">
                        <th style="text-align: left; padding: 8px;">Stock Item</th>
                        <th style="width: 120px; text-align: center;">Quantity (g)</th>
                        <th style="width: 120px; text-align: center;">Price/g</th>
                        <th style="width: 100px; text-align: center;">Total</th>
                        <th style="width: 80px; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody id="lineItemsList">
                </tbody>
                <tfoot>
                    <tr style="border-top: 2px solid #ccc; font-weight: bold;">
                        <td colspan="3" style="text-align: right; padding: 8px;">Grand Total:</td>
                        <td style="text-align: center; padding: 8px;" id="grandTotal">R 0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div style="margin-top: 15px;">
            <button type="button" class="button" onclick="addLineItem()" style="background-color: #4CAF50;">+ Add Item</button>
        </div>

        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <button type="submit" class="button">Create Batch Order</button>
            <a href="/hvf-app/pages/orders.php" class="button" style="background-color: #999;">Cancel</a>
        </div>
    </form>
</div>

<script>
const stockData = <?= json_encode($stockArray) ?>;
let lineItemCount = 0;

function addLineItem() {
    const container = document.getElementById('lineItemsList');
    const rowId = lineItemCount++;
    
    const row = document.createElement('tr');
    row.id = 'lineItem_' + rowId;
    row.style.borderBottom = '1px solid #eee';
    
    let stockOptions = '<option value="">-- Select Stock --</option>';
    stockData.forEach(s => {
        stockOptions += `<option value="${s.id}" data-price="${s.selling_price}" data-available="${s.quantity}">
            ${s.strain_code} - ${s.product_name} (${s.category_name || 'Unassigned'}, ${s.quantity}g available)
        </option>`;
    });
    
    row.innerHTML = `
        <td style="padding: 8px;">
            <select class="stock-select" name="stock_id[]" onchange="updateLineItemPrice(${rowId})" required>
                ${stockOptions}
            </select>
        </td>
        <td style="padding: 8px;">
            <input type="number" step="0.01" class="quantity-input" name="quantity[]" 
                   placeholder="0.00" required onchange="updateLineTotal(${rowId})" 
                   min="0" style="width: 100%; box-sizing: border-box;">
        </td>
        <td style="padding: 8px;">
            <input type="number" step="0.01" class="price-input" name="price[]" 
                   placeholder="0.00" required onchange="updateLineTotal(${rowId})" 
                   min="0" style="width: 100%; box-sizing: border-box;">
        </td>
        <td style="padding: 8px; text-align: center;" class="line-total">R 0.00</td>
        <td style="padding: 8px; text-align: center;">
            <button type="button" class="button" onclick="removeLineItem(${rowId})" 
                    style="background-color: #f44336; padding: 5px 10px; font-size: 12px;">Remove</button>
        </td>
    `;
    
    container.appendChild(row);
}

function updateLineItemPrice(rowId) {
    const row = document.getElementById('lineItem_' + rowId);
    const stockSelect = row.querySelector('.stock-select');
    const priceInput = row.querySelector('.price-input');
    
    const selectedOption = stockSelect.options[stockSelect.selectedIndex];
    if (selectedOption && selectedOption.dataset.price) {
        priceInput.value = selectedOption.dataset.price;
        updateLineTotal(rowId);
    }
}

function updateLineTotal(rowId) {
    const row = document.getElementById('lineItem_' + rowId);
    const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
    const price = parseFloat(row.querySelector('.price-input').value) || 0;
    const total = quantity * price;
    
    row.querySelector('.line-total').textContent = 'R ' + total.toFixed(2);
    updateGrandTotal();
}

function updateGrandTotal() {
    let grandTotal = 0;
    document.querySelectorAll('#lineItemsList tr').forEach(row => {
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        grandTotal += quantity * price;
    });
    
    document.getElementById('grandTotal').textContent = 'R ' + grandTotal.toFixed(2);
}

function removeLineItem(rowId) {
    const row = document.getElementById('lineItem_' + rowId);
    if (row) {
        row.remove();
        updateGrandTotal();
    }
}

document.getElementById('batchOrderForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const clientId = document.getElementById('client_id').value;
    if (!clientId) {
        alert('Please select a client');
        return;
    }
    
    const lineItems = document.querySelectorAll('#lineItemsList tr');
    if (lineItems.length === 0) {
        alert('Please add at least one item');
        return;
    }
    
    let hasError = false;
    lineItems.forEach((row, index) => {
        const stockId = row.querySelector('.stock-select').value;
        const quantity = parseFloat(row.querySelector('.quantity-input').value);
        const price = parseFloat(row.querySelector('.price-input').value);
        
        if (!stockId || !quantity || quantity <= 0 || !price || price < 0) {
            alert(`Please complete all fields for item ${index + 1}`);
            hasError = true;
        }
    });
    
    if (hasError) return;
    
    this.submit();
});

// Add first line item on page load
window.addEventListener('load', function() {
    addLineItem();
});
</script>

<style>
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-row > div {
    display: flex;
    flex-direction: column;
}

.form-row label {
    font-weight: bold;
    margin-bottom: 5px;
}

.form-row input,
.form-row select {
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
}

#lineItemsTable input,
#lineItemsTable select {
    padding: 6px;
    border: 1px solid #ccc;
    border-radius: 3px;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
?>
