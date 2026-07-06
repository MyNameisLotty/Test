<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM clients WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();
if (!$client) {
    die('Client not found.');
}

ob_start();
?>

<div class="page-title">
    <div><h2>Edit Client</h2><p>Update client contact and billing information.</p></div>
    <a class="button secondary" href="/hvf-app/pages/clients.php">Back to Clients</a>
</div>

<div class="card">
    <form method="POST" action="/hvf-app/api/clients_update.php" class="form-grid">
        <input type="hidden" name="id" value="<?= h($client['id']) ?>">
        <input type="text" name="client_name" value="<?= h($client['client_name']) ?>" required>
        <input type="text" name="contact_person" value="<?= h($client['contact_person']) ?>">
        <input type="text" name="phone" value="<?= h($client['phone']) ?>">
        <input type="email" name="email" value="<?= h($client['email']) ?>">
        <textarea name="address"><?= h($client['address']) ?></textarea>
        <button type="submit">Update Client</button>
    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
?>
