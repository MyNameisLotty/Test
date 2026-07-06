<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Invalid stock ID.');
}

$conn->begin_transaction();
try {
    // Optional: check if stock is linked to any orders
    $check = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE stock_id = ?");
    $check->bind_param('i', $id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();

    if ($result['cnt'] > 0) {
        die('Cannot delete: stock item is linked to existing orders.');
    }

    // Delete stock item
    $stmt = $conn->prepare("DELETE FROM stock WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();
    die('Stock deletion failed: ' . h($error->getMessage()));
}

redirect_to('/hvf-app/pages/stock.php');
?>
