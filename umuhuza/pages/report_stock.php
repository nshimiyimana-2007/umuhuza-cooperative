<?php
// pages/report_stock.php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = 'Stock Report';
$activePage = 'report_stock';
$rootPath = '../';

$products = $pdo->query("
    SELECT p.*, m.name AS member_name,
           COALESCE((SELECT SUM(s.quantity) FROM sales s WHERE s.product_id = p.id), 0) AS total_sold
    FROM products p
    JOIN members m ON p.member_id = m.id
    ORDER BY p.quantity ASC
")->fetchAll();

$totalStock     = array_sum(array_column($products, 'quantity'));
$totalSold      = array_sum(array_column($products, 'total_sold'));
$outOfStock     = count(array_filter($products, fn($p) => $p['quantity'] == 0));
$lowStock       = count(array_filter($products, fn($p) => $p['quantity'] > 0 && $p['quantity'] < 50));
$totalValue     = array_sum(array_map(fn($p) => $p['quantity'] * $p['price'], $products));

include '../includes/layout.php';
?>

<div class="page-header">
    <h2>📦 Stock / Inventory Report</h2>
    <button onclick="window.print()" class="btn btn-info">🖨️ Print</button>
</div>

<!-- SUMMARY -->
<div class="report-summary">
    <div class="report-item">
        <div class="r-value"><?= number_format($totalStock, 1) ?></div>
        <div class="r-label">Total Stock (kg)</div>
    </div>
    <div class="report-item">
        <div class="r-value"><?= number_format($totalSold, 1) ?></div>
        <div class="r-label">Total Sold (kg)</div>
    </div>
    <div class="report-item">
        <div class="r-value"><?= number_format($totalValue) ?></div>
        <div class="r-label">Stock Value (RWF)</div>
    </div>
    <div class="report-item">
        <div class="r-value" style="color:var(--red);"><?= $outOfStock ?></div>
        <div class="r-label">Out of Stock</div>
    </div>
    <div class="report-item">
        <div class="r-value" style="color:var(--gold);"><?= $lowStock ?></div>
        <div class="r-label">Low Stock (< 50kg)</div>
    </div>
</div>

<!-- TABLE -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Product Inventory (<?= count($products) ?> products)</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>#</th><th>Product Type</th><th>Member</th><th>Current Stock (kg)</th><th>Total Sold (kg)</th><th>Price/kg (RWF)</th><th>Stock Value (RWF)</th><th>Status</th></tr>
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
                    <td><?= number_format($p['total_sold'], 2) ?></td>
                    <td><?= number_format($p['price'], 2) ?></td>
                    <td><?= number_format($p['quantity'] * $p['price'], 2) ?></td>
                    <td>
                        <?php if ($p['quantity'] == 0): ?>
                            <span class="badge badge-danger">Out of Stock</span>
                        <?php elseif ($p['quantity'] < 50): ?>
                            <span class="badge badge-warning">Low Stock</span>
                        <?php else: ?>
                            <span class="badge badge-success">In Stock</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/layout_end.php'; ?>
