<?php
// api/stock_category_create.php
// Creates a new stock category and redirects back to stock page

include __DIR__ . '/../includes/db.php';

$name = trim($_POST['name'] ?? '');

if ($name === '') {
    header('Location: /hvf-app/pages/stock.php?error=empty_category');
    exit;
}

$name = $conn->real_escape_string($name);

// Prevent duplicates
$check = $conn->query("SELECT id FROM stock_categories WHERE name = '$name' LIMIT 1");
if ($check->num_rows > 0) {
    header('Location: /hvf-app/pages/stock.php?error=duplicate_category');
    exit;
}

$conn->query("INSERT INTO stock_categories (name) VALUES ('$name')");

header('Location: /hvf-app/pages/stock.php?success=category_added');
exit;
?>
