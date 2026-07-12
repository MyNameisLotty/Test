<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

ob_start();

$invoices = $conn->query("\n    SELECT i.*, c.client_name, o.order_number\n    FROM invoices i\n    LEFT JOIN clients c ON i.client_id = c.id\n    LEFT JOIN orders o ON i.order_id = o.id\n    ORDER BY i.id DESC\n");
?>

<div class="page-title">
    <div>
        <h2>Invoices</h2>
        <p>View, mark paid, and print generated invoices.</p>
    </div>
</div>

<div class="card">
    <table>
        <tr><th>Invoice #</th><th>Order</th><th>Client</th><th>Date</th><th>Total</th><th>Status</th><th>Actions</th></tr>
        <?php while ($i = $invoices->fetch_assoc()): ?>
            <?php $status = strtolower($i['status'] ?? 'unpaid'); ?>
            <tr>
                <td><?= h($i['invoice_number']) ?></td>
                <td><?= h($i['order_number'] ?? '') ?></td>
                <td><?= h($i['client_name'] ?? 'Unknown') ?></td>
                <td><?= h($i['invoice_date']) ?></td>
                <td><?= money($i['total']) ?></td>
                <td><span class="status-badge status-<?= h($status) ?>"><?= h(ucfirst($status)) ?></span></td>
                <td class="actions">
    <a class="btn-action btn-view" href="/hvf-app/pages/invoice_view.php?id=<?= h($i['id']) ?>">View</a>
    <?php if (($i['status'] ?? '') !== 'Paid'): ?>
        <a class="btn-action btn-paid" href="/hvf-app/api/invoices_update_status.php?id=<?= h($i['id']) ?>&status=Paid">Mark Paid</a>
    <?php else: ?>
        <a class="btn-action btn-unpaid" href="/hvf-app/api/invoices_update_status.php?id=<?= h($i['id']) ?>&status=Unpaid">Mark Unpaid</a>
    <?php endif; ?>
    <a class="btn-action btn-delete" href="/hvf-app/api/invoices_delete.php?id=<?= h($i['id']) ?>" onclick="return confirm('Delete this invoice?')">Delete</a>
</td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
?>
