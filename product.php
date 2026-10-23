<?php
require __DIR__ . '/auth_gate.php';
require __DIR__ . '/config.php';
// Check login for product details
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require __DIR__ . '/includes/session_check.php';
$user = current_user();

$id = (int)($_GET['id'] ?? 0);
$stmt = get_db()->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    http_response_code(404);
}

$page_title = $product['name'] ?? 'Product';
require __DIR__ . '/includes/header.php';
?>
<?php if ($product): ?>
<?php
$productName = strtolower($product['name']);
if (str_contains($productName, 'mouse')) {
    $placeholderIcon = '⌁';
} elseif (str_contains($productName, 'keyboard')) {
    $placeholderIcon = '⌨';
} elseif (str_contains($productName, 'hub')) {
    $placeholderIcon = '◇';
} elseif (str_contains($productName, 'webcam') || str_contains($productName, 'camera')) {
    $placeholderIcon = '◉';
} elseif (str_contains($productName, 'stand')) {
    $placeholderIcon = '⌃';
} else {
    $placeholderIcon = '✦';
}
?>
<section class="card product-detail-layout">
  <div class="product-image-frame<?= empty($product['image_url']) ? ' product-placeholder' : '' ?>">
    <?php if (!empty($product['image_url'])): ?>
      <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
    <?php else: ?>
      <span class="placeholder-icon" aria-hidden="true"><?= htmlspecialchars($placeholderIcon) ?></span>
      <span class="placeholder-caption" aria-hidden="true">ShopEase essentials</span>
    <?php endif; ?>
  </div>
  <div class="product-detail-copy">
    <div class="detail-kicker">ShopEase collection</div>
    <h1><?= htmlspecialchars($product['name']) ?></h1>
    <p class="detail-description"><?= htmlspecialchars($product['description']) ?></p>
    <div class="price">$<?= number_format((float)$product['price'], 2) ?></div>
    <?php if (!$user || !$user['is_admin']): ?>
      <form method="POST" action="/cart.php?action=add&id=<?= (int)$id ?>">
        <label for="quantity">Quantity</label>
        <input id="quantity" type="number" name="quantity" value="1" min="1" aria-label="Quantity">
        <button type="submit">Add to cart&nbsp; →</button>
      </form>
      <p class="muted">Your selection will be ready to review at checkout.</p>
    <?php else: ?>
      <p class="muted"><em>Admins cannot make purchases.</em></p>
    <?php endif; ?>
    <p><a class="button-link" href="/products.php">←&nbsp; Back to the collection</a></p>
  </div>
</section>
<?php else: ?>
  <div class="card">
    <h1>Product not found</h1>
    <p class="muted">This item may have moved. Browse the collection to find something else.</p>
    <a class="button-link" href="/products.php">Browse products</a>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
