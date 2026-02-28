<?php
// pages/dashboard.php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$rootPath = '../';

// Stats
$members_count  = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
$products_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$clients_count  = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$sales_count    = $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();
$total_revenue  = $pdo->query("SELECT COALESCE(SUM(total),0) FROM sales")->fetchColumn();
$total_stock    = $pdo->query("SELECT COALESCE(SUM(quantity),0) FROM products")->fetchColumn();

// Recent sales
$recent_sales = $pdo->query("
    SELECT s.*, c.name AS client_name, p.type AS product_type
    FROM sales s
    JOIN clients c ON s.client_id = c.id
    JOIN products p ON s.product_id = p.id
    ORDER BY s.created_at DESC LIMIT 8
")->fetchAll();

// Low stock
$low_stock = $pdo->query("
    SELECT p.*, m.name AS member_name
    FROM products p JOIN members m ON p.member_id = m.id
    WHERE p.quantity < 50
    ORDER BY p.quantity ASC LIMIT 5
")->fetchAll();

include '../includes/layout.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($members_count) ?></div>
            <div class="stat-label">Total Members</div>
        </div>
    </div>
    <div class="stat-card gold">
        <div class="stat-icon">🌾</div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($total_stock, 1) ?></div>
            <div class="stat-label">Total Stock (kg)</div>
        </div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon">🏢</div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($clients_count) ?></div>
            <div class="stat-label">Total Clients</div>
        </div>
    </div>
    <div class="stat-card purple">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($total_revenue) ?></div>
            <div class="stat-label">Revenue (RWF)</div>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; flex-wrap:wrap;">

    <div class="card">
        <div class="card-header">
            <span class="card-title">Recent Sales</span>
            <a href="sales.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Client</th><th>Product</th><th>Qty</th><th>Total (RWF)</th><th>Date</th></tr>
                </thead>
                <tbody>
                <?php if (empty($recent_sales)): ?>
                    <tr><td colspan="5" class="no-results">No sales recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recent_sales as $s): ?>
                    <tr>
                        <td><?= e($s['client_name']) ?></td>
                        <td><?= e($s['product_type']) ?></td>
                        <td><?= number_format($s['quantity'], 1) ?> kg</td>
                        <td><?= number_format($s['total']) ?></td>
                        <td><?= e($s['date']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">⚠️ Low Stock Products</span>
            <a href="products.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Type</th><th>Member</th><th>Stock (kg)</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php if (empty($low_stock)): ?>
                    <tr><td colspan="4" class="no-results" style="color:var(--green);">✓ All stock levels OK</td></tr>
                <?php else: ?>
                    <?php foreach ($low_stock as $p): ?>
                    <tr>
                        <td><?= e($p['type']) ?></td>
                        <td><?= e($p['member_name']) ?></td>
                        <td><?= number_format($p['quantity'], 1) ?></td>
                        <td>
                            <?php if ($p['quantity'] == 0): ?>
                                <span class="badge badge-danger">Out of Stock</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Low Stock</span>
                            <?php endif; ?>
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
