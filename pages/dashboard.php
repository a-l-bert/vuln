<?php
require_once __DIR__ . '/../config.php';
$pdo = get_db();
$totalProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE is_admin = 0')->fetchColumn();
$pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$totalOrders = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
?>
<h2>Store overview</h2>
<p class="admin-dashboard-intro">A quick snapshot of your shop. Use the sections above to update the catalog, support customers, and process purchases.</p>
<div class="admin-stat-grid" aria-label="Store summary">
    <article class="admin-stat">
        <span class="admin-stat-label">Products</span>
        <strong class="admin-stat-value"><?= $totalProducts ?></strong>
    </article>
    <article class="admin-stat">
        <span class="admin-stat-label">Customers</span>
        <strong class="admin-stat-value"><?= $totalUsers ?></strong>
    </article>
    <article class="admin-stat">
        <span class="admin-stat-label">All orders</span>
        <strong class="admin-stat-value"><?= $totalOrders ?></strong>
    </article>
    <article class="admin-stat">
        <span class="admin-stat-label">Awaiting approval</span>
        <strong class="admin-stat-value"><?= $pendingOrders ?></strong>
    </article>
</div>
<section class="admin-panel">
    <h4>Quick actions</h4>
    <div class="admin-actions">
        <a class="button" href="/admin.php?page=products.php">Manage products</a>
        <a class="button" href="/admin.php?page=orders.php">Review orders<?= $pendingOrders > 0 ? ' (' . $pendingOrders . ')' : '' ?></a>
        <a class="button" href="/admin.php?page=users.php">View customers</a>
    </div>
</section>
