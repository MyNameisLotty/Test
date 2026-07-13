<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

ob_start();

$today = date('Y-m-d');

// Handle mark-as-done for tasks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_task_done'])) {
    $id = (int)$_POST['mark_task_done'];
    $conn->query("UPDATE tasks SET status = 'done' WHERE id = $id");
    header('Location: /hvf-app/pages/planting_schedule.php');
    exit;
}

// Handle mark-as-done for planting schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_ps_done'])) {
    $id = (int)$_POST['mark_ps_done'];
    $conn->query("UPDATE planting_schedule SET status = 'done' WHERE id = $id");
    header('Location: /hvf-app/pages/planting_schedule.php');
    exit;
}

// Add new task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $task        = $conn->real_escape_string(trim($_POST['task'] ?? ''));
    $details     = $conn->real_escape_string(trim($_POST['details'] ?? ''));
    $assigned_to = $conn->real_escape_string(trim($_POST['assigned_to'] ?? ''));
    $task_date   = $conn->real_escape_string($_POST['task_date'] ?? $today);

    if ($task !== '' && $task_date !== '') {
        $conn->query("
            INSERT INTO tasks (task, details, assigned_to, task_date, status)
            VALUES ('$task', '$details', '$assigned_to', '$task_date', 'pending')
        ");
    }
    header('Location: /hvf-app/pages/planting_schedule.php');
    exit;
}

// Add new planting schedule entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_planting'])) {
    $strain   = $conn->real_escape_string(trim($_POST['strain'] ?? ''));
    $action   = $conn->real_escape_string($_POST['action'] ?? '');
    $date     = $conn->real_escape_string($_POST['scheduled_date'] ?? $today);
    $quantity = (int)($_POST['quantity'] ?? 1);
    $notes    = $conn->real_escape_string(trim($_POST['notes'] ?? ''));

    if ($strain !== '' && $action !== '' && $date !== '') {
        $conn->query("
            INSERT INTO planting_schedule (strain, action, scheduled_date, quantity, notes, status)
            VALUES ('$strain', '$action', '$date', $quantity, '$notes', 'pending')
        ");
    }
    header('Location: /hvf-app/pages/planting_schedule.php');
    exit;
}

// Fetch all upcoming + today tasks
$tasks = $conn->query("
    SELECT * FROM tasks
    WHERE task_date >= '$today'
    ORDER BY task_date ASC, id ASC
");

// Fetch all upcoming + today planting schedule
$schedule = $conn->query("
    SELECT * FROM planting_schedule
    WHERE scheduled_date >= '$today'
    ORDER BY scheduled_date ASC, id ASC
");
?>

<div class="page-title">
    <div>
        <h2>Planting Schedule & Tasks</h2>
        <p>Schedule clones, cuttings, transplants, harvests, and daily tasks.</p>
    </div>
</div>

<!-- ADD TASK FORM -->
<div class="card">
    <h3>Add Task</h3>
    <form method="POST" class="form-grid">
        <input type="hidden" name="add_task" value="1">

        <div class="form-field">
            <label for="task_name">Task</label>
            <select id="task_name" name="task">
                <option value="Take Clones">Take Clones</option>
                <option value="Take Cuttings">Take Cuttings</option>
                <option value="Transplant">Transplant</option>
                <option value="Harvest">Harvest</option>
                <option value="Feed Plants">Feed Plants</option>
                <option value="Flush">Flush</option>
                <option value="Spray">Spray</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div class="form-field">
            <label for="task_details">Details</label>
            <input type="text" id="task_details" name="details" placeholder="e.g. OG Kush — Room 2, 20 cuts">
        </div>

        <div class="form-field">
            <label for="assigned_to">Assigned To</label>
            <input type="text" id="assigned_to" name="assigned_to" placeholder="e.g. Thabo">
        </div>

        <div class="form-field">
            <label for="task_date">Date</label>
            <input type="date" id="task_date" name="task_date" value="<?= $today ?>" required>
        </div>

        <button type="submit">Add Task</button>
    </form>
</div>

<!-- ADD PLANTING SCHEDULE FORM -->
<div class="card">
    <h3>Add Planting Schedule Entry</h3>
    <form method="POST" class="form-grid">
        <input type="hidden" name="add_planting" value="1">

        <div class="form-field">
            <label for="strain">Strain</label>
            <input type="text" id="strain" name="strain" placeholder="e.g. Girl Scout Cookies" required>
        </div>

        <div class="form-field">
            <label for="action">Action</label>
            <select id="action" name="action" required>
                <option value="">Select Action</option>
                <option value="clone">Clone</option>
                <option value="cutting">Cutting</option>
                <option value="transplant">Transplant</option>
                <option value="harvest">Harvest</option>
                <option value="feed">Feed</option>
            </select>
        </div>

        <div class="form-field">
            <label for="scheduled_date">Scheduled Date</label>
            <input type="date" id="scheduled_date" name="scheduled_date" value="<?= $today ?>" required>
        </div>

        <div class="form-field">
            <label for="quantity">Quantity</label>
            <input type="number" id="quantity" name="quantity" value="1" min="1" required>
        </div>

        <div class="form-field">
            <label for="notes">Notes</label>
            <input type="text" id="notes" name="notes" placeholder="e.g. Veg room, top shelf">
        </div>

        <button type="submit">Add to Schedule</button>
    </form>
</div>

<!-- UPCOMING TASKS TABLE -->
<div class="card">
    <h3>Upcoming Tasks</h3>
    <?php if ($tasks->num_rows === 0): ?>
        <p class="empty-state">No tasks scheduled.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Task</th>
                    <th>Details</th>
                    <th>Assigned To</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($t = $tasks->fetch_assoc()): ?>
                    <tr class="<?= $t['task_date'] === $today ? 'row-today' : '' ?>">
                        <td><?= h($t['task_date']) ?></td>
                        <td><?= h($t['task']) ?></td>
                        <td><?= h($t['details'] ?? '—') ?></td>
                        <td><?= h($t['assigned_to'] ?? '—') ?></td>
                        <td>
                            <span class="status-badge status-<?= strtolower(h($t['status'])) ?>">
                                <?= h($t['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($t['status'] === 'pending'): ?>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="mark_task_done" value="<?= (int)$t['id'] ?>">
                                    <button type="submit" class="button-sm">Mark Done</button>
                                </form>
                            <?php else: ?>
                                ✅
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- UPCOMING PLANTING SCHEDULE TABLE -->
<div class="card">
    <h3>Upcoming Planting Schedule</h3>
    <?php if ($schedule->num_rows === 0): ?>
        <p class="empty-state">No planting schedule entries.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Strain</th>
                    <th>Action</th>
                    <th>Quantity</th>
                    <th>Notes</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($ps = $schedule->fetch_assoc()): ?>
                    <tr class="<?= $ps['scheduled_date'] === $today ? 'row-today' : '' ?>">
                        <td><?= h($ps['scheduled_date']) ?></td>
                        <td><?= h($ps['strain']) ?></td>
                        <td><?= ucfirst(h($ps['action'])) ?></td>
                        <td><?= (int)$ps['quantity'] ?></td>
                        <td><?= h($ps['notes'] ?? '—') ?></td>
                        <td>
                            <span class="status-badge status-<?= strtolower(h($ps['status'])) ?>">
                                <?= h($ps['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($ps['status'] === 'pending'): ?>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="mark_ps_done" value="<?= (int)$ps['id'] ?>">
                                    <button type="submit" class="button-sm">Mark Done</button>
                                </form>
                            <?php else: ?>
                                ✅
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
?>
            if (stripos($cName, 'high5') !== false)