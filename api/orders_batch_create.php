<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$clientId   = (int)($_POST['client_id'] ?? 0);
$orderDate  = $_POST['order_date'] ?? date('Y-m-d');
$stockIds   = $_POST['stock_id'] ?? [];
$quantities = $_POST['quantity'] ?? [];
$prices     = $_POST['price'] ?? [];

// Validate client
if ($clientId <= 0) {
    die('Please select a client.');
}

// Validate arrays have same length and content
$itemCount = count($stockIds);
if ($itemCount === 0 || $itemCount !== count($quantities) || $itemCount !== count($prices)) {
    die('Invalid order items.');
}

// Validate each item
$items = [];
$stockToDeduct = []; // Track stock deductions

for ($i = 0; $i < $itemCount; $i++) {
    $stockId  = (int)$stockIds[$i];
    $quantity = (float)$quantities[$i];
    $price    = (float)$prices[$i];

    if ($stockId <= 0 || $quantity <= 0 || $price < 0) {
        die('Please complete all fields for each item.');
    }

    $items[] = [
        'stock_id'      => $stockId,
        'quantity'      => $quantity,
        'selling_price' => $price,   // matches DB column
        'total'         => $quantity * $price
    ];

    // Track stock to deduct
    if (!isset($stockToDeduct[$stockId])) {
        $stockToDeduct[$stockId] = 0;
    }
    $stockToDeduct[$stockId] += $quantity;
}

try {
    $conn->begin_transaction();

    // Generate a base order number (example: timestamp + client ID)
    $baseOrderNumber = uniqid("ORD-{$clientId}-");

    // Prepare statements
    $orderStmt = $conn->prepare(
        'INSERT INTO orders (order_number, client_id, order_date, stock_id, description, quantity, selling_price, total)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$orderStmt) {
        throw new Exception("Order prepare failed: " . $conn->error);
    }

    $updateStockStmt = $conn->prepare(
        'UPDATE stock SET quantity = quantity - ? WHERE id = ?'
    );
    if (!$updateStockStmt) {
        throw new Exception("Stock update prepare failed: " . $conn->error);
    }

    $stockNameStmt = $conn->prepare(
        'SELECT strain_code, product_name FROM stock WHERE id = ?'
    );
    if (!$stockNameStmt) {
        throw new Exception("Stock name prepare failed: " . $conn->error);
    }

    $ordersCreated = 0;

    foreach ($items as $index => $item) {
        $rowOrderNumber = ($itemCount > 1)
            ? $baseOrderNumber . '-' . ($index + 1)
            : $baseOrderNumber;

        // Fetch stock info
        $stockNameStmt->bind_param('i', $item['stock_id']);
        $stockNameStmt->execute();
        $stockInfo = $stockNameStmt->get_result()->fetch_assoc();

        $strainCode  = !empty($stockInfo['strain_code']) ? '[' . $stockInfo['strain_code'] . '] ' : '';
        $description = $strainCode . ($stockInfo['product_name'] ?? 'Stock Item');

    $orderStmt->bind_param(
    'sisisddd',
    $rowOrderNumber,
    $clientId,
    $orderDate,
    $item['stock_id'],
    $description,
    $item['quantity'],
    $item['selling_price'],   // ✅ must be this
    $item['total']
);


        $orderStmt->execute();

        // Update stock
        $updateStockStmt->bind_param('di', $item['quantity'], $item['stock_id']);
        $updateStockStmt->execute();

        $ordersCreated++;
    }

    $conn->commit();

    $_SESSION['success'] = "Order #$baseOrderNumber created successfully with $ordersCreated items.";
    redirect_to('/hvf-app/pages/orders.php');

} catch (Throwable $error) {
    $conn->rollback();
    die('Batch order creation failed: ' . h($error->getMessage()));
}
?>
