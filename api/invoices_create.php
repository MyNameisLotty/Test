<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$orderId = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
if ($orderId <= 0) {
    die('Missing order ID.');
}

// 1. Fetch the primary order row to get its order_number and client_id
$orderStmt = $conn->prepare('SELECT order_number, client_id FROM orders WHERE id = ?');
$orderStmt->bind_param('i', $orderId);
$orderStmt->execute();
$primaryOrder = $orderStmt->get_result()->fetch_assoc();

if (!$primaryOrder) {
    die('Order not found.');
}

// Extract the base batch identifier (e.g., "ORD-CLIENTID-TIMESTAMP")
// This is consistent with how orders.php and invoice_view.php group orders.
$fullOrderNumber = $primaryOrder['order_number'];
$clientId = (int)$primaryOrder['client_id'];

$baseOrderNumberQuery = $conn->prepare("SELECT SUBSTRING_INDEX(?, '-', 3) AS base_order_num");
$baseOrderNumberQuery->bind_param('s', $fullOrderNumber);
$baseOrderNumberQuery->execute();
$baseOrderResult = $baseOrderNumberQuery->get_result()->fetch_assoc();
$baseOrderNumber = $baseOrderResult['base_order_num'];

// 2. Check if an invoice already exists for ANY order within this batch
$existingInvoiceStmt = $conn->prepare("
    SELECT i.id
    FROM invoices i
    JOIN orders o ON i.order_id = o.id
    WHERE o.client_id = ? AND SUBSTRING_INDEX(o.order_number, '-', 3) = ?
    LIMIT 1
");
$existingInvoiceStmt->bind_param('is', $clientId, $baseOrderNumber);
$existingInvoiceStmt->execute();
$existingInvoice = $existingInvoiceStmt->get_result()->fetch_assoc();

if ($existingInvoice) {
    redirect_to('/hvf-app/pages/invoice_view.php?id=' . (int)$existingInvoice['id']);
}

// 3. Calculate the total for the entire batch
$batchTotalStmt = $conn->prepare("
    SELECT SUM(total) AS grand_total
    FROM orders
    WHERE client_id = ? AND SUBSTRING_INDEX(order_number, '-', 3) = ?
");
$batchTotalStmt->bind_param('is', $clientId, $baseOrderNumber);
$batchTotalStmt->execute();
$batchTotalResult = $batchTotalStmt->get_result()->fetch_assoc();
$grandTotal = (float)$batchTotalResult['grand_total'];

// Now create the new invoice
$invoiceNumber = next_invoice_number($conn);
$invoiceDate = date('Y-m-d');
$status = 'Unpaid';

$insert = $conn->prepare('INSERT INTO invoices (invoice_number, client_id, order_id, invoice_date, total, status) VALUES (?, ?, ?, ?, ?, ?)');
$insert->bind_param('siisds', $invoiceNumber, $clientId, $orderId, $invoiceDate, $grandTotal, $status);
$insert->execute();
$invoiceId = $conn->insert_id;

redirect_to('/hvf-app/pages/invoice_view.php?id=' . $invoiceId);
?>
