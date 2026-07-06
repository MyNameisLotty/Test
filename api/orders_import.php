<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    die('No CSV file uploaded.');
}

$handle = fopen($_FILES['file']['tmp_name'], 'r');
if (!$handle) {
    die('Could not open uploaded CSV.');
}

$selectClient = $conn->prepare('SELECT id FROM clients WHERE client_name = ? LIMIT 1');
$insertClient = $conn->prepare('INSERT INTO clients (client_name, contact_person, phone, email, address, notes) VALUES (?, "", "", "", "", "Imported from orders CSV")');
$selectCategory = $conn->prepare('SELECT id FROM stock_categories WHERE name = ? LIMIT 1');
$insertCategory = $conn->prepare('INSERT INTO stock_categories (name) VALUES (?)');
$selectStock = $conn->prepare('SELECT id FROM stock WHERE strain_code = ? AND product_name = ? LIMIT 1');
$insertStock = $conn->prepare('INSERT INTO stock (strain_code, product_name, category_id, grow_type, stage, quantity, unit, selling_price) VALUES (?, ?, ?, ?, "Dry", 0, "grams", ?)');
$selectOrder = $conn->prepare('SELECT id FROM orders WHERE order_number = ? LIMIT 1');
$insertOrder = $conn->prepare('INSERT INTO orders (order_number, client_id, order_date, stock_id, description, quantity, selling_price, total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "Completed")');
$selectFinance = $conn->prepare('SELECT id FROM finances WHERE transaction_date = ? AND description = ? AND type = "Income" AND amount = ? LIMIT 1');
$insertFinance = $conn->prepare('INSERT INTO finances (transaction_date, description, type, amount) VALUES (?, ?, "Income", ?)');

fgetcsv($handle);
$imported = 0;
$skipped = 0;
$lastClientName = '';
$lastRawDate = '';

$conn->begin_transaction();

try {
    while (($row = fgetcsv($handle)) !== false) {
        [$clientName, $rawDate, $strainCode, $description, $rawGrowType, $rawKg, $rawPrice, $rawTotal] = array_pad($row, 8, '');

        $clientName = trim($clientName);
        $description = trim($description);
        $strainCode = strtoupper(trim($strainCode));
        $quantityGrams = round(((float)str_replace(',', '.', trim($rawKg))) * 1000, 2);
        $sellingPrice = parse_money($rawPrice);
        $total = parse_money($rawTotal);

        if (strcasecmp($clientName, 'Shipping') === 0) {
            $description = 'Shipping';
            $clientName = $lastClientName;
            $rawDate = trim($rawDate) !== '' ? $rawDate : $lastRawDate;
        }

        $orderDate = parse_csv_date($rawDate);
        if ($clientName === '' || !$orderDate || $total <= 0) {
            $skipped++;
            continue;
        }

        if (strcasecmp($description, 'Shipping') !== 0) {
            $lastClientName = $clientName;
            $lastRawDate = trim($rawDate);
        }

        $selectClient->bind_param('s', $clientName);
        $selectClient->execute();
        $client = $selectClient->get_result()->fetch_assoc();
        if ($client) {
            $clientId = (int)$client['id'];
        } else {
            $insertClient->bind_param('s', $clientName);
            $insertClient->execute();
            $clientId = $conn->insert_id;
        }

        $stockId = null;
        if ($strainCode !== '') {
            $categoryName = 'Flower';
            $selectCategory->bind_param('s', $categoryName);
            $selectCategory->execute();
            $category = $selectCategory->get_result()->fetch_assoc();
            if ($category) {
                $categoryId = (int)$category['id'];
            } else {
                $insertCategory->bind_param('s', $categoryName);
                $insertCategory->execute();
                $categoryId = $conn->insert_id;
            }

            $selectStock->bind_param('ss', $strainCode, $description);
            $selectStock->execute();
            $stock = $selectStock->get_result()->fetch_assoc();
            if ($stock) {
                $stockId = (int)$stock['id'];
            } else {
                $growType = grow_type_label($rawGrowType);
                $insertStock->bind_param('ssisd', $strainCode, $description, $categoryId, $growType, $sellingPrice);
                $insertStock->execute();
                $stockId = $conn->insert_id;
            }
        }

        $orderKey = implode('|', [$clientName, $orderDate, $strainCode, $description, number_format($quantityGrams, 2, '.', ''), number_format($total, 2, '.', '')]);
        $orderNumber = 'IMP-' . strtoupper(substr(sha1($orderKey), 0, 12));

        $selectOrder->bind_param('s', $orderNumber);
        $selectOrder->execute();
        if ($selectOrder->get_result()->fetch_assoc()) {
            $skipped++;
            continue;
        }

        $stockIdForInsert = $stockId;
        $insertOrder->bind_param('sisisddd', $orderNumber, $clientId, $orderDate, $stockIdForInsert, $description, $quantityGrams, $sellingPrice, $total);
        $insertOrder->execute();
        $imported++;

        $financeDescription = "Imported order {$orderNumber}: {$clientName} - {$description}";
        $selectFinance->bind_param('ssd', $orderDate, $financeDescription, $total);
        $selectFinance->execute();
        if (!$selectFinance->get_result()->fetch_assoc()) {
            $insertFinance->bind_param('ssd', $orderDate, $financeDescription, $total);
            $insertFinance->execute();
        }
    }

    $conn->commit();
} catch (Throwable $error) {
    $conn->rollback();
    die('Import failed: ' . h($error->getMessage()));
} finally {
    fclose($handle);
}

redirect_to('/hvf-app/pages/orders_import.php?imported=' . $imported . '&skipped=' . $skipped);
?>
