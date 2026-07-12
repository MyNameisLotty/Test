<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

ob_start();

$clients = $conn->query("SELECT * FROM clients ORDER BY client_name ASC");
$stockItems = $conn->query("
    SELECT s.id, s.product_name, s.strain_code, s.quantity, s.selling_price, c.name AS category_name
    FROM stock s
    LEFT JOIN stock_categories c ON s.category_id = c.id
    ORDER BY s.strain_code, s.product_name
");

/* FIXED QUERY: We use SUBSTRING_INDEX to drop the trailing item suffix (-1, -2).
  This forces rows sharing the same timestamp batch code to combine as ONE order group!
*/
$orders = $conn->query("
    SELECT 
        o.id,
        o.client_id,
        o.order_date,
        o.description,
        o.quantity,
        o.total,
        o.status,
        SUBSTRING_INDEX(o.order_number, '-', 3) AS order_number, 
        c.client_name, 
        '—' AS strain_code, 
        o.description AS product_name, 
        o.total AS selling_price
    FROM orders o
    JOIN clients c ON o.client_id = c.id
    ORDER BY o.id DESC
");
?>

<style>
.actions {
    display: flex;
    gap: 8px;
    align-items: center;
}
.btn-action {
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
    transition: background 0.2s ease;
    text-align: center;
}
.btn-invoice { background: #4CAF50; color: white !important; }
.btn-invoice:hover { background: #439a46; }

.btn-edit { background: #2196F3; color: white !important; }
.btn-edit:hover { background: #0b7dda; }

.btn-delete { background: #f44336; color: white !important; }
.btn-delete:hover { background: #da190b; }

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

<div class="page-title">
    <div>
        <h2>Orders</h2>
        <p>Create orders, invoice them, and import sales from CSV.</p>
    </div>
    <a class="button" href="/hvf-app/pages/orders_import.php">Import Orders</a>
</div>

<div class="card">
    <h3>Create Orders</h3>
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
            <button type="submit" class="button">Create Orders</button>
        </div>
    </form>
</div>

<script>
<?php 
$stockItems->data_seek(0);
$stockArray = [];
while ($s = $stockItems->fetch_assoc()) {
    $stockArray[] = $s;
}
?>
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

window.addEventListener('load', function() {
    addLineItem();
});
</script>

<script>
function toggleDetails(button) {
    const row = button.closest('tr');
    const detailRow = row.nextElementSibling;
    
    if (detailRow && detailRow.classList.contains('order-details')) {
        if (detailRow.style.display === 'none') {
            detailRow.style.display = '';
            button.textContent = '▲';
        } else {
            detailRow.style.display = 'none';
            button.textContent = '▼';
        }
    }
}
</script>

<div class="card">
    <h3>Order List</h3>
    <table>
        <tr><th>Order</th><th>Client</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th>Actions</th></tr>
        <?php 
        $ordersByNumber = [];
        $orders->data_seek(0);
        while ($o = $orders->fetch_assoc()) {
            $orderNum = $o['order_number'];
            if (!isset($ordersByNumber[$orderNum])) {
                $ordersByNumber[$orderNum] = [];
            }
            $ordersByNumber[$orderNum][] = $o;
        }
        
        foreach ($ordersByNumber as $orderNum => $orderItems): 
            $firstItem = $orderItems[0];
            $itemCount = count($orderItems);
            $orderTotal = 0;
            foreach ($orderItems as $item) {
                $orderTotal += (float)$item['total'];
            }

            // DYNAMIC SHORTCODE GENERATOR BLOCK
            $cName = $firstItem['client_name'] ?? '';
            $cId = (int)($firstItem['client_id'] ?? 0);
            $oId = (int)($firstItem['id'] ?? 0);

            if (stripos($cName, 'high5') !== false) {
                $prefix = 'Hi5';
                $words = explode(' ', $cName);
                $lastWord = strtoupper(end($words));
                if ($lastWord !== 'CANNA' && $lastWord !== 'HIGH5') {
                    $consonants = preg_replace('/[AEIOU\s]/', '', $lastWord);
                    $prefix .= substr($consonants, 0, 3);
                } else {
                    $prefix .= 'GEN';
                }
            } else {
                $words = explode(' ', preg_replace('/[^A-Za-z0-9 ]/', '', $cName));
                $prefix = '';
                foreach ($words as $w) {
                    if (!empty($w) && strtoupper($w) !== 'CANNA') {
                        $prefix .= ucfirst(substr($w, 0, 2));
                    }
                }
                if (empty($prefix)) { $prefix = "CLN"; }
            }

            $seqStmt = $conn->prepare("
                SELECT COUNT(DISTINCT SUBSTRING_INDEX(order_number, '-', 3)) as order_sequence 
                FROM orders 
                WHERE client_id = ? AND id <= ?
            ");
            $seqStmt->bind_param('ii', $cId, $oId);
            $seqStmt->execute();
            $seqResult = $seqStmt->get_result()->fetch_assoc();
            $seqNo = (int)($seqResult['order_sequence'] ?? 1);
            $cleanCodeDisplay = $prefix . str_pad($seqNo, 3, '0', STR_PAD_LEFT);
        ?>
            <tr>
                <td><strong><?= h($cleanCodeDisplay) ?></strong></td>
                <td><?= h($firstItem['client_name']) ?></td>
                <td><?= h($firstItem['order_date'] ?? '—') ?></td>
                <td><?= $itemCount ?> item<?= $itemCount > 1 ? 's' : '' ?> <button type="button" onclick="toggleDetails(this)" style="background: none; border: none; cursor: pointer; color: #2196F3; font-weight: bold;">▼</button></td>
                <td><?= money($orderTotal) ?></td>
                <td>
                    <form method="POST" action="/hvf-app/api/orders_status.php">
                        <input type="hidden" name="id" value="<?= h($firstItem['id']) ?>">
                        <select name="status" onchange="this.form.submit()">
                            <?php foreach (['Pending', 'Processing', 'Completed', 'Cancelled'] as $status): ?>
                                <option value="<?= h($status) ?>" <?= $firstItem['status'] === $status ? 'selected' : '' ?>><?= h($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td class="actions">
                  <a class="btn-action btn-invoice" href="/hvf-app/api/invoices_create.php?order_id=<?= h($firstItem['id']) ?>">
                    <i class="fa fa-leaf"></i> Invoice
                  </a>
                  <a class="btn-action btn-edit" href="/hvf-app/pages/order_edit.php?id=<?= h($firstItem['id']) ?>">
                    <i class="fa fa-pencil"></i> Edit
                  </a>
                <a class="btn-action btn-delete" href="/hvf-app/api/orders_delete.php?id=<?= h($firstItem['id']) ?>" onclick="return confirm('Delete this order?')">
                    <i class="fa fa-trash"></i> Delete
                </a>
                </td>

            </tr>
            <tr style="background-color: #f9f9f9; display: none;" class="order-details">
                <td colspan="7" style="padding: 10px 15px;">
                    <strong>Items:</strong>
                    <ul style="margin: 5px 0; padding-left: 20px;">
                        <?php foreach ($orderItems as $item): ?>
                            <li><?= h($item['strain_code']) ?> - <?= h($item['product_name'] ?? 'General Item Blueprint') ?> | <?= number_format((float)($item['quantity'] ?? 1), 2) ?>g = <?= money($item['total']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
?>