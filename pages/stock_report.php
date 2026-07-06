<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

ob_start();

// Fetch orders with stock linkage
$orders = $conn->query("
    SELECT o.id, o.order_number, o.order_date, o.description, o.quantity AS order_qty,
           s.product_name, s.strain_code, s.quantity AS current_stock, c.client_name
    FROM orders o
    JOIN clients c ON o.client_id = c.id
    LEFT JOIN stock s ON s.id = o.stock_id
    ORDER BY o.order_date DESC, o.id DESC
");
?>

<div class="page-title">
    <div>
        <h2>Stock Movement Report</h2>
        <p>Audit trail of orders and their impact on stock levels.</p>
    </div>
</div>

<div class="card">
    <h3>Orders vs Stock</h3>
    <table>
        <thead>
            <tr>
                <th>Order #</th>
                <th>Date</th>
                <th>Client</th>
                <th>Description</th>
                <th>Ordered Qty</th>
                <th>Stock Item</th>
                <th>Strain Code</th>
                <th>Current Stock Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($o = $orders->fetch_assoc()): ?>
                <tr>
                    <td><?= h($o['order_number']) ?></td>
                    <td><?= h($o['order_date']) ?></td>
                    <td><?= h($o['client_name']) ?></td>
                    <td><?= h($o['description']) ?></td>
                    <td><?= number_format((float)$o['order_qty'], 2) ?> g</td>
                    <td><?= h($o['product_name']) ?></td>
                    <td><?= h($o['strain_code']) ?></td>
                    <td><?= number_format((float)$o['current_stock'], 2) ?> g</td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
?>
