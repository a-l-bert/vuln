<?php
require_once __DIR__ . '/../config.php';
$pdo = get_db();

$flagAwards = $pdo->query(
    "SELECT h.username, COALESCE(h.email, u.email, '') AS email, h.vuln_id,
            h.learner_name, h.learner_email, h.award_code, h.flag_id,
            COALESCE(f.vulnerability_name, CASE h.vuln_id
                WHEN 'VULN-004' THEN 'SQL Injection in Login'
                ELSE h.vuln_id END) AS vulnerability_name,
            f.flag, h.timestamp
     FROM hall_of_fame h
     LEFT JOIN users u ON h.user_id = u.id OR (h.user_id IS NULL AND h.username = u.username)
     LEFT JOIN flags f ON h.flag_id = f.id
     ORDER BY h.timestamp DESC"
)->fetchAll(PDO::FETCH_ASSOC);
$automaticAwards = $pdo->query(
    "SELECT username, email, vuln_id, learner_name, learner_email, award_code,
            NULL AS flag_id, vulnerability_name, 'Automatically detected' AS flag, timestamp
     FROM automatic_awards
     ORDER BY timestamp DESC"
)->fetchAll(PDO::FETCH_ASSOC);
$heroes = array_merge($flagAwards, $automaticAwards);
usort($heroes, static fn($left, $right) => strcmp($right['timestamp'], $left['timestamp']));
?>
<h3>Hall of Heroes</h3>
<table border="1">
    <tr><th>Learner</th><th>Learner email</th><th>Demo account</th><th>Certificate #</th><th>Flag</th><th>Vulnerability Type</th><th>Time (EAT)</th></tr>
    <?php if (empty($heroes)): ?>
    <tr><td colspan="7">No discoveries have been submitted yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($heroes as $h): ?>
    <tr>
        <td><?= htmlspecialchars($h['learner_name'] ?: 'Legacy award') ?></td>
        <td><?= htmlspecialchars($h['learner_email'] ?? '') ?></td>
        <td><?= htmlspecialchars($h['username']) ?><?= $h['email'] !== '' ? ' · ' . htmlspecialchars($h['email']) : '' ?></td>
        <td><?= htmlspecialchars($h['award_code'] ?? '') ?></td>
        <td><code><?= htmlspecialchars($h['flag'] ?? ($h['flag_id'] === null ? 'Automatically detected' : 'Legacy entry')) ?></code></td>
        <td><?= htmlspecialchars($h['vulnerability_name']) ?> (<?= htmlspecialchars($h['vuln_id']) ?>)</td>
        <td><?= htmlspecialchars(format_eat_datetime($h['timestamp'])) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
