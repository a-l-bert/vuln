<?php
$ua = $_SERVER['HTTP_USER_AGENT'];
if (VULN_HEADER_SQLI) {
    // VULNERABLE: Direct concatenation of header
    get_db()->exec("INSERT INTO access_logs (ua) VALUES ('$ua')");
} else {
    // SECURE: Prepared statement
    $stmt = get_db()->prepare("INSERT INTO access_logs (ua) VALUES (?)");
    $stmt->execute([$ua]);
}
