<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("\n    SELECT i.*, o.order_number, o.description, o.quantity, o.selling_price,\n           c.client_name, c.phone, c.email, c.address\n    FROM invoices i\n    LEFT JOIN orders o ON i.order_id = o.id\n    LEFT JOIN clients c ON i.client_id = c.id\n    WHERE i.id = ?\n");
$stmt->bind_param('i', $id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();

if (!$invoice) {
    die('Invoice not found.');
}

ob_start();
?>

<div class="card invoice-card">
    <div class="invoice-header">
        <img src="/hvf-app/images/HVF-Logo.png" alt="HVF Logo">
        <div>
            <h2>Invoice <?= h($invoice['invoice_number']) ?></h2>
            <p><?= h($invoice['invoice_date']) ?></p>
            <p><span class="status-badge status-<?= strtolower(h($invoice['status'])) ?>"><?= h($invoice['status']) ?></span></p>
        </div>
    </div>

    <hr>

    <div class="split-grid">
        <div>
            <h3>Bill To</h3>
            <p><strong><?= h($invoice['client_name'] ?? 'Unknown') ?></strong></p>
            <p><?= h($invoice['phone'] ?? '') ?></p>
            <p><?= h($invoice['email'] ?? '') ?></p>
            <p><?= nl2br(h($invoice['address'] ?? '')) ?></p>
        </div>
        <div>
            <h3>Order</h3>
            <p><?= h($invoice['order_number'] ?? '') ?></p>
        </div>
    </div>

    <table>
        <tr><th>Description</th><th>Quantity</th><th>Unit Price</th><th>Total</th></tr>
        <tr>
            <td><?= h($invoice['description'] ?? 'Order') ?></td>
            <td><?= number_format((float)($invoice['quantity'] ?? 0), 2) ?> g</td>
            <td><?= money($invoice['selling_price'] ?? 0) ?></td>
            <td><?= money($invoice['total']) ?></td>
        </tr>
    </table>

    <h2 class="invoice-total">Total: <?= money($invoice['total']) ?></h2>

    <div class="banking">
        <h3>Banking Details</h3>
        <p>Bank: YOUR BANK</p>
        <p>Account: 123456789</p>
        <p>Branch: 000000</p>
    </div>

    <button onclick="window.print()">Print / Save PDF</button>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
?>
