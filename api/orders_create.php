<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$clientId = (int)($_POST['client_id'] ?? 0);
$stockId = (int)($_POST['stock_id'] ?? 0);
$orderDate = $_POST['order_date'] ?? date('Y-m-d');
$description = trim($_POST['description'] ?? '');
$quantity = (float)($_POST['quantity'] ?? 0);
$price = (float)($_POST['price'] ?? 0);
$total = $quantity * $price;

if ($clientId <= 0 || $stockId <= 0 || $description === '' || $quantity <= 0 || $price < 0) {
    die('Please complete all order fields.');
}

$stockStmt = $conn->prepare('SELECT quantity FROM stock WHERE id = ?');
$stockStmt->bind_param('i', $stockId);
$stockStmt->execute();
$stock = $stockStmt->get_result()->fetch_assoc();
if (!$stock) {
    die('Stock item not found.');
}
if ((float)$stock['quantity'] < $quantity) {
    die('Not enough stock available.');
}

$conn->begin_transaction();
try {
    $newQty = (float)$stock['quantity'] - $quantity;
    $updateStock = $conn->prepare('UPDATE stock SET quantity = ? WHERE id = ?');
    $updateStock->bind_param('di', $newQty, $stockId);
    $updateStock->execute();

    $orderNumber = next_order_number($conn);
    $insert = $conn->prepare('INSERT INTO orders (order_number, client_id, order_date, stock_id, description, quantity, selling_price, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "Pending")');
    $insert->bind_param('sisisddd', $orderNumber, $clientId, $orderDate, $stockId, $description, $quantity, $price, $total);
    $insert->execute();

    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();
    die('Order creation failed: ' . h($error->getMessage()));
}

redirect_to('/hvf-app/pages/orders.php');
?>
