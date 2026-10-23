<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: /cart.php');
    exit;
}

$user = current_user();
if (!$user) {
    header('Location: /login.php');
    exit;
}
$pdo = get_db();
$checkoutError = null;
$fullname = trim($_POST['fullname'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$district = trim($_POST['district'] ?? '');
$address = trim($_POST['address'] ?? '');
$cartTotal = 0;
foreach ($_SESSION['cart'] as $itemId => $quantity) {
    $priceQuery = $pdo->prepare('SELECT price FROM products WHERE id = ?');
    $priceQuery->execute([(int)$itemId]);
    $price = $priceQuery->fetchColumn();
    if ($price !== false) {
        $cartTotal += (float)$price * max(1, (int)$quantity);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $full_shipping = "$address, $district, Contact: $contact";
    
    $total = 0;
    $cartItems = [];
    foreach ($_SESSION['cart'] as $item_id => $qty) {
        $itemId = (int)$item_id;
        $quantity = filter_var($qty, FILTER_VALIDATE_INT);
        if ($itemId < 1 || $quantity === false || $quantity < 1) {
            $checkoutError = 'Please check your cart and make sure every quantity is at least 1.';
            break;
        }
        $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$itemId]);
        $p = $stmt->fetch();
        if (!$p) {
            $checkoutError = 'A product in your cart is no longer available. Please review your cart.';
            break;
        }
        $total += $p['price'] * $quantity;
        $cartItems[] = ['id' => $itemId, 'quantity' => $quantity];
    }
    
    if ($checkoutError === null) {
        $pdo->beginTransaction();
        $balanceStmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $balanceStmt->execute([$user['id']]);
        $balance = $balanceStmt->fetchColumn();
        if ($balance === false || (float)$balance < $total) {
            $pdo->rollBack();
            $checkoutError = 'Insufficient funds. Add money to your wallet before confirming this order.';
        } else {
            $deductStmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?");
            $deductStmt->execute([$total, $user['id'], $total]);
            if ($deductStmt->rowCount() === 0) {
                $pdo->rollBack();
                $checkoutError = 'Insufficient funds. Add money to your wallet before confirming this order.';
            } else {
                $orderId = insert_and_get_id(
                    $pdo,
                    "INSERT INTO orders (user_id, total, shipping_address, status) VALUES (?, ?, ?, 'pending')",
                    [$user['id'], $total, $full_shipping]
                );

                $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)");
                foreach ($cartItems as $item) {
                    $itemStmt->execute([$orderId, $item['id'], $item['quantity']]);
                }
                $pdo->commit();

                $_SESSION['cart'] = [];
                header("Location: /order_success.php?id=" . $orderId);
                exit;
            }
        }
    }
}
?>
<?php
$page_title = 'Checkout';
require __DIR__ . '/includes/header.php';
?>
<section class="card" style="max-width:760px;margin:32px auto;">
<h1 style="margin-top:0;">Checkout</h1>
<p class="muted">Confirm your delivery details and review the total before placing your order.</p>
<div class="card" style="padding:16px 20px;margin:20px 0;background:#f7f8fc;box-shadow:none;">
    <strong>Order total</strong>
    <span style="float:right;font-size:18px;font-weight:800;color:#3947ad;">$<?= number_format($cartTotal, 2) ?></span>
</div>
<?php if ($checkoutError !== null): ?>
    <div class="error" role="alert">
        <p><?= htmlspecialchars($checkoutError) ?></p>
        <?php if (str_starts_with($checkoutError, 'Insufficient funds.')): ?>
            <a href="/profile.php?id=<?= (int)$user['id'] ?>">Add funds to your wallet</a>
        <?php else: ?>
            <a href="/cart.php">Review your cart</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
<form method="POST">
    <label for="checkout-fullname">Full name</label>
    <input id="checkout-fullname" type="text" name="fullname" value="<?= htmlspecialchars($fullname) ?>" autocomplete="name" required>
    <label for="checkout-contact">Contact</label>
    <input id="checkout-contact" type="text" name="contact" value="<?= htmlspecialchars($contact) ?>" autocomplete="tel" required>
    <label for="checkout-district">District</label>
    <input id="checkout-district" type="text" name="district" value="<?= htmlspecialchars($district) ?>" required>
    <label for="checkout-address">Delivery address</label>
    <textarea id="checkout-address" name="address" autocomplete="street-address" rows="3" required><?= htmlspecialchars($address) ?></textarea>
    <button type="submit" name="confirm">Confirm order · $<?= number_format($cartTotal, 2) ?></button>
</form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
