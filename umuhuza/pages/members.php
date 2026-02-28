<?php
// pages/members.php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = 'Members';
$activePage = 'members';
$rootPath = '../';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name     = trim($_POST['name'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $village  = trim($_POST['village'] ?? '');
        $joinDate = $_POST['join_date'] ?? '';

        $err = [];
        if (empty($name))    $err[] = 'Name is required.';
        if (empty($phone))   $err[] = 'Phone is required.';
        if (empty($village)) $err[] = 'Village is required.';
        if (empty($joinDate)) $err[] = 'Join date is required.';

        if (empty($err)) {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO members (name, phone, village, join_date) VALUES (?,?,?,?)");
                $stmt->execute([$name, $phone, $village, $joinDate]);
                setFlash('success', 'Member added successfully.');
            } else {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("UPDATE members SET name=?, phone=?, village=?, join_date=? WHERE id=?");
                $stmt->execute([$name, $phone, $village, $joinDate, $id]);
                setFlash('success', 'Member updated successfully.');
            }
        } else {
            setFlash('error', implode(' ', $err));
        }
        header('Location: members.php'); exit();
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM members WHERE id=?")->execute([$id]);
        setFlash('success', 'Member deleted.');
        header('Location: members.php'); exit();
    }
}

// Fetch for edit
$editMember = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editMember = $stmt->fetch();
}

// Search
$search = trim($_GET['search'] ?? '');
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE name LIKE ? OR phone LIKE ? OR village LIKE ? ORDER BY name");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM members ORDER BY name");
}
$members = $stmt->fetchAll();

include '../includes/layout.php';
?>

<div class="page-header">
    <h2>👥 Members Management</h2>
    <a href="members.php" class="btn btn-primary">➕ Add Member</a>
</div>

<!-- ADD / EDIT FORM -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <span class="card-title"><?= $editMember ? '✏️ Edit Member' : '➕ Add New Member' ?></span>
        <?php if ($editMember): ?><a href="members.php" class="btn btn-secondary btn-sm">Cancel</a><?php endif; ?>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="action" value="<?= $editMember ? 'edit' : 'add' ?>">
            <?php if ($editMember): ?><input type="hidden" name="id" value="<?= $editMember['id'] ?>"><?php endif; ?>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-control"
                           placeholder="e.g. Jean Baptiste" required
                           value="<?= e($editMember['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="text" name="phone" class="form-control"
                           placeholder="e.g. 0788000000" required
                           value="<?= e($editMember['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Village *</label>
                    <input type="text" name="village" class="form-control"
                           placeholder="Village name" required
                           value="<?= e($editMember['village'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Join Date *</label>
                    <input type="date" name="join_date" class="form-control" required
                           value="<?= e($editMember['join_date'] ?? date('Y-m-d')) ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $editMember ? '💾 Update Member' : '➕ Add Member' ?>
                </button>
                <?php if ($editMember): ?><a href="members.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- SEARCH + TABLE -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Members List (<?= count($members) ?>)</span>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="search-bar">
            <input type="text" name="search" class="search-input"
                   placeholder="🔍 Search by name, phone or village..."
                   value="<?= e($search) ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($search): ?><a href="members.php" class="btn btn-secondary">Clear</a><?php endif; ?>
        </form>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th><th>Name</th><th>Phone</th><th>Village</th><th>Join Date</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($members)): ?>
                    <tr><td colspan="6" class="no-results">No members found.</td></tr>
                <?php else: ?>
                    <?php foreach ($members as $i => $m): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= e($m['name']) ?></strong></td>
                        <td><?= e($m['phone']) ?></td>
                        <td><?= e($m['village']) ?></td>
                        <td><?= e($m['join_date']) ?></td>
                        <td>
                            <div class="actions-cell">
                                <a href="members.php?edit=<?= $m['id'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                                <form method="POST" action="" onsubmit="return confirm('Delete <?= e($m['name']) ?>?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
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
</div>

<?php include '../includes/layout_end.php'; ?>
