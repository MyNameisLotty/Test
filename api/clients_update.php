<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$id = (int)($_POST['id'] ?? 0);
$name = trim($_POST['client_name'] ?? '');
$contact = trim($_POST['contact_person'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');

if ($id <= 0 || $name === '') {
    die('Invalid client update.');
}

$stmt = $conn->prepare('UPDATE clients SET client_name = ?, contact_person = ?, phone = ?, email = ?, address = ? WHERE id = ?');
$stmt->bind_param('sssssi', $name, $contact, $phone, $email, $address, $id);
$stmt->execute();

redirect_to('/hvf-app/pages/clients.php');
?>
