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

$stmt = $conn->prepare('UPDATE orders SET description = ?, quantity = ?, selling_price = ?, total = ? WHERE id = ?');
$stmt->bind_param('sdddi', $description, $quantity, $price, $total, $id);
$stmt->execute();

redirect_to('/hvf-app/pages/orders.php');
?>
