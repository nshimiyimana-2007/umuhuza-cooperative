<?php
// pages/clients.php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = 'Clients';
$activePage = 'clients';
$rootPath = '../';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name     = trim($_POST['name'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $location = trim($_POST['location'] ?? '');

        $err = [];
        if (empty($name))     $err[] = 'Name is required.';
        if (empty($phone))    $err[] = 'Phone is required.';
        if (empty($location)) $err[] = 'Location is required.';

        if (empty($err)) {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO clients (name, phone, location) VALUES (?,?,?)");
                $stmt->execute([$name, $phone, $location]);
                setFlash('success', 'Client added successfully.');
            } else {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("UPDATE clients SET name=?, phone=?, location=? WHERE id=?");
                $stmt->execute([$name, $phone, $location, $id]);
                setFlash('success', 'Client updated successfully.');
            }
        } else {
            setFlash('error', implode(' ', $err));
        }
        header('Location: clients.php'); exit();
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM clients WHERE id=?")->execute([$id]);
        setFlash('success', 'Client deleted.');
        header('Location: clients.php'); exit();
    }
}

$editClient = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editClient = $stmt->fetch();
}

$clients = $pdo->query("SELECT * FROM clients ORDER BY name")->fetchAll();

include '../includes/layout.php';
?>

<div class="page-header">
    <h2>🏢 Clients Management</h2>
</div>

<!-- FORM -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <span class="card-title"><?= $editClient ? '✏️ Edit Client' : '➕ Add New Client' ?></span>
        <?php if ($editClient): ?><a href="clients.php" class="btn btn-secondary btn-sm">Cancel</a><?php endif; ?>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="action" value="<?= $editClient ? 'edit' : 'add' ?>">
            <?php if ($editClient): ?><input type="hidden" name="id" value="<?= $editClient['id'] ?>"><?php endif; ?>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Client Name *</label>
                    <input type="text" name="name" class="form-control"
                           placeholder="e.g. Kigali Grain Ltd" required
                           value="<?= e($editClient['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="text" name="phone" class="form-control"
                           placeholder="e.g. 0722000000" required
                           value="<?= e($editClient['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Location *</label>
                    <input type="text" name="location" class="form-control"
                           placeholder="City or address" required
                           value="<?= e($editClient['location'] ?? '') ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $editClient ? '💾 Update Client' : '➕ Add Client' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- TABLE -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Clients List (<?= count($clients) ?>)</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>#</th><th>Name</th><th>Phone</th><th>Location</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($clients)): ?>
                <tr><td colspan="5" class="no-results">No clients found.</td></tr>
            <?php else: ?>
                <?php foreach ($clients as $i => $c): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= e($c['name']) ?></strong></td>
                    <td><?= e($c['phone']) ?></td>
                    <td><?= e($c['location']) ?></td>
                    <td>
                        <div class="actions-cell">
                            <a href="clients.php?edit=<?= $c['id'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                            <form method="POST" action="" onsubmit="return confirm('Delete <?= e($c['name']) ?>?');" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">🗑️ Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/layout_end.php'; ?>
