<?php
include __DIR__ . '/includes/db.php';

$newUser = trim($_POST['newUser'] ?? '');
$newPass = $_POST['newPass'] ?? '';

if ($newUser === '' || $newPass === '') {
    exit('Missing fields');
}

$check = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$check->bind_param('s', $newUser);
$check->execute();
if ($check->get_result()->fetch_assoc()) {
    echo "<script>alert('Username already exists'); window.location.href='index.html';</script>";
    exit;
}

$hash = password_hash($newPass, PASSWORD_DEFAULT);
$role = 'staff';
$stmt = $conn->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $newUser, $hash, $role);

if ($stmt->execute()) {
    echo "<script>alert('User created successfully'); window.location.href='index.html';</script>";
    exit;
}

echo 'Error: ' . htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8');
?>
