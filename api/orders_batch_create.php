<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$clientId = (int)($_POST['client_id'] ?? 0);
$orderDate = $_POST['order_date'] ?? date('Y-m-d');
$stockIds = $_POST['stock_id'] ?? [];
$quantities = $_POST['quantity'] ?? [];
$prices = $_POST['price'] ?? [];

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
    $stockId = (int)$stockIds[$i];
    $quantity = (float)$quantities[$i];
    $price = (float)$prices[$i];
    
    if ($stockId <= 0 || $quantity <= 0 || $price < 0) {
        die('Please complete all fields for each item.');
    }
    
    $items[] = [
        'stock_id' => $stockId,
        'quantity' => $quantity,
        'price' => $price,
        'total' => $quantity * $price
    ];
    
    // Track stock to deduct
    if (!isset($stockToDeduct[$stockId])) {
        $stockToDeduct[$stockId] = 0;
    }
    $stockToDeduct[$stockId] += $quantity;
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Validate and lock all stock items
    foreach ($stockToDeduct as $stockId => $neededQty) {
        $stockStmt = $conn->prepare('SELECT quantity FROM stock WHERE id = ? FOR UPDATE');
        $stockStmt->bind_param('i', $stockId);
        $stockStmt->execute();
        $stock = $stockStmt->get_result()->fetch_assoc();
        
        if (!$stock) {
            throw new Exception("Stock item ID $stockId not found.");
        }
        
        if ((float)$stock['quantity'] < $neededQty) {
            throw new Exception("Not enough stock for item ID $stockId. Available: " . $stock['quantity'] . "g, Requested: " . $neededQty . "g");
        }
    }
    
    // Create ONE order number for all items
  $orderNumber = next_order_number($conn, $clientId);

    
    // Calculate grand total
    $grandTotal = 0;
    foreach ($items as $item) {
        $grandTotal += $item['total'];
    }
    
    // Create orders and update stock
    $orderStmt = $conn->prepare('INSERT INTO orders (order_number, client_id, order_date, stock_id, description, quantity, selling_price, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "Pending")');
    $updateStockStmt = $conn->prepare('UPDATE stock SET quantity = quantity - ? WHERE id = ?');
    
    $ordersCreated = 0;
    
    foreach ($items as $item) {
        $description = "Part of Order #" . $orderNumber; // All items reference the main order number
        
        $orderStmt->bind_param('sisisddd', $orderNumber, $clientId, $orderDate, $item['stock_id'], $description, $item['quantity'], $item['price'], $item['total']);
        $orderStmt->execute();
        
        // Update stock
        $updateStockStmt->bind_param('di', $item['quantity'], $item['stock_id']);
        $updateStockStmt->execute();
        
        $ordersCreated++;
    }
    
    $conn->commit();
    
    $_SESSION['success'] = "Order #" . $orderNumber . " created successfully with $ordersCreated items for client ID $clientId.";
    redirect_to('/hvf-app/pages/orders.php');
    
} catch (Throwable $error) {
    $conn->rollback();
    die('Batch order creation failed: ' . h($error->getMessage()));
}
?>
