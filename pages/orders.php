<?php
require_once __DIR__ . '/../config.php';
$pdo = get_db();

if (isset($_GET['approve'])) {
    $order_id = (int)$_GET['approve'];
    $pdo->prepare("UPDATE orders SET status = 'approved' WHERE id = ?")->execute([$order_id]);
}

$orders = $pdo->query("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<h3>Manage Orders</h3>
<table>
    <tr><th>ID</th><th>Customer</th><th>Total</th><th>Status</th><th>Action</th></tr>
    <?php foreach ($orders as $o): ?>
    <tr>
        <td><?= htmlspecialchars($o['id']) ?></td>
        <td><?= htmlspecialchars($o['username']) ?></td>
        <td>$<?= htmlspecialchars((float)$o['total']) ?></td>
        <td><?= htmlspecialchars($o['status']) ?></td>
        <td>
            <?php if ($o['status'] === 'pending'): ?>
                <a href="/admin.php?page=orders.php&approve=<?= (int)$o['id'] ?>">Approve</a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
