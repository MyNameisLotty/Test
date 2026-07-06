<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) {
    die('Order not found.');
}

ob_start();
?>

<div class="page-title">
    <div><h2>Edit Order</h2><p>Adjust order description, quantity, and price.</p></div>
    <a class="button secondary" href="/hvf-app/pages/orders.php">Back to Orders</a>
</div>

<div class="card">
    <form method="POST" action="/hvf-app/api/orders_update.php" class="form-grid">
        <input type="hidden" name="id" value="<?= h($order['id']) ?>">
        <input type="text" name="description" value="<?= h($order['description']) ?>" required>
        <input type="number" step="0.01" name="quantity" value="<?= h($order['quantity']) ?>" required>
        <input type="number" step="0.01" name="price" value="<?= h($order['selling_price']) ?>" required>
        <button type="submit">Update Order</button>
    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
?>
