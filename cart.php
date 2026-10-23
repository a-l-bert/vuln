<?php
require __DIR__ . '/auth_gate.php';
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
foreach ($_SESSION['cart'] as $cartProductId => $cartQuantity) {
    if ((int)$cartProductId < 1 || (int)$cartQuantity < 1) {
        unset($_SESSION['cart'][$cartProductId]);
    } else {
        $_SESSION['cart'][$cartProductId] = (int)$cartQuantity;
    }
}

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

// Add item
if ($action === 'add' && $id > 0) {
    $qty = (int)($_POST['quantity'] ?? 1);
    $qty = max(1, $qty);
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + $qty;
    header('Location: /cart.php');
    exit;
}

// Remove item
if ($action === 'remove' && $id > 0) {
    unset($_SESSION['cart'][$id]);
    header('Location: /cart.php');
    exit;
}

// Update quantity
if ($action === 'update' && $id > 0) {
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    $_SESSION['cart'][$id] = $qty;
    header('Location: /cart.php');
    exit;
}

$page_title = 'Shopping Cart';
require __DIR__ . '/includes/header.php';
$pdo = get_db();
$total = 0;
?>
<div class="card">
  <h2>Your Shopping Cart</h2>
  <?php if (empty($_SESSION['cart'])): ?>
    <p class="muted">Your cart is empty. Browse the collection to find something you like.</p>
    <a class="button-link" href="/products.php">Browse products</a>
  <?php else: ?>
    <table>
      <tr><th>Product</th><th>Quantity</th><th>Price</th><th>Subtotal</th><th>Action</th></tr>
      <?php foreach ($_SESSION['cart'] as $item_id => $qty): 
        $stmt = $pdo->prepare("SELECT name, price FROM products WHERE id = ?");
        $stmt->execute([$item_id]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($p) {
            $subtotal = $p['price'] * $qty;
            $total += $subtotal;
      ?>
        <tr>
          <td><?= htmlspecialchars($p['name']) ?></td>
          <td>
            <form method="POST" action="/cart.php?action=update&id=<?= (int)$item_id ?>">
                <input type="number" name="quantity" value="<?= max(1, (int)$qty) ?>" min="1" style="width:60px;">
                <button type="submit">Update</button>
            </form>
          </td>
          <td>$<?= htmlspecialchars((float)$p['price']) ?></td>
          <td>$<?= htmlspecialchars((float)$subtotal) ?></td>
          <td><a href="/cart.php?action=remove&id=<?= (int)$item_id ?>">Remove</a></td>
        </tr>
      <?php } endforeach; ?>
    </table>
    <h3>Total: $<?= htmlspecialchars((float)$total) ?></h3>
    
    <div style="margin-top:20px;">
        <a href="/checkout.php"><button>Proceed to Checkout</button></a>
    </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
