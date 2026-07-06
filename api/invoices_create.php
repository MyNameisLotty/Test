<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$orderId = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
if ($orderId <= 0) {
    die('Missing order ID.');
}

$orderStmt = $conn->prepare('SELECT * FROM orders WHERE id = ?');
$orderStmt->bind_param('i', $orderId);
$orderStmt->execute();
$order = $orderStmt->get_result()->fetch_assoc();
if (!$order) {
    die('Order not found.');
}

$existingStmt = $conn->prepare('SELECT id FROM invoices WHERE order_id = ? LIMIT 1');
$existingStmt->bind_param('i', $orderId);
$existingStmt->execute();
$existing = $existingStmt->get_result()->fetch_assoc();
if ($existing) {
    redirect_to('/hvf-app/pages/invoice_view.php?id=' . (int)$existing['id']);
}

$invoiceNumber = next_invoice_number($conn);
$invoiceDate = date('Y-m-d');
$status = 'Unpaid';
$clientId = (int)$order['client_id'];
$total = (float)$order['total'];

$insert = $conn->prepare('INSERT INTO invoices (invoice_number, client_id, order_id, invoice_date, total, status) VALUES (?, ?, ?, ?, ?, ?)');
$insert->bind_param('siisds', $invoiceNumber, $clientId, $orderId, $invoiceDate, $total, $status);
$insert->execute();
$invoiceId = $conn->insert_id;

redirect_to('/hvf-app/pages/invoice_view.php?id=' . $invoiceId);
?>
