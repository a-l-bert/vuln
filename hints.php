<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h2>Pentest Hint Center</h2>
  <p>Need help? Look for vulnerabilities in the following areas based on what's active:</p>
  <ul>
    <?php if (VULN_SQLI_PRODUCT_SEARCH): ?>
        <li><strong>SQL Injection:</strong> Try searching for products with characters like <code>'</code> or <code>--</code>.</li>
    <?php endif; ?>
    <?php if (VULN_IDOR_ORDER_VIEW): ?>
        <li><strong>IDOR:</strong> Check the URL when viewing an order success page. Can you guess another ID?</li>
    <?php endif; ?>
  </ul>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
