<?php
header("Content-Type: application/json");
include 'db.php';
include 'functions.php';

$action = $_GET['action'] ?? '';

// --- CLIENTS ---
if ($action == 'get_clients') {
    $result = $conn->query("SELECT * FROM clients");
    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
}
elseif ($action == 'save_client') {
    $data = json_decode(file_get_contents("php://input"), true);
    $name = $conn->real_escape_string($data['name']);
    $email = $conn->real_escape_string($data['email']);
    $phone = $conn->real_escape_string($data['phone']);
    $address = $conn->real_escape_string($data['address']);
    $notes = $conn->real_escape_string($data['notes']);
    $discount = $data['discount'] ? 1 : 0;
    
    if (!empty($data['dbId'])) {
        $id = intval($data['dbId']);
        $conn->query("UPDATE clients SET name='$name', email='$email', phone='$phone', address='$address', notes='$notes', discount=$discount WHERE id=$id");
    } else {
        $conn->query("INSERT INTO clients (name, email, phone, address, notes, discount) VALUES ('$name', '$email', '$phone', '$address', '$notes', $discount)");
    }
    echo json_encode(["status" => "success"]);
}
elseif ($action == 'delete_client') {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM clients WHERE id=$id");
    echo json_encode(["status" => "success"]);
}

// --- CATEGORIES ---
elseif ($action == 'get_categories') {
    $result = $conn->query("SELECT name FROM categories");
    $cats = [];
    while($row = $result->fetch_assoc()) { 
        $cats[] = $row['name']; 
    }
    echo json_encode($cats);
}
elseif ($action == 'save_category') {
    $data = json_decode(file_get_contents("php://input"), true);
    $name = strtoupper(trim($conn->real_escape_string($data['name'])));
    
    if(!empty($name)) {
        $conn->query("INSERT IGNORE INTO categories (name) VALUES ('$name')");
    }
    echo json_encode(["status" => "success"]);
}

// --- INVENTORY ---
elseif ($action == 'get_inventory') {
    $result = $conn->query("SELECT * FROM inventory");
    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
}
elseif ($action == 'save_inventory') {
    $data = json_decode(file_get_contents("php://input"), true);
    $strain = $conn->real_escape_string($data['strain']);
    $category = $conn->real_escape_string($data['category']);
    $description = $conn->real_escape_string($data['description']);
    $weight = floatval($data['weight']);
    $price = floatval($data['price']);
    
    if (!empty($data['dbId'])) {
        $id = intval($data['dbId']);
        $conn->query("UPDATE inventory SET strain='$strain', category='$category', description='$description', weight=$weight, price=$price WHERE id=$id");
    } else {
        $conn->query("INSERT INTO inventory (strain, category, description, weight, price) VALUES ('$strain', '$category', '$description', $weight, $price)");
    }
    echo json_encode(["status" => "success"]);
}
elseif ($action == 'delete_inventory') {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM inventory WHERE id=$id");
    echo json_encode(["status" => "success"]);
}

// --- ORDERS ---
elseif ($action == 'get_orders') {
    $ordersResult = $conn->query("SELECT * FROM saved_orders");
    $orders = [];
    while ($order = $ordersResult->fetch_assoc()) {
        $orderId = $order['id'];
        $itemsResult = $conn->query("SELECT * FROM order_items WHERE order_id='$orderId'");
        $items = [];
        while($itemRow = $itemsResult->fetch_assoc()) {
            $items[] = $itemRow;
        }
        $order['items'] = $items;
        $orders[] = $order;
    }
    echo json_encode($orders);
}
elseif ($action == 'save_order') {
    $data = json_decode(file_get_contents("php://input"), true);
    $orderId = $conn->real_escape_string($data['id']);
    $customer = $conn->real_escape_string($data['customer']);
    $total = floatval($data['total']);
    $status = !empty($data['status']) ? $conn->real_escape_string($data['status']) : 'Uninvoiced';
    
    $conn->query("DELETE FROM saved_orders WHERE id='$orderId'");
    $conn->query("INSERT INTO saved_orders (id, customer, total_val, status) VALUES ('$orderId', '$customer', $total, '$status')");
    
    if (!empty($data['items'])) {
        foreach ($data['items'] as $item) {
            $strain = $conn->real_escape_string($item['strain']);
            $cat = $conn->real_escape_string($item['category']);
            $qty = floatval($item['qty']);
            $price = floatval($item['price']);
            $lineTotal = !empty($item['total']) ? floatval($item['total']) : ($qty * $price);
            $conn->query("INSERT INTO order_items (order_id, strain, category, qty, price, total) VALUES ('$orderId', '$strain', '$cat', $qty, $price, $lineTotal)");
        }
    }
    echo json_encode(["status" => "success"]);
}

// --- INVOICES ---
elseif ($action == 'get_invoices') {
    $result = $conn->query("SELECT * FROM invoices");
    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
}
elseif ($action == 'save_invoice') {
    $data = json_decode(file_get_contents("php://input"), true);
    $number = $conn->real_escape_string($data['number']);
    $client = $conn->real_escape_string($data['client']);
    $summary = $conn->real_escape_string($data['summaryText']);
    $total = floatval($data['totalVal']);
    $linkedOrder = !empty($data['linkedOrderId']) ? "'".$conn->real_escape_string($data['linkedOrderId'])."'" : "NULL";
    
    if (!empty($data['editIndex'])) {
        $id = intval($data['editIndex']);
        $conn->query("UPDATE invoices SET number='$number', client='$client', summary_text='$summary', total_val=$total WHERE id=$id");
    } else {
        $conn->query("INSERT INTO invoices (number, client, summary_text, total_val, linked_order_id) VALUES ('$number', '$client', '$summary', $total, $linkedOrder)");
        
        if(!empty($data['itemsCache'])) {
            foreach ($data['itemsCache'] as $soldItem) {
                $strain = $conn->real_escape_string($soldItem['strain']);
                $qty = floatval($soldItem['qty']);
                $conn->query("UPDATE inventory SET weight = GREATEST(0, weight - $qty) WHERE strain='$strain'");
            }
        }
        if (!empty($data['linkedOrderId'])) {
            $orderId = $conn->real_escape_string($data['linkedOrderId']);
            $conn->query("UPDATE saved_orders SET status='Invoiced' WHERE id='$orderId'");
        }
    }
    echo json_encode(["status" => "success"]);
}
elseif ($action == 'delete_invoice') {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM invoices WHERE id=$id");
    echo json_encode(["status" => "success"]);
}
elseif ($action == 'get_finance') {
    $result = $conn->query("SELECT * FROM finance ORDER BY finance_date DESC");
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
}

elseif ($action == 'save_finance') {
    $data = json_decode(file_get_contents("php://input"), true);

    $date = $conn->real_escape_string($data['date']);
    $type = $conn->real_escape_string($data['type']);
    $amount = floatval($data['amount']);
    $notes = $conn->real_escape_string($data['notes']);

    $conn->query("
        INSERT INTO finance (finance_date, type, amount, notes)
        VALUES ('$date', '$type', $amount, '$notes')
    ");

    echo json_encode(["status" => "success"]);
}

elseif ($action == 'import_orders') {

    $orders = json_decode(file_get_contents("php://input"), true);

    foreach ($orders as $order) {

        $customer = $conn->real_escape_string($order['customer']);
        $date = $conn->real_escape_string($order['date']);
        $total = floatval($order['total']);

        /* -------------------------
           CREATE CLIENT IF MISSING
        --------------------------*/

        $checkClient = $conn->query("
            SELECT id
            FROM clients
            WHERE name = '$customer'
            LIMIT 1
        ");

        if ($checkClient->num_rows === 0) {

            $conn->query("
                INSERT INTO clients
                (
                    name,
                    email,
                    phone,
                    address,
                    notes,
                    discount
                )
                VALUES
                (
                    '$customer',
                    '',
                    '',
                    '',
                    'Imported from CSV',
                    0
                )
            ");
        }

        /* -------------------------
           ORDER ID
        --------------------------*/

        $orderId =
            "IMP_" .
            md5($customer . "_" . $date);

        /* -------------------------
           DUPLICATE PROTECTION
        --------------------------*/

        $exists = $conn->query("
            SELECT id
            FROM saved_orders
            WHERE id = '$orderId'
            LIMIT 1
        ");

        if ($exists->num_rows > 0) {
            continue;
        }

        /* -------------------------
           CREATE ORDER
        --------------------------*/

        $conn->query("
            INSERT INTO saved_orders
            (
                id,
                customer,
                total_val,
                status
            )
            VALUES
            (
                '$orderId',
                '$customer',
                $total,
                'Uninvoiced'
            )
        ");

        /* -------------------------
           CREATE ITEMS
        --------------------------*/

        foreach ($order['items'] as $item) {

            $strain =
                $conn->real_escape_string(
                    $item['strain']
                );

            $category =
                $conn->real_escape_string(
                    $item['category']
                );

            $qty =
                floatval($item['qty']);

            $price =
                floatval($item['price']);

            $lineTotal =
                floatval($item['total']);

            $conn->query("
                INSERT INTO order_items
                (
                    order_id,
                    strain,
                    category,
                    qty,
                    price,
                    total
                )
                VALUES
                (
                    '$orderId',
                    '$strain',
                    '$category',
                    $qty,
                    $price,
                    $lineTotal
                )
            ");
        }
    }

    echo json_encode([
        "status" => "success"
    ]);
}
?>
