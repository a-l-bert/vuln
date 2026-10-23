<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = get_db()->prepare("SELECT id, username, email, ssn, is_admin, balance FROM users WHERE id = ?");
$stmt->execute([$id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$profile) {
    http_response_code(404);
}

// Fetch user's exploited flags
$flags = [];
if ($profile) {
    $stmt = get_db()->prepare(
        "SELECT h.vuln_id, h.timestamp, h.learner_name, h.learner_email, h.award_code,
                COALESCE(f.vulnerability_name, CASE h.vuln_id
                    WHEN 'VULN-004' THEN 'SQL Injection in Login'
                    ELSE h.vuln_id END) AS vulnerability_name
         FROM hall_of_fame h
         LEFT JOIN flags f ON h.flag_id = f.id
         WHERE h.user_id = ? OR (h.user_id IS NULL AND h.username = ?)
         ORDER BY h.timestamp DESC"
    );
    $stmt->execute([$profile['id'], $profile['username']]);
    $flags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $automaticAwards = get_db()->prepare(
        'SELECT vuln_id, timestamp, learner_name, learner_email, award_code, vulnerability_name
         FROM automatic_awards WHERE user_id = ? ORDER BY timestamp DESC'
    );
    $automaticAwards->execute([$profile['id']]);
    $flags = array_merge($flags, $automaticAwards->fetchAll(PDO::FETCH_ASSOC));
    usort($flags, static fn($left, $right) => strcmp($right['timestamp'], $left['timestamp']));
}

$page_title = 'Profile';
require __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:480px;">
  <h2 style="margin-top:0;">User Profile</h2>
  <?php if ($profile): ?>
    <p><strong>Username:</strong> <?= htmlspecialchars($profile['username']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($profile['email']) ?></p>
    <p><strong>Balance:</strong> $<?= htmlspecialchars((float)$profile['balance']) ?></p>
    
    <h3>Flags Earned</h3>
    <ul>
        <?php foreach ($flags as $f): ?>
            <li>
                <?= htmlspecialchars($f['vulnerability_name']) ?> (<?= htmlspecialchars($f['vuln_id']) ?>)
                — <?= htmlspecialchars(format_eat_datetime($f['timestamp'])) ?>
                <?php if (!empty($f['learner_name'])): ?>
                    <br><span class="muted">Claimed by <?= htmlspecialchars($f['learner_name']) ?> · <?= htmlspecialchars($f['learner_email']) ?> · Certificate <?= htmlspecialchars($f['award_code']) ?></span>
                <?php endif; ?>
                <?php if (!empty($f['award_code'])): ?>
                    <br><a href="/generate_cert.php?award_code=<?= rawurlencode($f['award_code']) ?>">Download certificate</a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    
    <button onclick="window.print()">Download Profile as PDF</button>
  <?php else: ?>
    <p class="muted">User not found.</p>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
