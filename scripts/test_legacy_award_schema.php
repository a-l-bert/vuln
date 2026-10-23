<?php
$dataDirectory = getenv('APP_DATA_DIR');
if (!is_string($dataDirectory) || $dataDirectory === '' || !is_dir($dataDirectory)) {
    throw new RuntimeException('Set APP_DATA_DIR to an existing isolated test directory.');
}

$databasePath = $dataDirectory . '/vulnapp.db';
$legacy = new PDO('sqlite:' . $databasePath);
$legacy->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$legacy->exec('CREATE TABLE automatic_awards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT,
    user_id INTEGER,
    vuln_id TEXT
)');
$legacy->exec("INSERT INTO automatic_awards (username, user_id, vuln_id)
    VALUES ('legacy-user', 99, 'VULN-LEGACY')");
$legacy->exec('CREATE TABLE award_claims (id INTEGER PRIMARY KEY AUTOINCREMENT)');
$legacy = null;

require __DIR__ . '/../config.php';

$pdo = get_db();
foreach ([
    'automatic_awards' => [
        'username',
        'email',
        'user_id',
        'vuln_id',
        'vulnerability_name',
        'learner_name',
        'learner_email',
        'award_code',
        'timestamp',
    ],
    'award_claims' => [
        'challenge_key',
        'learner_email',
        'award_table',
        'award_id',
        'created_at',
    ],
] as $table => $expectedColumns) {
    $actualColumns = $pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_COLUMN, 1);
    foreach ($expectedColumns as $column) {
        if (!in_array($column, $actualColumns, true)) {
            throw new RuntimeException("Legacy table $table is missing migrated column $column.");
        }
    }
}

$userQuery = $pdo->prepare('SELECT id, username, email FROM users WHERE username = ?');
$userQuery->execute(['alice']);
$user = $userQuery->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    throw new RuntimeException('The isolated test database is missing the seeded learner account.');
}

$awardData = [
    'vuln_id' => 'VULN-004',
    'vulnerability_name' => 'SQL Injection in Login',
];
$firstAward = record_automatic_award(
    $pdo,
    $user,
    $awardData,
    'Legacy Schema Learner',
    'legacy-schema@example.test'
);
$repeatAward = record_automatic_award(
    $pdo,
    $user,
    $awardData,
    'Legacy Schema Learner',
    'legacy-schema@example.test'
);
if (
    empty($firstAward['award_code'])
    || $repeatAward['award_code'] !== $firstAward['award_code']
    || (int)$pdo->query('SELECT COUNT(*) FROM automatic_awards')->fetchColumn() !== 2
    || (string)$pdo->query("SELECT username FROM automatic_awards WHERE vuln_id = 'VULN-LEGACY'")->fetchColumn() !== 'legacy-user'
) {
    throw new RuntimeException('Legacy awards were not preserved or new award saving/idempotency failed after migration.');
}

echo "PASS: legacy award tables are upgraded and an automatic login award can be saved and re-claimed.\n";
