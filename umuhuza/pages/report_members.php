<?php
// pages/report_members.php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

$pageTitle = 'Member Report';
$activePage = 'report_members';
$rootPath = '../';

$members = $pdo->query("
    SELECT m.*,
        COALESCE(SUM(p.quantity), 0) AS total_stock,
        COUNT(DISTINCT p.id) AS product_count,
        COALESCE(SUM(s.quantity), 0) AS total_sold,
        COALESCE(SUM(s.total), 0) AS total_revenue
    FROM members m
    LEFT JOIN products p ON p.member_id = m.id
    LEFT JOIN sales s ON s.product_id = p.id
    GROUP BY m.id
    ORDER BY total_revenue DESC
")->fetchAll();

$grandRevenue = array_sum(array_column($members, 'total_revenue'));
$grandStock   = array_sum(array_column($members, 'total_stock'));
$grandSold    = array_sum(array_column($members, 'total_sold'));

include '../includes/layout.php';
?>

<div class="page-header">
    <h2>🌿 Member Contributions Report</h2>
    <button onclick="window.print()" class="btn btn-info">🖨️ Print</button>
</div>

<!-- SUMMARY -->
<div class="report-summary">
    <div class="report-item">
        <div class="r-value"><?= count($members) ?></div>
        <div class="r-label">Total Members</div>
    </div>
    <div class="report-item">
        <div class="r-value"><?= number_format($grandStock, 1) ?></div>
        <div class="r-label">Total Stock Held (kg)</div>
    </div>
    <div class="report-item">
        <div class="r-value"><?= number_format($grandSold, 1) ?></div>
        <div class="r-label">Total Sold (kg)</div>
    </div>
    <div class="report-item">
        <div class="r-value"><?= number_format($grandRevenue) ?></div>
        <div class="r-label">Total Revenue (RWF)</div>
    </div>
</div>

<!-- TABLE -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Member Contributions</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>#</th><th>Member</th><th>Phone</th><th>Village</th><th>Join Date</th><th>Products</th><th>Stock (kg)</th><th>Sold (kg)</th><th>Revenue (RWF)</th><th>Share %</th></tr>
            </thead>
            <tbody>
            <?php if (empty($members)): ?>
                <tr><td colspan="10" class="no-results">No members found.</td></tr>
            <?php else: ?>
                <?php foreach ($members as $i => $m): ?>
                <?php $share = $grandRevenue > 0 ? ($m['total_revenue'] / $grandRevenue * 100) : 0; ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= e($m['name']) ?></strong></td>
                    <td><?= e($m['phone']) ?></td>
                    <td><?= e($m['village']) ?></td>
                    <td><?= e($m['join_date']) ?></td>
                    <td><?= $m['product_count'] ?></td>
                    <td><?= number_format($m['total_stock'], 2) ?></td>
                    <td><?= number_format($m['total_sold'], 2) ?></td>
                    <td><strong><?= number_format($m['total_revenue'], 2) ?></strong></td>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div style="flex:1; background:var(--gray-100); border-radius:4px; height:8px; min-width:60px;">
                                <div style="width:<?= min(100, round($share)) ?>%; background:var(--green); height:100%; border-radius:4px;"></div>
                            </div>
                            <?= number_format($share, 1) ?>%
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr style="background:var(--green-pale); font-weight:700;">
                    <td colspan="6" style="text-align:right;">TOTALS:</td>
                    <td><?= number_format($grandStock, 2) ?></td>
                    <td><?= number_format($grandSold, 2) ?></td>
                    <td><?= number_format($grandRevenue, 2) ?></td>
                    <td>100%</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/layout_end.php'; ?>
