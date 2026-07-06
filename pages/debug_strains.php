<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

ob_start();
?>

<div class="page-title">
    <div>
        <h2>Debug - Strains Table</h2>
        <p>View current strains data and fix it</p>
    </div>
</div>

<div class="card">
    <h3>Current Strains Data</h3>
    <table border="1" cellpadding="10" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f0f0f0;">
                <th>ID</th>
                <th>Strain Code</th>
                <th>Product Name</th>
                <th>Grow Type</th>
                <th>Default Price</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $result = $conn->query("SELECT id, strain_code, product_name, grow_type, default_price FROM strains ORDER BY strain_code, grow_type");
            while ($row = $result->fetch_assoc()):
            ?>
                <tr>
                    <td><?= h($row['id']) ?></td>
                    <td><?= h($row['strain_code']) ?></td>
                    <td><?= h($row['product_name']) ?></td>
                    <td><?= h($row['grow_type']) ?></td>
                    <td><?= h($row['default_price']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h3>SQL to Fix Your Data</h3>
    <p>Copy and run this SQL in your database to set the correct product names:</p>
    <pre style="background: #f5f5f5; padding: 15px; overflow-x: auto; border-radius: 4px;">
UPDATE strains SET product_name = CONCAT(
    CASE strain_code
        WHEN 'GSK-GH' THEN 'Grape Stank'
        WHEN 'GSK-IND' THEN 'Grape Stank'
        WHEN 'GSK-OD' THEN 'Grape Stank'
        WHEN 'PM-GH' THEN 'Permanent Marker'
        WHEN 'PM-IND' THEN 'Permanent Marker'
        WHEN 'PM-OD' THEN 'Permanent Marker'
        WHEN 'MD-GH' THEN 'Mango Dog'
        WHEN 'MD-IND' THEN 'Mango Dog'
        WHEN 'MD-OD' THEN 'Mango Dog'
        WHEN 'FOF-GH' THEN 'Face on Fire'
        WHEN 'FOF-IND' THEN 'Face on Fire'
        WHEN 'FOF-OD' THEN 'Face on Fire'
        WHEN 'BN-GH' THEN 'Bad Neighbour'
        WHEN 'BN-IND' THEN 'Bad Neighbour'
        WHEN 'BN-OD' THEN 'Bad Neighbour'
        WHEN 'AJ-GH' THEN 'Apple Jelly'
        WHEN 'AJ-IND' THEN 'Apple Jelly'
        WHEN 'AJ-OD' THEN 'Apple Jelly'
        ELSE product_name
    END,
    ' - ',
    grow_type
);
    </pre>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
?>
