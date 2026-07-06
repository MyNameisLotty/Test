<?php
include __DIR__ . '/../includes/functions.php';

ob_start();
$imported = isset($_GET['imported']) ? (int)$_GET['imported'] : null;
$skipped = isset($_GET['skipped']) ? (int)$_GET['skipped'] : null;
?>

<div class="page-title">
    <div>
        <h2>Import Orders</h2>
        <p>Upload a CSV with Shop / Contact, Date, Strain, Description, CT, Nett Weight (Kg), Unit Price, and Total Incl.</p>
    </div>
    <a class="button secondary" href="/hvf-app/pages/orders.php">Back to Orders</a>
</div>

<?php if ($imported !== null): ?>
    <div class="notice success">Imported <?= h($imported) ?> orders. Skipped <?= h($skipped ?? 0) ?> existing or empty rows.</div>
<?php endif; ?>

<div class="card">
    <h3>Upload CSV</h3>
    <form action="/hvf-app/api/orders_import.php" method="POST" enctype="multipart/form-data" class="form-grid">
        <input type="file" name="file" accept=".csv,text/csv" required>
        <button type="submit">Upload and Import</button>
    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
?>
