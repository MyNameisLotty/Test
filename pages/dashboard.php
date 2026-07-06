<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

ob_start();

$today = date('Y-m-d');

// Today's orders
$todaysOrders = $conn->query("
    SELECT o.*, c.client_name
    FROM orders o
    JOIN clients c ON c.id = o.client_id
    WHERE DATE(o.order_date) = '$today'
    ORDER BY o.id DESC
");

// Today's tasks (clones, cuttings, general tasks)
$todaysTasks = $conn->query("
    SELECT *
    FROM tasks
    WHERE task_date = '$today'
    ORDER BY id ASC
");

// Today's planting schedule
$todaysPlanting = $conn->query("
    SELECT ps.*, sm.flowering_weeks, sm.ec_curve, sm.pest_spray_interval
    FROM planting_schedule ps
    JOIN strain_metadata sm ON sm.strain = ps.strain
    WHERE ps.scheduled_date = '$today'
    ORDER BY ps.id ASC
");

// Available stock summary
$stockSummary = $conn->query("
    SELECT s.product_name, s.strain_code, s.quantity, s.selling_price, s.category_id AS category_name
    FROM stock s
    ORDER BY s.strain_code, s.product_name
");

$orderCount    = $todaysOrders->num_rows;
$taskCount     = $todaysTasks->num_rows;
$plantingCount = $todaysPlanting->num_rows;
?>

<div class="page-title">
    <div>
        <h2>Dashboard</h2>
        <p>What needs to happen today — <?= date('l, d F Y') ?>.</p>
    </div>
    <a class="button" href="/hvf-app/pages/orders_import.php">Import CSV</a>
</div>

<?php if ($orderCount === 0 && $taskCount === 0 && $plantingCount === 0): ?>
    <div class="card" style="text-align:center; padding: 2.5rem 1rem; color: var(--text-muted, #888);">
        <p style="font-size:1.4rem; margin-bottom:.5rem;">✅ Nothing due today</p>
        <p>No orders, tasks, or planting scheduled for today.</p>
    </div>
<?php endif; ?>

<!-- TODAY'S ORDERS -->
<div class="card">
    <h3>Today's Orders
        <?php if ($orderCount > 0): ?>
            <span class="badge"><?= $orderCount ?></span>
        <?php endif; ?>
    </h3>
    <?php if ($orderCount === 0): ?>
        <p class="empty-state">No orders placed today.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Client</th>
                    <th>Description</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = $todaysOrders->fetch_assoc()): ?>
                    <tr>
                        <td><?= h($order['order_number']) ?></td>
                        <td><?= h($order['client_name']) ?></td>
                        <td><?= h($order['description']) ?></td>
                        <td><?= money($order['total']) ?></td>
                        <td>
                            <span class="status-badge status-<?= strtolower(h($order['status'])) ?>">
                                <?= h($order['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- TODAY'S TASKS -->
<div class="card">
    <h3>Today's Tasks
        <?php if ($taskCount > 0): ?>
            <span class="badge"><?= $taskCount ?></span>
        <?php endif; ?>
    </h3>
    <?php if ($taskCount === 0): ?>
        <p class="empty-state">No tasks scheduled for today.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Details</th>
                    <th>Assigned To</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($task = $todaysTasks->fetch_assoc()): ?>
                    <tr>
                        <td><?= h($task['task']) ?></td>
                        <td><?= h($task['details']) ?></td>
                        <td><?= h($task['assigned_to'] ?? '—') ?></td>
                        <td>
                            <span class="status-badge status-<?= strtolower(h($task['status'])) ?>">
                                <?= h($task['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- TODAY'S PLANTING SCHEDULE -->
<div class="card">
    <h3>Today's Planting Schedule
        <?php if ($plantingCount > 0): ?>
            <span class="badge"><?= $plantingCount ?></span>
        <?php endif; ?>
    </h3>
    <?php if ($plantingCount === 0): ?>
        <p class="empty-state">Nothing on the planting schedule for today.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Strain</th>
                    <th>Action</th>
                    <th>Quantity</th>
                    <th>Stage / Dates / EC / Sprays</th>
                    <th>Status</th>
                    <th>Update</th> <!-- NEW COLUMN -->
                </tr>
            </thead>
            <tbody>
                <?php while ($ps = $todaysPlanting->fetch_assoc()): ?>
                    <?php
                    // Calculate harvest date
                    $plantDate = new DateTime($ps['planted_date'] ?? $ps['scheduled_date']);
                    $harvestDate = clone $plantDate;
                    $harvestDate->modify('+' . $ps['flowering_weeks'] . ' weeks');

                    // EC curve for current week
                    $ec = json_decode($ps['ec_curve'], true);
                    $plantAgeWeeks = $plantDate->diff(new DateTime($today))->days / 7;
                    $currentWeek = 'week' . ceil($plantAgeWeeks);
                    $ecToday = isset($ec[$currentWeek]) ? $ec[$currentWeek] : null;
                    ?>
                    <tr>
                        <td><?= h($ps['strain']) ?></td>
                        <td><?= ucfirst(h($ps['action'])) ?></td>
                        <td><?= (int)$ps['quantity'] ?></td>
                        <td>
                            <strong>Stage:</strong> <?= h($ps['stage'] ?? '—') ?><br>
                            <strong>Planted:</strong> <?= h($ps['planted_date'] ?? '—') ?><br>
                            <strong>Flowered:</strong> <?= h($ps['flower_date'] ?? '—') ?><br>
                            <strong>Harvest:</strong> <?= $harvestDate->format('Y-m-d') ?><br>
                            <?php if ($ecToday): ?>
                                <strong>EC Today:</strong> <?= $ecToday ?><br>
                            <?php endif; ?>
                            <?php
                            // Spray schedule
                            if (preg_match('/(\d+)/', $ps['pest_spray_interval'], $matches)) {
                                $days = (int)$matches[1];
                                $sprayDate = clone $plantDate;
                                while ($sprayDate < $harvestDate) {
                                    echo "Spray on: " . $sprayDate->format('Y-m-d') . "<br>";
                                    $sprayDate->modify("+$days days");
                                }
                            }
                            ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?= strtolower(h($ps['status'])) ?>">
                                <?= h($ps['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="/hvf-app/pages/planting_update.php?id=<?= $ps['id'] ?>" class="button">Update</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>


<!-- AVAILABLE STOCK SUMMARY -->
<div class="card">
    <h3>Available Stock</h3>
    <table>
        <thead>
            <tr>
                <th>Strain</th>
                <th>Product</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($s = $stockSummary->fetch_assoc()): ?>
                <tr>
                    <td><?= h($s['strain_code']) ?></td>
                    <td><?= h($s['product_name']) ?></td>
                    <td><?= h($s['category_name'] ?? 'Unassigned') ?></td>
                    <td><?= number_format((float)$s['quantity'], 2) ?> g</td>
                    <td><?= money($s['selling_price']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
?>
