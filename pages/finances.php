<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

ob_start();

$income = (float)$conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM finances WHERE type = 'Income'")->fetch_assoc()['total'];
$expenses = (float)$conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM finances WHERE type = 'Expense'")->fetch_assoc()['total'];
$transactions = $conn->query("SELECT * FROM finances ORDER BY transaction_date DESC, id DESC");
?>

<div class="page-title">
    <div>
        <h2>Finances</h2>
        <p>Track imported income and manual expenses.</p>
    </div>
</div>

<div class="metrics-grid three">
    <div class="metric"><span>Income</span><strong><?= money($income) ?></strong></div>
    <div class="metric"><span>Expenses</span><strong><?= money($expenses) ?></strong></div>
    <div class="metric"><span>Net</span><strong><?= money($income - $expenses) ?></strong></div>
</div>

<div class="card">
    <h3>Add Transaction</h3>
    <form method="POST" action="/hvf-app/api/finances_create.php" class="form-grid">
        <input type="date" name="transaction_date" value="<?= date('Y-m-d') ?>" required>
        <select name="type" required>
            <option value="Income">Income</option>
            <option value="Expense">Expense</option>
        </select>
        <input type="number" step="0.01" name="amount" placeholder="Amount" required>
        <input type="text" name="description" placeholder="Description" required>
        <button type="submit">Save Transaction</button>
    </form>
</div>

<div class="card">
    <h3>Transaction History</h3>
    <table>
        <tr><th>Date</th><th>Description</th><th>Type</th><th>Amount</th></tr>
        <?php while ($row = $transactions->fetch_assoc()): ?>
            <tr>
                <td><?= h($row['transaction_date']) ?></td>
                <td><?= h($row['description']) ?></td>
                <td><?= h($row['type']) ?></td>
                <td><?= money($row['amount']) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
?>
