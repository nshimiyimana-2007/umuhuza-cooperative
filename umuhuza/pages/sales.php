<?php
// pages/sales.php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = 'Sales';
$activePage = 'sales';
$rootPath = '../';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $client_id  = (int)$_POST['client_id'];
        $product_id = (int)$_POST['product_id'];
        $quantity   = (float)$_POST['quantity'];
        $date       = $_POST['date'] ?? date('Y-m-d');

        $err = [];
        if ($client_id <= 0)  $err[] = 'Select a client.';
        if ($product_id <= 0) $err[] = 'Select a product.';
        if ($quantity <= 0)   $err[] = 'Quantity must be greater than 0.';

        if (empty($err)) {
            // Get product price and current stock
            $prod = $pdo->prepare("SELECT price, quantity FROM products WHERE id=?");
            $prod->execute([$product_id]);
            $product = $prod->fetch();

            if (!$product) {
                setFlash('error', 'Product not found.');
            } else {
                $total = $quantity * $product['price'];

                if ($action === 'add') {
                    // Check stock
                    if ($quantity > $product['quantity']) {
                        setFlash('error', 'Insufficient stock. Available: ' . number_format($product['quantity'], 2) . ' kg');
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO sales (client_id, product_id, quantity, total, date) VALUES (?,?,?,?,?)");
                        $stmt->execute([$client_id, $product_id, $quantity, $total, $date]);
                        // Deduct stock
                        $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id=?")->execute([$quantity, $product_id]);
                        setFlash('success', 'Sale recorded and stock updated.');
                    }
                } else {
                    $id = (int)$_POST['id'];
                    // Get old sale to restore stock
                    $old = $pdo->prepare("SELECT * FROM sales WHERE id=?");
                    $old->execute([$id]);
                    $oldSale = $old->fetch();

                    if ($oldSale) {
                        // Restore old stock
                        $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id=?")->execute([$oldSale['quantity'], $oldSale['product_id']]);
                        // Re-fetch current stock (after restore)
                        $prod2 = $pdo->prepare("SELECT quantity FROM products WHERE id=?");
                        $prod2->execute([$product_id]);
                        $currentQty = $prod2->fetchColumn();
                        if ($quantity > $currentQty) {
                            // Rollback restore
                            $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id=?")->execute([$oldSale['quantity'], $oldSale['product_id']]);
                            setFlash('error', 'Insufficient stock after restore. Available: ' . number_format($currentQty, 2) . ' kg');
                        } else {
                            $stmt = $pdo->prepare("UPDATE sales SET client_id=?, product_id=?, quantity=?, total=?, date=? WHERE id=?");
                            $stmt->execute([$client_id, $product_id, $quantity, $total, $date, $id]);
                            // Deduct new stock
                            $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id=?")->execute([$quantity, $product_id]);
                            setFlash('success', 'Sale updated and stock adjusted.');
                        }
                    }
                }
            }
        } else {
            setFlash('error', implode(' ', $err));
        }
        header('Location: sales.php'); exit();
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        // Restore stock
        $old = $pdo->prepare("SELECT * FROM sales WHERE id=?");
        $old->execute([$id]);
        $oldSale = $old->fetch();
        if ($oldSale) {
            $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id=?")->execute([$oldSale['quantity'], $oldSale['product_id']]);
        }
        $pdo->prepare("DELETE FROM sales WHERE id=?")->execute([$id]);
        setFlash('success', 'Sale deleted and stock restored.');
        header('Location: sales.php'); exit();
    }
}

$editSale = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM sales WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editSale = $stmt->fetch();
}

$clients  = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll();
$products = $pdo->query("SELECT p.id, p.type, p.quantity, p.price, m.name AS member_name FROM products p JOIN members m ON p.member_id=m.id ORDER BY p.type")->fetchAll();

$sales = $pdo->query("
    SELECT s.*, c.name AS client_name, p.type AS product_type, m.name AS member_name
    FROM sales s
    JOIN clients c ON s.client_id = c.id
    JOIN products p ON s.product_id = p.id
    JOIN members m ON p.member_id = m.id
    ORDER BY s.date DESC, s.created_at DESC
")->fetchAll();

include '../includes/layout.php';
?>

<div class="page-header">
    <h2>🛒 Sales Management</h2>
</div>

<!-- FORM -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <span class="card-title"><?= $editSale ? '✏️ Edit Sale' : '➕ Record New Sale' ?></span>
        <?php if ($editSale): ?><a href="sales.php" class="btn btn-secondary btn-sm">Cancel</a><?php endif; ?>
    </div>
    <div class="card-body">
        <form method="POST" action="" id="saleForm">
            <input type="hidden" name="action" value="<?= $editSale ? 'edit' : 'add' ?>">
            <?php if ($editSale): ?><input type="hidden" name="id" value="<?= $editSale['id'] ?>"><?php endif; ?>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Client *</label>
                    <select name="client_id" class="form-select" required>
                        <option value="">— Select Client —</option>
                        <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id'] ?>"
                            <?= ($editSale && $editSale['client_id'] == $c['id']) ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Product *</label>
                    <select name="product_id" class="form-select" id="productSelect" required>
                        <option value="">— Select Product —</option>
                        <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>"
                                data-price="<?= $p['price'] ?>"
                                data-stock="<?= $p['quantity'] ?>"
                            <?= ($editSale && $editSale['product_id'] == $p['id']) ? 'selected' : '' ?>>
                            <?= e($p['type']) ?> (<?= e($p['member_name']) ?>) — Stock: <?= number_format($p['quantity'], 1) ?> kg
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Quantity (kg) *</label>
                    <input type="number" step="0.01" name="quantity" id="qtyInput" class="form-control"
                           placeholder="0.00" required min="0.01"
                           value="<?= e($editSale['quantity'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Date *</label>
                    <input type="date" name="date" class="form-control" required
                           value="<?= e($editSale['date'] ?? date('Y-m-d')) ?>">
                </div>
            </div>
            <div style="margin-top:16px; padding:14px; background:var(--green-pale); border-radius:var(--radius-sm); border:1px solid var(--green-mid);">
                <strong>Price per kg:</strong> <span id="priceDisplay">—</span> RWF &nbsp;|&nbsp;
                <strong>Total Amount:</strong> <span id="totalDisplay" style="font-size:18px; font-weight:700; color:var(--green);">—</span> RWF
                <input type="hidden" name="price" id="priceHidden">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $editSale ? '💾 Update Sale' : '🛒 Record Sale' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- TABLE -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Sales Transactions (<?= count($sales) ?>)</span>
        <?php
        $grandTotal = array_sum(array_column($sales, 'total'));
        ?>
        <span class="badge badge-success">Total: <?= number_format($grandTotal) ?> RWF</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>#</th><th>Date</th><th>Client</th><th>Product</th><th>Member</th><th>Qty (kg)</th><th>Total (RWF)</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($sales)): ?>
                <tr><td colspan="8" class="no-results">No sales recorded yet.</td></tr>
            <?php else: ?>
                <?php foreach ($sales as $i => $s): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($s['date']) ?></td>
                    <td><?= e($s['client_name']) ?></td>
                    <td><?= e($s['product_type']) ?></td>
                    <td><?= e($s['member_name']) ?></td>
                    <td><?= number_format($s['quantity'], 2) ?></td>
                    <td><strong><?= number_format($s['total'], 2) ?></strong></td>
                    <td>
                        <div class="actions-cell">
                            <a href="sales.php?edit=<?= $s['id'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                            <form method="POST" action="" onsubmit="return confirm('Delete this sale? Stock will be restored.');" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
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

<script>
function calcTotal() {
    const sel = document.getElementById('productSelect');
    const opt = sel.options[sel.selectedIndex];
    const qty = parseFloat(document.getElementById('qtyInput').value) || 0;
    if (opt && opt.dataset.price) {
        const price = parseFloat(opt.dataset.price);
        const total = price * qty;
        document.getElementById('priceDisplay').textContent = price.toLocaleString();
        document.getElementById('totalDisplay').textContent = total.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
        document.getElementById('priceHidden').value = price;
    } else {
        document.getElementById('priceDisplay').textContent = '—';
        document.getElementById('totalDisplay').textContent = '—';
    }
}
document.getElementById('productSelect').addEventListener('change', calcTotal);
document.getElementById('qtyInput').addEventListener('input', calcTotal);
window.addEventListener('load', calcTotal);
</script>

<?php include '../includes/layout_end.php'; ?>
