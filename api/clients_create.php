<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$name = trim($_POST['client_name'] ?? '');
$contact = trim($_POST['contact_person'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');

if ($name === '') {
    die('Client name is required.');
}

$stmt = $conn->prepare('INSERT INTO clients (client_name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?)');
$stmt->bind_param('sssss', $name, $contact, $phone, $email, $address);
$stmt->execute();

redirect_to('/hvf-app/pages/clients.php');
?>
