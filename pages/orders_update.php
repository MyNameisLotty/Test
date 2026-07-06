<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$id = (int)($_POST['id'] ?? 0);
$description = trim($_POST['description'] ?? '');
$quantity = (float)($_POST['quantity'] ?? 0);
$price = (float)($_POST['price'] ?? 0);
$total = $quantity * $price;

if ($id <= 0 || $description === '' || $quantity < 0 || $price < 0) {
    die('Invalid order update.');
}

// Get old order details
$orderStmt = $conn->prepare('SELECT stock_id, quantity FROM orders WHERE id = ?');
$orderStmt->bind_param('i', $id);
$orderStmt->execute();
$order = $orderStmt->get_result()->fetch_assoc();

if (!$order) {
    die('Order not found.');
}

$oldQty = (float)$order['quantity'];
$stockId = (int)$order['stock_id'];

// Calculate difference
$diff = $quantity - $oldQty;

$conn->begin_transaction();
try {
    // Adjust stock if quantity changed
    if ($diff !== 0) {
        $stockStmt = $conn->prepare('UPDATE stock SET quantity = quantity - ? WHERE id = ?');
        $stockStmt->bind_param('di', $diff, $stockId);
        $stockStmt->execute();
    }

    // Update order
    $stmt = $conn->prepare('UPDATE orders SET description = ?, quantity = ?, selling_price = ?, total = ? WHERE id = ?');
    $stmt->bind_param('sdddi', $description, $quantity, $price, $total, $id);
    $stmt->execute();

    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();
    die('Order update failed: ' . h($error->getMessage()));
}

redirect_to('/hvf-app/pages/orders.php');
?>
