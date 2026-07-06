<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$id = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? 'Pending';
$allowed = ['Pending', 'Processing', 'Completed', 'Cancelled'];
if ($id <= 0 || !in_array($status, $allowed, true)) {
    die('Invalid order status.');
}

$stmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
$stmt->bind_param('si', $status, $id);
$stmt->execute();

redirect_to('/hvf-app/pages/orders.php');
?>
