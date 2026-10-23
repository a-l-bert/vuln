<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST['data'];
    // VULNERABLE: Catastrophic backtracking regex
    if (VULN_REDOS) {
        preg_match('/(a+)+$/', $input);
    } else {
        preg_match('/^a+$/', $input);
    }
}
?>
