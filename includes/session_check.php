<?php
require_once __DIR__ . '/log_ua.php';

if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember'])) {
    if (VULN_WEAK_REMEMBER_ME) {
        $data = @unserialize(base64_decode($_COOKIE['remember']));
        if (is_array($data) && isset($data['user_id'])) {
            $_SESSION['user_id'] = $data['user_id'];
            $_SESSION['username'] = $data['username'] ?? '';
            $_SESSION['is_admin'] = $data['is_admin'] ?? false;
        }
    } else {
        $token = $_COOKIE['remember'];
        $stmt = get_db()->prepare("SELECT user_id FROM remember_tokens WHERE token = ?");
        $stmt->execute([$token]);
        $user_id = $stmt->fetchColumn();
        if ($user_id) {
            $_SESSION['user_id'] = $user_id;
        }
    }
}

function current_user() {
    if (!isset($_SESSION['user_id'])) return null;
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT id, username, email, is_admin, balance FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function check_admin_access() {
    if (VULN_HTTP_VERB_TAMPERING && $_SERVER['REQUEST_METHOD'] === 'POST') {
        return; // Weak access control
    }
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        http_response_code(403);
        die('Forbidden');
    }
}
