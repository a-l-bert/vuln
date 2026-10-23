<?php
require __DIR__ . '/auth_gate.php';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($requestPath === '/register.php') {
    http_response_code(404);
    exit('Page not found.');
}

header('Location: /products.php');
exit;