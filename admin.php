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
  <h2>Admin Panel</h2>
  <?php
    if (file_exists($target)) {
        include $target;
    } else {
        echo '<p class="error" role="alert">This admin section is unavailable. Return to the dashboard or choose another section.</p>';
    }
  ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
