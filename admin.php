<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';

// VULNERABLE: URL quirks (trailing slash, etc)
$request_uri = $_SERVER['REQUEST_URI'];
if (VULN_URL_QUIRKS) {
    // bypass check if trailing slash is added
    if (strpos($request_uri, '/admin.php/') !== false) {
        // bypass
    } else {
        check_admin();
    }
} else {
    check_admin();
}

function check_admin() {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        http_response_code(403);
        die('Forbidden');
    }
}

$page = $_GET['page'] ?? 'dashboard.php';
$target = 'pages/' . $page;
$adminSections = [
    'dashboard.php' => 'Overview',
    'products.php' => 'Products',
    'users.php' => 'Users',
    'orders.php' => 'Orders',
    'heroes.php' => 'Hall of Heroes',
];
$activeSection = match ($page) {
    'edit_product.php' => 'products.php',
    'edit_user.php' => 'users.php',
    default => $page,
};
$recordChecks = [
    'edit_user.php' => 'users',
    'edit_product.php' => 'products',
];
if (isset($recordChecks[$page])) {
    $table = $recordChecks[$page];
    $recordQuery = get_db()->prepare("SELECT 1 FROM $table WHERE id = ?");
    $recordQuery->execute([(int)($_GET['id'] ?? 0)]);
    if ($recordQuery->fetchColumn() === false) {
        http_response_code(404);
    }
} elseif (!is_file($target)) {
    http_response_code(404);
}

$page_title = 'Admin';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <section class="admin-shell">
    <header class="admin-heading">
      <div>
        <span class="detail-kicker">ShopEase management</span>
        <h1>Admin workspace</h1>
        <p>Manage the storefront, customers, and incoming orders.</p>
      </div>
    </header>
    <nav class="admin-nav" aria-label="Admin sections">
      <?php foreach ($adminSections as $sectionPage => $sectionLabel): ?>
        <a href="/admin.php?page=<?= htmlspecialchars($sectionPage) ?>"
           <?= $activeSection === $sectionPage ? 'aria-current="page"' : '' ?>>
          <?= htmlspecialchars($sectionLabel) ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <section class="admin-content">
      <?php
        if (file_exists($target)) {
            include $target;
        } else {
            echo '<p class="error" role="alert">This admin section is unavailable. Return to the dashboard or choose another section.</p>';
        }
      ?>
    </section>
  </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
