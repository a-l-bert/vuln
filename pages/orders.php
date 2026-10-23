<?php
require_once __DIR__ . '/../config.php';
$pdo = get_db();

if (isset($_GET['approve'])) {
    $order_id = (int)$_GET['approve'];
    $pdo->prepare("UPDATE orders SET status = 'approved' WHERE id = ?")->execute([$order_id]);
}

$orders = $pdo->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<h3>Orders</h3>
<p class="admin-dashboard-intro">Review purchase activity and approve orders awaiting processing.</p>
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php if (empty($orders)): ?>
            <tr><td colspan="5" class="admin-empty">No orders have been placed yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $o): ?>
        <tr>
            <td><strong>#<?= htmlspecialchars($o['id']) ?></strong></td>
            <td><?= htmlspecialchars($o['username']) ?></td>
            <td>$<?= htmlspecialchars((float)$o['total']) ?></td>
            <td><span class="admin-badge <?= $o['status'] === 'pending' ? 'pending' : 'approved' ?>"><?= htmlspecialchars(ucfirst($o['status'])) ?></span></td>
            <td>
                <?php if ($o['status'] === 'pending'): ?>
                    <a href="/admin.php?page=orders.php&amp;approve=<?= (int)$o['id'] ?>">Approve order</a>
                <?php else: ?>
                    <span aria-label="No action required">None</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
