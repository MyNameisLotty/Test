<?php
session_start();
include __DIR__ . '/includes/db.php';

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    header('Location: /hvf-app/index.html?error=missing');
    exit;
}

$stmt = $conn->prepare('SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$storedPassword = $user['password'] ?? '';
$validPassword = $user && (password_verify($password, $storedPassword) || hash_equals($storedPassword, $password));

if ($validPassword) {
    session_regenerate_id(true);
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    header('Location: /hvf-app/pages/dashboard.php');
    exit;
}

header('Location: /hvf-app/index.html?error=invalid');
exit;
?>
