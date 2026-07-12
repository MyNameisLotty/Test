<?php
include __DIR__ . '/../includes/db.php';

$orderId   = $_POST['id'] ?? null;
$category_id = $_POST['category_id'] ?? null;
$strain_id   = $_POST['strain_id'] ?? null;
$grow_type   = $_POST['grow_type'] ?? null;
$quantity    = $_POST['quantity'] ?? null;

if (!$orderId || !$category_id || !$strain_id || !$grow_type || !$quantity) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

// Lookup strain details
$stmt = $conn->prepare("SELECT strain_code, product_name, default_price 
                        FROM strains 
                        WHERE id=? AND grow_type=?");
$stmt->bind_param("is", $strain_id, $grow_type);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result) {
    http_response_code(404);
    echo json_encode(["error" => "Strain not found"]);
    exit;
}

$strain_code   = $result['strain_code'];
$product_name  = $result['product_name'];
$selling_price = $result['default_price'];

// Update stock
$stmt = $conn->prepare("UPDATE stock 
    SET category_id=?, strain_code=?, grow_type=?, product_name=?, quantity=?, selling_price=? 
    WHERE id=?");
$stmt->bind_param("isssddi", $category_id, $strain_code, $grow_type, $product_name, $quantity, $selling_price, $orderId);

if ($stmt->execute()) {
    // Redirect back to the stock view page with a success message flag
header("Location: /hvf-app/pages/stock.php?success=1");
exit();
} else {
    http_response_code(500);
    // Redirect back to the stock view page with a success message flag
header("Location: /hvf-app/pages/stock.php?success=1");
exit();
}
?>
