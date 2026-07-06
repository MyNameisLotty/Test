<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$date = $_POST['transaction_date'] ?? date('Y-m-d');
$description = trim($_POST['description'] ?? '');
$type = $_POST['type'] ?? 'Expense';
$amount = (float)($_POST['amount'] ?? 0);

if ($description === '' || $amount <= 0 || !in_array($type, ['Income', 'Expense'], true)) {
    die('Please complete all finance fields.');
}

$stmt = $conn->prepare('INSERT INTO finances (transaction_date, description, type, amount) VALUES (?, ?, ?, ?)');
$stmt->bind_param('sssd', $date, $description, $type, $amount);
$stmt->execute();

redirect_to('/hvf-app/pages/finances.php');
?>
