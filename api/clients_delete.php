<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die('Invalid client.');
}

$stmt = $conn->prepare('DELETE FROM clients WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();

redirect_to('/hvf-app/pages/clients.php');
?>
