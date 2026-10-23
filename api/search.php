<?php
require __DIR__ . '/../config.php';
$q = $_GET['q'] ?? '';

if (VULN_SQLI_PRODUCT_SEARCH) {
    $sql = "SELECT id, name, description, price, image_url FROM products WHERE LOWER(name) LIKE LOWER('%$q%')";
    $items = get_db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = get_db()->prepare("SELECT id, name, description, price, image_url FROM products WHERE LOWER(name) LIKE LOWER(?)");
    $stmt->execute(['%' . $q . '%']);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

foreach ($items as $p):
    $productName = strtolower($p['name']);
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
    <div class="product">
      <?php if (!empty($p['image_url'])): ?>
        <div class="product-image-frame">
          <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
        </div>
      <?php else: ?>
        <div class="product-image-frame product-placeholder" aria-hidden="true">
          <span class="placeholder-icon"><?= htmlspecialchars($placeholderIcon) ?></span>
          <span class="placeholder-caption">ShopEase essentials</span>
        </div>
      <?php endif; ?>
      <h3><a href="/product.php?id=<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></a></h3>
      <p class="muted"><?= htmlspecialchars($p['description']) ?></p>
      <div class="price">$<?= number_format((float)$p['price'], 2) ?></div>
      <div class="product-actions"><a href="/product.php?id=<?= (int)$p['id'] ?>">View product details&nbsp; →</a></div>
    </div>
<?php endforeach; 
