<?php
// pages/products.php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = 'Products';
$activePage = 'products';
$rootPath = '../';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $member_id = (int)$_POST['member_id'];
        $quantity  = (float)$_POST['quantity'];
        $price     = (float)$_POST['price'];
        $type      = trim($_POST['type'] ?? '');

        $err = [];
        if ($member_id <= 0) $err[] = 'Select a member.';
        if ($quantity <= 0)  $err[] = 'Quantity must be greater than 0.';
        if ($price <= 0)     $err[] = 'Price must be greater than 0.';
        if (empty($type))    $err[] = 'Product type is required.';

        if (empty($err)) {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO products (member_id, quantity, price, type) VALUES (?,?,?,?)");
                $stmt->execute([$member_id, $quantity, $price, $type]);
                setFlash('success', 'Product added successfully.');
            } else {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("UPDATE products SET member_id=?, quantity=?, price=?, type=? WHERE id=?");
                $stmt->execute([$member_id, $quantity, $price, $type, $id]);
                setFlash('success', 'Product updated successfully.');
            }
        } else {
            setFlash('error', implode(' ', $err));
        }
        header('Location: products.php'); exit();
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
        setFlash('success', 'Product deleted.');
        header('Location: products.php'); exit();
    }
}

$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editProduct = $stmt->fetch();
}

$members = $pdo->query("SELECT id, name FROM members ORDER BY name")->fetchAll();
$products = $pdo->query("
    SELECT p.*, m.name AS member_name
    FROM products p JOIN members m ON p.member_id = m.id
    ORDER BY p.created_at DESC
")->fetchAll();

include '../includes/layout.php';
?>

<div class="page-header">
    <h2>🌾 Products Management</h2>
</div>

<!-- FORM -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <span class="card-title"><?= $editProduct ? '✏️ Edit Product' : '➕ Add New Product' ?></span>
        <?php if ($editProduct): ?><a href="products.php" class="btn btn-secondary btn-sm">Cancel</a><?php endif; ?>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="action" value="<?= $editProduct ? 'edit' : 'add' ?>">
            <?php if ($editProduct): ?><input type="hidden" name="id" value="<?= $editProduct['id'] ?>"><?php endif; ?>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Member *</label>
                    <select name="member_id" class="form-select" required>
                        <option value="">— Select Member —</option>
                        <?php foreach ($members as $m): ?>
                        <option value="<?= $m['id'] ?>"
                            <?= ($editProduct && $editProduct['member_id'] == $m['id']) ? 'selected' : '' ?>>
                            <?= e($m['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Product Type *</label>
                    <input type="text" name="type" class="form-control"
                           placeholder="e.g. Yellow Maize" required
                           value="<?= e($editProduct['type'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Quantity (kg) *</label>
                    <input type="number" step="0.01" name="quantity" class="form-control"
                           placeholder="0.00" required min="0.01"
                           value="<?= e($editProduct['quantity'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Price per kg (RWF) *</label>
                    <input type="number" step="0.01" name="price" class="form-control"
                           placeholder="0.00" required min="0.01"
                           value="<?= e($editProduct['price'] ?? '') ?>">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $editProduct ? '💾 Update Product' : '➕ Add Product' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- TABLE -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Products List (<?= count($products) ?>)</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>#</th><th>Type</th><th>Member</th><th>Quantity (kg)</th><th>Price/kg (RWF)</th><th>Total Value</th><th>Stock Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="8" class="no-results">No products found.</td></tr>
            <?php else: ?>
                <?php foreach ($products as $i => $p): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= e($p['type']) ?></strong></td>
                    <td><?= e($p['member_name']) ?></td>
                    <td><?= number_format($p['quantity'], 2) ?></td>
                    <td><?= number_format($p['price'], 2) ?></td>
                    <td><?= number_format($p['quantity'] * $p['price'], 2) ?></td>
                    <td>
                        <?php if ($p['quantity'] == 0): ?>
                            <span class="badge badge-danger stock-out">Out of Stock</span>
                        <?php elseif ($p['quantity'] < 50): ?>
                            <span class="badge badge-warning stock-low">Low Stock</span>
                        <?php else: ?>
                            <span class="badge badge-success stock-ok">In Stock</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="actions-cell">
                            <a href="products.php?edit=<?= $p['id'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                            <form method="POST" action="" onsubmit="return confirm('Delete this product?');" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
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
