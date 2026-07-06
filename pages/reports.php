<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

ob_start();

$orders    = $conn->query("SELECT * FROM orders ORDER BY order_date DESC");
$invoices  = $conn->query("SELECT * FROM invoices ORDER BY invoice_date DESC");
$stock     = $conn->query("SELECT * FROM stock ORDER BY strain_code, product_name");
$finances  = $conn->query("SELECT * FROM finances ORDER BY created_at DESC");
?>

<div class="page-title">
    <div>
        <h2>Reports</h2>
        <p>Expand a section to view and print its report.</p>
    </div>
</div>

<!-- ORDERS REPORT -->
<div class="card">
    <div class="card-header">
        <h3 onclick="toggleCard('orders-data')" style="display:inline; cursor:pointer;">➕ Orders Report</h3>
        <button onclick="printSection('orders-data')" style="float:right;">🖨 Print Orders</button>
    </div>
    <div id="orders-data" style="display:none;">
        <table>
            <thead>
                <tr><th>Order #</th><th>Client</th><th>Date</th><th>Description</th><th>Total</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php while ($o = $orders->fetch_assoc()): ?>
                    <tr>
                        <td><?= h($o['order_number']) ?></td>
                        <td><?= h($o['client_id']) ?></td>
                        <td><?= h($o['order_date']) ?></td>
                        <td><?= h($o['description']) ?></td>
                        <td><?= money($o['total']) ?></td>
                        <td><?= h($o['status']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- INVOICES REPORT -->
<div class="card">
    <div class="card-header">
        <h3 onclick="toggleCard('invoices-data')" style="display:inline; cursor:pointer;">➕ Invoices Report</h3>
        <button onclick="printSection('invoices-data')" style="float:right;">🖨 Print Invoices</button>
    </div>
    <div id="invoices-data" style="display:none;">
        <table>
            <thead>
                <tr><th>Invoice #</th><th>Client</th><th>Date</th><th>Total</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php while ($i = $invoices->fetch_assoc()): ?>
                    <tr>
                        <td><?= h($i['invoice_number']) ?></td>
                        <td><?= h($i['client_id']) ?></td>
                        <td><?= h($i['invoice_date']) ?></td>
                        <td><?= money($i['total']) ?></td>
                        <td><?= h($i['status']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- STOCK REPORT -->
<div class="card">
    <div class="card-header">
        <h3 onclick="toggleCard('stock-data')" style="display:inline; cursor:pointer;">➕ Stock Report</h3>
        <button onclick="printSection('stock-data')" style="float:right;">🖨 Print Stock</button>
    </div>
    <div id="stock-data" style="display:none;">
        <table>
            <thead>
                <tr><th>Strain</th><th>Product</th><th>Category</th><th>Grow Type</th><th>Quantity</th><th>Price</th></tr>
            </thead>
            <tbody>
                <?php while ($s = $stock->fetch_assoc()): ?>
                    <tr>
                        <td><?= h($s['strain_code']) ?></td>
                        <td><?= h($s['product_name']) ?></td>
                        <td><?= h($s['category_id']) ?></td>
                        <td><?= h($s['grow_type']) ?></td>
                        <td><?= number_format((float)$s['quantity'], 2) ?> g</td>
                        <td><?= money($s['selling_price']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- FINANCES REPORT -->
<div class="card">
    <div class="card-header">
        <h3 onclick="toggleCard('finances-data')" style="display:inline; cursor:pointer;">➕ Finances Report</h3>
        <button onclick="printSection('finances-data')" style="float:right;">🖨 Print Finances</button>
    </div>
    <div id="finances-data" style="display:none;">
        <table>
            <thead>
                <tr><th>Date</th><th>Description</th><th>Amount</th><th>Type</th></tr>
            </thead>
            <tbody>
                <?php while ($f = $finances->fetch_assoc()): ?>
                    <tr>
                        <td><?= h($f['created_at']) ?></td>
                        <td><?= h($f['description']) ?></td>
                        <td><?= money($f['amount']) ?></td>
                        <td><?= h($f['type']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleCard(id) {
    var el = document.getElementById(id);
    el.style.display = (el.style.display === 'none') ? 'block' : 'none';
}
function printSection(sectionId) {
    var content = document.getElementById(sectionId).innerHTML;
    var printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Print Report</title></head><body>');
    printWindow.document.write(content);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
?>
