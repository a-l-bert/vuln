<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';

$id = (int)($_GET['id'] ?? 0);
$pdo = get_db();

// VULN: CWE-639 — IDOR. No check that the logged-in user owns the order.
if (VULN_IDOR_ORDER_VIEW) {
    $stmt = $pdo->prepare("SELECT o.*, u.username, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->execute([$id]);
} else {
    // SECURE: Ownership check
    $stmt = $pdo->prepare("SELECT o.*, u.username, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? AND o.user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
}
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    http_response_code(404);
    exit('Order not found.');
}

$stmt = $pdo->prepare("SELECT p.name, p.price FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Order Summary';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h2>Order #<?= $id ?> Placed Successfully!</h2>
  <p>Thank you for your order, <?= htmlspecialchars($order['username']) ?>.</p>
  
  <h3>Customer Info</h3>
  <p>Email: <?= htmlspecialchars($order['email']) ?></p>
  <p>Shipping Address: <?= htmlspecialchars($order['shipping_address']) ?></p>

  <h3>Items</h3>
  <ul>
    <?php foreach ($items as $item): ?>
      <li><?= htmlspecialchars($item['name']) ?> - $<?= htmlspecialchars($item['price']) ?></li>
    <?php endforeach; ?>
  </ul>
  <p><strong>Total: $<?= htmlspecialchars($order['total']) ?></strong></p>
  <p>Status: <?= htmlspecialchars($order['status']) ?></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
