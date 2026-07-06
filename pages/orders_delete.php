<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Invalid order ID.');
}

// Get order details
$orderStmt = $conn->prepare('SELECT stock_id, quantity FROM orders WHERE id = ?');
$orderStmt->bind_param('i', $id);
$orderStmt->execute();
$order = $orderStmt->get_result()->fetch_assoc();

if (!$order) {
    die('Order not found.');
}

$stockId = (int)$order['stock_id'];
$qty = (float)$order['quantity'];

$conn->begin_transaction();
try {
    // Restore stock
    $stockStmt = $conn->prepare('UPDATE stock SET quantity = quantity + ? WHERE id = ?');
    $stockStmt->bind_param('di', $qty, $stockId);
    $stockStmt->execute();

    // Delete order
    $deleteStmt = $conn->prepare('DELETE FROM orders WHERE id = ?');
    $deleteStmt->bind_param('i', $id);
    $deleteStmt->execute();

    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();
    die('Order deletion failed: ' . h($error->getMessage()));
}

redirect_to('/hvf-app/pages/orders.php');
?>
