<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$id = (int)($_POST['id'] ?? 0);
$categoryId = (int)($_POST['category_id'] ?? 0);
$strainCode = strtoupper(trim($_POST['strain_code'] ?? ''));
$growType = trim($_POST['grow_type'] ?? '');
$productName = trim($_POST['product_name'] ?? '');
$quantity = (float)($_POST['quantity'] ?? 0);
$sellingPrice = (float)($_POST['selling_price'] ?? 0);

if ($id <= 0 || $categoryId <= 0 || $strainCode === '' || $productName === '') {
    die('Please complete all stock fields.');
}

$stmt = $conn->prepare('UPDATE stock SET category_id = ?, strain_code = ?, grow_type = ?, product_name = ?, quantity = ?, selling_price = ? WHERE id = ?');
$stmt->bind_param('isssdsi', $categoryId, $strainCode, $growType, $productName, $quantity, $sellingPrice, $id);
$stmt->execute();

redirect_to('/hvf-app/pages/stock.php');
?>
