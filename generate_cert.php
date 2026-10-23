<?php
require __DIR__ . '/auth_gate.php';
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';

$user = current_user();
if (!$user) {
    header('Location: /login.php');
    exit;
}

$pdo = get_db();
$awardCode = is_string($_GET['award_code'] ?? null) ? trim($_GET['award_code']) : '';
if ($awardCode !== '') {
    $stmt = $pdo->prepare(
        'SELECT vuln_id, vulnerability_name, timestamp, learner_name, learner_email, award_code
         FROM automatic_awards WHERE award_code = ? AND user_id = ? LIMIT 1'
    );
    $stmt->execute([$awardCode, $user['id']]);
    $award = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$award) {
        $stmt = $pdo->prepare(
            "SELECT COALESCE(f.vuln_id, h.vuln_id) AS vuln_id,
                    COALESCE(f.vulnerability_name, CASE h.vuln_id
                        WHEN 'VULN-004' THEN 'SQL Injection in Login'
                        ELSE h.vuln_id END) AS vulnerability_name,
                    h.timestamp, h.learner_name, h.learner_email, h.award_code
             FROM hall_of_fame h
             LEFT JOIN flags f ON h.flag_id = f.id
             WHERE h.award_code = ? AND h.user_id = ?
             LIMIT 1"
        );
        $stmt->execute([$awardCode, $user['id']]);
        $award = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} else {
    $flagId = (int)($_GET['flag_id'] ?? 0);
    $stmt = $pdo->prepare(
        "SELECT COALESCE(f.vuln_id, h.vuln_id) AS vuln_id,
                COALESCE(f.vulnerability_name, CASE h.vuln_id
                    WHEN 'VULN-004' THEN 'SQL Injection in Login'
                    ELSE h.vuln_id END) AS vulnerability_name,
                h.timestamp, h.learner_name, h.learner_email, h.award_code
         FROM hall_of_fame h
         LEFT JOIN flags f ON h.flag_id = f.id
         WHERE h.flag_id = ? AND h.user_id = ?
         ORDER BY h.timestamp DESC
         LIMIT 1"
    );
    $stmt->execute([$flagId, $user['id']]);
    $award = $stmt->fetch(PDO::FETCH_ASSOC);
}
if (!$award) {
    http_response_code(404);
    exit('Achievement not found.');
}

require __DIR__ . '/includes/certificate_pdf.php';
send_certificate_pdf(
    $user['username'],
    $award['vulnerability_name'] ?: $award['vuln_id'],
    format_eat_datetime($award['timestamp']),
    $award['learner_name'] ?? '',
    $award['learner_email'] ?? '',
    $award['award_code'] ?? ''
);
