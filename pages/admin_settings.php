<?php
require_once __DIR__ . '/../config.php';
$pdo = get_db();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die("Forbidden");
}

$vuln_flags = [
    'VULN_SQLI_PRODUCT_SEARCH', 'VULN_IDOR_ORDER_VIEW', 'VULN_PATH_TRAVERSAL_INCLUDE',
    'VULN_SQLI_LOGIN', 'VULN_REFLECTED_XSS', 'VULN_CSRF', 'VULN_UNRESTRICTED_FILE_UPLOAD'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_config = "<?php\n";
    $new_config .= "session_start();\n\n";
    foreach ($vuln_flags as $flag) {
        $val = isset($_POST[$flag]) ? 'true' : 'false';
        $new_config .= "define('$flag', $val);\n";
    }
    $new_config .= "?>";
    file_put_contents(__DIR__ . '/../vuln_config.php', $new_config);
    echo "Configuration updated.";
}
?>
<h3>Admin Settings - Toggle Vulnerabilities</h3>
<form method="POST">
    <?php foreach ($vuln_flags as $flag): ?>
        <label><input type="checkbox" name="<?= $flag ?>" <?= constant($flag) ? 'checked' : '' ?>> <?= $flag ?></label><br>
    <?php endforeach; ?>
    <button type="submit">Update Config</button>
</form>
