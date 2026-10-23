<?php
require_once __DIR__ . '/../config.php';
$pdo = get_db();

$historicalAwards = $pdo->query(
    "SELECT h.username, COALESCE(h.email, u.email, '') AS email, h.vuln_id,
            h.learner_name, h.learner_email, h.award_code,
            COALESCE(f.vulnerability_name, CASE h.vuln_id
                WHEN 'VULN-004' THEN 'SQL Injection in Login'
                ELSE h.vuln_id END) AS vulnerability_name,
            h.timestamp
     FROM hall_of_fame h
     LEFT JOIN users u ON h.user_id = u.id OR (h.user_id IS NULL AND h.username = u.username)
     LEFT JOIN flags f ON h.flag_id = f.id
     ORDER BY h.timestamp DESC"
)->fetchAll(PDO::FETCH_ASSOC);
$automaticAwards = $pdo->query(
    "SELECT username, email, vuln_id, learner_name, learner_email, award_code,
            vulnerability_name, timestamp
     FROM automatic_awards
     ORDER BY timestamp DESC"
)->fetchAll(PDO::FETCH_ASSOC);
$heroes = array_merge($historicalAwards, $automaticAwards);
usort($heroes, static fn($left, $right) => strcmp($right['timestamp'], $left['timestamp']));
?>
<h3>Hall of Heroes</h3>
<p class="admin-dashboard-intro">Review recorded vulnerability discoveries and certificate details.</p>
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Recipient</th><th>Email</th><th>Account</th><th>Certificate #</th><th>Vulnerability</th><th>Time (EAT)</th></tr></thead>
        <tbody>
        <?php if (empty($heroes)): ?>
            <tr><td colspan="6" class="admin-empty">No discoveries have been recorded yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($heroes as $h): ?>
        <tr>
            <td><?= htmlspecialchars($h['learner_name'] ?: 'Legacy award') ?></td>
            <td><?= htmlspecialchars($h['learner_email'] ?? '') ?></td>
            <td><?= htmlspecialchars($h['username']) ?><?= $h['email'] !== '' ? ' · ' . htmlspecialchars($h['email']) : '' ?></td>
            <td><?= htmlspecialchars($h['award_code'] ?? '') ?></td>
            <td><?= htmlspecialchars($h['vulnerability_name']) ?></td>
            <td><?= htmlspecialchars(format_eat_datetime($h['timestamp'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
