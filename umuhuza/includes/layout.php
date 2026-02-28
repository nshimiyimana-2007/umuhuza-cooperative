<?php
// includes/layout.php
// Usage: include this after requireLogin()
// Must define $pageTitle and $activePage before including
if (!isset($pageTitle)) $pageTitle = 'Dashboard';
if (!isset($activePage)) $activePage = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> — UMUHUZA Cooperative</title>
<link rel="stylesheet" href="<?= $rootPath ?? '../' ?>css/style.css">
</head>
<body>
<div class="layout">

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">🌽</div>
        <h2>UMUHUZA</h2>
        <p>Cooperative Management</p>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <a href="<?= $rootPath ?? '../' ?>pages/dashboard.php" class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">
            <span class="nav-icon">📊</span> Dashboard
        </a>

        <div class="nav-section-label">Management</div>
        <a href="<?= $rootPath ?? '../' ?>pages/members.php" class="nav-link <?= $activePage === 'members' ? 'active' : '' ?>">
            <span class="nav-icon">👥</span> Members
        </a>
        <a href="<?= $rootPath ?? '../' ?>pages/products.php" class="nav-link <?= $activePage === 'products' ? 'active' : '' ?>">
            <span class="nav-icon">🌾</span> Products
        </a>
        <a href="<?= $rootPath ?? '../' ?>pages/clients.php" class="nav-link <?= $activePage === 'clients' ? 'active' : '' ?>">
            <span class="nav-icon">🏢</span> Clients
        </a>
        <a href="<?= $rootPath ?? '../' ?>pages/sales.php" class="nav-link <?= $activePage === 'sales' ? 'active' : '' ?>">
            <span class="nav-icon">🛒</span> Sales
        </a>

        <div class="nav-section-label">Reports</div>
        <a href="<?= $rootPath ?? '../' ?>pages/report_sales.php" class="nav-link <?= $activePage === 'report_sales' ? 'active' : '' ?>">
            <span class="nav-icon">📈</span> Sales Report
        </a>
        <a href="<?= $rootPath ?? '../' ?>pages/report_stock.php" class="nav-link <?= $activePage === 'report_stock' ? 'active' : '' ?>">
            <span class="nav-icon">📦</span> Stock Report
        </a>
        <a href="<?= $rootPath ?? '../' ?>pages/report_members.php" class="nav-link <?= $activePage === 'report_members' ? 'active' : '' ?>">
            <span class="nav-icon">🌿</span> Member Report
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-info">
            <div class="admin-avatar"><?= strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)) ?></div>
            <div class="admin-details">
                <strong><?= e($_SESSION['admin_username'] ?? 'Admin') ?></strong>
                <span>Administrator</span>
            </div>
        </div>
        <a href="<?= $rootPath ?? '../' ?>logout.php" class="btn-logout">🚪 Logout</a>
    </div>
</aside>

<!-- MAIN -->
<div class="main">
    <div class="topbar">
        <div>
            <div class="topbar-title"><?= e($pageTitle) ?></div>
            <div class="topbar-meta">UMUHUZA Cooperative — Eastern Province, Rwanda</div>
        </div>
        <div class="topbar-meta"><?= date('D, d M Y') ?></div>
    </div>
    <div class="page-content">
        <?php displayFlash(); ?>
