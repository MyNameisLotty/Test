<?php
include __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/functions.php';

ob_start();
$result = $conn->query("SELECT * FROM clients ORDER BY client_name ASC");
?>

<div class="page-title">
    <div>
        <h2>Clients</h2>
        <p>Manage shops, contacts, and billing details.</p>
    </div>
</div>

<div class="card">
    <h3>Add Client</h3>
    <form method="POST" action="/hvf-app/api/clients_create.php" class="form-grid">

        <div class="form-field">
            <label for="client_name">Client Name <span class="required">*</span></label>
            <input type="text" id="client_name" name="client_name" placeholder="e.g. Green Leaf Shop" required>
        </div>

        <div class="form-field">
            <label for="contact_person">Contact Person</label>
            <input type="text" id="contact_person" name="contact_person" placeholder="e.g. John Smith">
        </div>

        <div class="form-field">
            <label for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone" placeholder="e.g. 071 234 5678">
        </div>

        <div class="form-field">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="e.g. john@greenshop.co.za">
        </div>

        <div class="form-field">
            <label for="address">Address</label>
            <textarea id="address" name="address" placeholder="e.g. 12 Main St, Cape Town, 8001"></textarea>
        </div>

        <button type="submit">Save Client</button>
    </form>
</div>

<div class="card">
    <h3>Client List</h3>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Contact</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= h($row['client_name']) ?></td>
                    <td><?= h($row['contact_person']) ?></td>
                    <td><?= h($row['phone']) ?></td>
                    <td><?= h($row['email']) ?></td>
                    <td class="actions">
                        <a href="/hvf-app/pages/client_edit.php?id=<?= h($row['id']) ?>">Edit</a>
                        <a href="/hvf-app/api/clients_delete.php?id=<?= h($row['id']) ?>"
                           onclick="return confirm('Delete this client?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
?>
