<?php
require __DIR__ . '/config.php';
$labAccessAuthenticated = !empty($_SESSION['lab_access_authenticated']);
$_SESSION = [];
if ($labAccessAuthenticated) {
    $_SESSION['lab_access_authenticated'] = true;
}
session_regenerate_id(true);
setcookie('remember', '', time() - 3600, '/');
header('Location: /login.php');
exit;
