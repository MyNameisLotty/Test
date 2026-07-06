<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$status = $_GET['status'] ?? 'Paid';
$allowed = ['Unpaid', 'Paid'];
if ($id <= 0 || !in_array($status, $allowed, true)) {
    die('Invalid invoice status.');
}

$stmt = $conn->prepare('UPDATE invoices SET status = ? WHERE id = ?');
$stmt->bind_param('si', $status, $id);
$stmt->execute();

redirect_to('/hvf-app/pages/invoices.php');
?>
