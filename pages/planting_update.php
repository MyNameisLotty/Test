<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("No planting ID provided.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stage        = $_POST['stage'] ?? null;
    $planted_date = $_POST['planted_date'] ?? null;
    $flower_date  = $_POST['flower_date'] ?? null;

    $stmt = $conn->prepare("UPDATE planting_schedule 
        SET stage=?, planted_date=?, flower_date=? 
        WHERE id=?");
    $stmt->bind_param("sssi", $stage, $planted_date, $flower_date, $id);
    $stmt->execute();

    header("Location: /hvf-app/dashboard.php");
    exit;
}

// Load current planting entry
$planting = $conn->query("SELECT * FROM planting_schedule WHERE id=$id")->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Planting</title>
    <link rel="stylesheet" href="/hvf-app/css/style.css">
</head>
<body>
<div class="card" style="max-width:600px; margin:2rem auto; padding:1.5rem;">
    <h2>Update Planting Schedule</h2>
    <form method="post">
        <label>Stage</label><br>
        <select name="stage">
            <option value="clone" <?= $planting['stage']=='clone'?'selected':'' ?>>Clone</option>
            <option value="veg" <?= $planting['stage']=='veg'?'selected':'' ?>>Veg</option>
            <option value="flower" <?= $planting['stage']=='flower'?'selected':'' ?>>Flower</option>
            <option value="harvest" <?= $planting['stage']=='harvest'?'selected':'' ?>>Harvest</option>
        </select><br><br>

        <label>Planted Date</label><br>
        <input type="date" name="planted_date" value="<?= $planting['planted_date'] ?>"><br><br>

        <label>Flower Date</label><br>
        <input type="date" name="flower_date" value="<?= $planting['flower_date'] ?>"><br><br>

        <button type="submit" class="button">Save Changes</button>
        <a href="/hvf-app/dashboard.php" class="button" style="background:#888;">Cancel</a>
    </form>
</div>
</body>
</html>
