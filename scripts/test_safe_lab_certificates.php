<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/safe_lab_simulations.php';

$pdo = get_db();
$toggles = array_filter(
    get_defined_constants(true)['user'] ?? [],
    static fn($value, $name) => str_starts_with($name, 'VULN_') && is_bool($value),
    ARRAY_FILTER_USE_BOTH
);
$userQuery = $pdo->prepare('SELECT id, username, email FROM users WHERE username = ?');
$userQuery->execute(['alice']);
$user = $userQuery->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    throw new RuntimeException('The isolated test database is missing the seeded learner account.');
}

$before = (int)$pdo->query('SELECT COUNT(*) FROM automatic_awards')->fetchColumn();
$claimedAwards = [];
foreach ($toggles as $toggle => $enabled) {
    $simulation = safe_lab_simulation_profile($toggle);
    if (!$simulation['passed']) {
        throw new RuntimeException("$toggle did not pass its synthetic safety check.");
    }

    $certificateDetails = safe_lab_certificate_details($toggle);
    $learnerEmail = strtolower(str_replace('_', '-', $toggle)) . '@example.test';
    $award = record_automatic_award(
        $pdo,
        $user,
        $certificateDetails,
        'Safe Lab Test Learner',
        $learnerEmail
    );
    if (empty($award['award_code']) || empty($award['id'])) {
        throw new RuntimeException("$toggle did not receive a certificate record.");
    }
    $claimedAwards[$toggle] = $award;
}

if (count($claimedAwards) !== 69 || count(array_unique(array_column($claimedAwards, 'award_code'))) !== 69) {
    throw new RuntimeException('Every configured toggle must issue a distinct certificate.');
}

$firstToggle = array_key_first($claimedAwards);
$firstAward = $claimedAwards[$firstToggle];
$repeat = record_automatic_award(
    $pdo,
    $user,
    safe_lab_certificate_details($firstToggle),
    'Safe Lab Test Learner',
    strtolower(str_replace('_', '-', $firstToggle)) . '@example.test'
);
if ($repeat['award_code'] !== $firstAward['award_code']) {
    throw new RuntimeException('Reclaiming the same simulation certificate created a duplicate.');
}

$after = (int)$pdo->query('SELECT COUNT(*) FROM automatic_awards')->fetchColumn();
if ($after - $before !== 69) {
    throw new RuntimeException('Expected exactly one simulation certificate per configured toggle.');
}

echo "PASS: all 69 simulations issued distinct certificates; repeat claims reuse the original certificate.\n";
