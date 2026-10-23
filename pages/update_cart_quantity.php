<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    $productId = (int)($_POST['product_id'] ?? 0);
    // VULNERABLE: Integer overflow/sign abuse
    if (VULN_INTEGER_OVERFLOW) {
        // No upper bound is applied.
    } else {
        if ($qty < 1 || $qty > 99) die("Invalid qty");
    }
    if ($productId > 0) {
        $_SESSION['cart'][$productId] = $qty;
    }
}
