<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

// Collect POST values
$category_id = $_POST['category_id'] ?? null;
$strain_id   = $_POST['strain_id'] ?? null;   // strain selected from dropdown
$grow_type   = $_POST['grow_type'] ?? null;
$quantity    = $_POST['quantity'] ?? null;

// Validate required fields
if (!$category_id || !$strain_id || !$grow_type || !$quantity) {
    die('Please complete all required fields.');
}

// Lookup strain details from reference table
$stmt = $conn->prepare("SELECT strain_code, product_name, default_price 
                        FROM strains 
                        WHERE id=? AND grow_type=?");
$stmt->bind_param("is", $strain_id, $grow_type);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result) {
    die('Strain not found for the selected grow type.');
}

$strain_code   = $result['strain_code'];
$product_name  = $result['product_name'];
$selling_price = $result['default_price'];

// Insert into stock
$stmt = $conn->prepare("INSERT INTO stock 
    (category_id, strain_code, grow_type, product_name, quantity, selling_price) 
    VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssdd", $category_id, $strain_code, $grow_type, $product_name, $quantity, $selling_price);

if ($stmt->execute()) {
    redirect_to('/hvf-app/pages/stock.php');
} else {
    die('Failed to create stock item: ' . h($stmt->error));
}
?>
