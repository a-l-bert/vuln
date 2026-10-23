<?php
require_once __DIR__ . '/../config.php';
$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VULNERABLE: Parameter Pollution (grabbing first vs last)
    // PHP's ['code'] will be an array if ?code[]=A&code[]=B
    $code = VULN_PARAMETER_POLLUTION ? $_POST['code'] : ($_POST['code'][0] ?? $_POST['code']);
    
    // VULNERABLE: Coupon Stacking
    if (VULN_COUPON_STACKING || !isset($_SESSION['applied_coupons'])) {
        // ... apply discount logic
    }
    echo "Discount applied.";
}
?>
