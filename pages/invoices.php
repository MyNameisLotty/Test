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
            <?php
            $cleanCodeDisplay = '—'; // Default if no order linked or client data missing
            if (!empty($i['client_name']) && !empty($i['client_id']) && !empty($i['order_id'])) {
                $cName = $i['client_name'];
                $cId = (int)$i['client_id'];
                $oId = (int)$i['order_id'];

                $prefix = '';
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
                $seqStmt->close();
            }
            ?>
            <tr>
                <td><?= h($i['invoice_number']) ?></td>
                <td><?= h($cleanCodeDisplay) ?></td>
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
