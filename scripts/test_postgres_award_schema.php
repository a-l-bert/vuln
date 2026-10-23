<?php
$databaseUrl = getenv('DATABASE_URL');
if (!is_string($databaseUrl) || $databaseUrl === '') {
    throw new RuntimeException('Set DATABASE_URL to an isolated PostgreSQL test database.');
}

$parts = parse_url($databaseUrl);
if ($parts === false || empty($parts['host']) || empty($parts['path'])) {
    throw new RuntimeException('DATABASE_URL is not a valid PostgreSQL connection URL.');
}
$query = [];
parse_str($parts['query'] ?? '', $query);
$dsn = sprintf(
    'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
    $parts['host'],
    $parts['port'] ?? 5432,
    rawurldecode(ltrim($parts['path'], '/')),
    $query['sslmode'] ?? 'disable'
);
$legacy = new PDO(
    $dsn,
    rawurldecode($parts['user'] ?? ''),
    rawurldecode($parts['pass'] ?? ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$legacy->exec('CREATE TABLE automatic_awards (
    id BIGSERIAL PRIMARY KEY,
    username TEXT,
    user_id INTEGER,
    vuln_id TEXT
)');
$legacy->exec("INSERT INTO automatic_awards (username, user_id, vuln_id)
    VALUES ('legacy-user', 99, 'VULN-LEGACY')");
$legacy->exec('CREATE TABLE award_claims (id BIGSERIAL PRIMARY KEY)');
$legacy = null;

require __DIR__ . '/../config.php';

$pdo = get_db();
foreach ([
    'automatic_awards' => [
        'email',
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
    $columns = $pdo->prepare(
        'SELECT column_name FROM information_schema.columns
         WHERE table_schema = current_schema() AND table_name = ?'
    );
    $columns->execute([$table]);
    $actualColumns = $columns->fetchAll(PDO::FETCH_COLUMN);
    foreach ($expectedColumns as $column) {
        if (!in_array($column, $actualColumns, true)) {
            throw new RuntimeException("Legacy PostgreSQL table $table is missing column $column.");
        }
    }
}

$userQuery = $pdo->prepare('SELECT id, username, email FROM users WHERE username = ?');
$userQuery->execute(['alice']);
$user = $userQuery->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    throw new RuntimeException('The isolated PostgreSQL test database is missing the seeded learner account.');
}

$awardData = [
    'vuln_id' => 'VULN-004',
    'vulnerability_name' => 'SQL Injection in Login',
];
$pdo->beginTransaction();
try {
    $pdo->query('SELECT 1 / 0');
} catch (PDOException $exception) {
    if ($exception->getCode() !== '22012') {
        throw $exception;
    }
}
$firstAward = record_automatic_award(
    $pdo,
    $user,
    $awardData,
    'PostgreSQL Test Learner',
    'postgres-award@example.test'
);
$repeatAward = record_automatic_award(
    $pdo,
    $user,
    $awardData,
    'PostgreSQL Test Learner',
    'POSTGRES-AWARD@example.test'
);
$count = (int)$pdo->query('SELECT COUNT(*) FROM automatic_awards')->fetchColumn();
$claimCount = (int)$pdo->query(
    "SELECT COUNT(*) FROM award_claims
     WHERE challenge_key = 'sql injection in login'
       AND LOWER(learner_email) = 'postgres-award@example.test'"
)->fetchColumn();
$legacyUsername = $pdo->query("SELECT username FROM automatic_awards WHERE vuln_id = 'VULN-LEGACY'")->fetchColumn();
if (
    empty($firstAward['award_code'])
    || $repeatAward['award_code'] !== $firstAward['award_code']
    || $count !== 2
    || $claimCount !== 1
    || $pdo->inTransaction()
    || $legacyUsername !== 'legacy-user'
) {
    throw new RuntimeException('PostgreSQL migration did not recover an aborted transaction and save/idempotently reclaim a new award.');
}

echo "PASS: PostgreSQL legacy award tables migrate and an automatic login award saves and reclaims once.\n";
