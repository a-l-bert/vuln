<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';

$file = $_GET['f'] ?? '';

if (VULN_PATH_TRAVERSAL_DOWNLOAD) {
    $path = 'uploads/' . $file;
} else {
    // SECURE: basename and check if it exists in uploads/
    $base = basename($file);
    $path = 'uploads/' . $base;
}

if (is_file($path) && is_readable($path)) {
    header('Content-Type: application/octet-stream');
    readfile($path);
} else {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Receipt not found.';
}
