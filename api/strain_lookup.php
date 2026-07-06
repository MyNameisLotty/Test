<?php
include __DIR__ . '/../includes/db.php';

$id       = $_GET['id'] ?? null;
$growType = $_GET['grow_type'] ?? null;

if ($id && $growType) {
    $stmt = $conn->prepare("SELECT product_name, default_price 
                            FROM strains 
                            WHERE id=? AND grow_type=?");
    $stmt->bind_param("is", $id, $growType);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode(["error" => "Missing parameters"]);
}
?>
