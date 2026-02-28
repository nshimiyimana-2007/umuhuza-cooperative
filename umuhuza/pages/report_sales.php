<?php
// pages/report_sales.php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = 'Sales Report';
$activePage = 'report_sales';
$rootPath = '../';

$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT s.*, c.name AS client_name, c.location AS client_location,
           p.type AS product_type, p.price AS unit_price,
           m.name AS member_name
    FROM sales s
    JOIN clients c ON s.client_id = c.id
    JOIN products p ON s.product_id = p.id
    JOIN members m ON p.member_id = m.id
    WHERE s.date BETWEEN ? AND ?
    ORDER BY s.date DESC
");
$stmt->execute([$dateFrom, $dateTo]);
$sales = $stmt->fetchAll();

$totalRevenue = array_sum(array_column($sales, 'total'));
$totalQty     = array_sum(array_column($sales, 'quantity'));
$totalTrans   = count($sales);

include '../includes/layout.php';
?>

<div class="page-header">
    <h2>📈 Sales Report</h2>
    <button onclick="window.print()" class="btn btn-info">🖨️ Print</button>
</div>

<!-- DATE FILTER -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-header"><span class="card-title">📅 Date Range Filter</span></div>
    <div class="card-body">
        <form method="GET" action="" style="display:flex; gap:16px; align-items:flex-end; flex-wrap:wrap;">
            <div class="form-group">
                <label class="form-label">From Date</label>
                <input type="date" name="date_from" class="form-control" value="<?= e($dateFrom) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">To Date</label>
                <input type="date" name="date_to" class="form-control" value="<?= e($dateTo) ?>">
            </div>
            <div>
                <button type="submit" class="btn btn-primary">🔍 Generate Report</button>
                <a href="report_sales.php" class="btn btn-secondary" style="margin-left:8px;">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- SUMMARY -->
<div class="report-summary">
    <div class="report-item">
        <div class="r-value"><?= $totalTrans ?></div>
        <div class="r-label">Total Transactions</div>
    </div>
    <div class="report-item">
        <div class="r-value"><?= number_format($totalQty, 1) ?></div>
        <div class="r-label">Total Qty Sold (kg)</div>
    </div>
    <div class="report-item">
        <div class="r-value"><?= number_format($totalRevenue) ?></div>
        <div class="r-label">Total Revenue (RWF)</div>
    </div>
    <div class="report-item">
        <div class="r-value"><?= $totalTrans > 0 ? number_format($totalRevenue / $totalTrans) : 0 ?></div>
        <div class="r-label">Avg Transaction (RWF)</div>
    </div>
</div>

<!-- TABLE -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Sales from <?= e($dateFrom) ?> to <?= e($dateTo) ?></span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>#</th><th>Date</th><th>Client</th><th>Location</th><th>Product</th><th>Member</th><th>Unit Price</th><th>Qty (kg)</th><th>Total (RWF)</th></tr>
            </thead>
            <tbody>
            <?php if (empty($sales)): ?>
                <tr><td colspan="9" class="no-results">No sales in this date range.</td></tr>
            <?php else: ?>
                <?php foreach ($sales as $i => $s): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($s['date']) ?></td>
                    <td><?= e($s['client_name']) ?></td>
                    <td><?= e($s['client_location']) ?></td>
                    <td><?= e($s['product_type']) ?></td>
                    <td><?= e($s['member_name']) ?></td>
                    <td><?= number_format($s['unit_price'], 2) ?></td>
                    <td><?= number_format($s['quantity'], 2) ?></td>
                    <td><strong><?= number_format($s['total'], 2) ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <tr style="background:var(--green-pale); font-weight:700;">
                    <td colspan="7" style="text-align:right;">TOTALS:</td>
                    <td><?= number_format($totalQty, 2) ?></td>
                    <td><?= number_format($totalRevenue, 2) ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/layout_end.php'; ?>
