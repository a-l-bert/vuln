<?php
$databaseUrl = getenv('POSTGRES_TEST_DATABASE_URL');
if (!is_string($databaseUrl) || $databaseUrl === '') {
    throw new RuntimeException('Set POSTGRES_TEST_DATABASE_URL to a disposable PostgreSQL database.');
}

$parts = parse_url($databaseUrl);
$databaseName = is_array($parts) ? rawurldecode(ltrim($parts['path'] ?? '', '/')) : '';
if (
    $parts === false
    || empty($parts['host'])
    || !preg_match('/^shopease_test_[a-z0-9_]+$/i', $databaseName)
) {
    throw new RuntimeException('Use a dedicated database named shopease_test_<name>.');
}

$query = [];
parse_str($parts['query'] ?? '', $query);
$dsn = sprintf(
    'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
    $parts['host'],
    $parts['port'] ?? 5432,
    $databaseName,
    $query['sslmode'] ?? 'disable'
);
$credentials = [
    rawurldecode($parts['user'] ?? ''),
    rawurldecode($parts['pass'] ?? ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
];
$setup = new PDO($dsn, $credentials[0], $credentials[1], $credentials[2]);
if ($setup->query("SELECT to_regclass('flags')")->fetchColumn() !== null) {
    throw new RuntimeException('Use a fresh disposable database without an existing flags table.');
}

$suffix = bin2hex(random_bytes(6));
$functionName = 'reject_catalog_insert_' . $suffix;
$triggerName = 'reject_catalog_insert_' . $suffix;
$setup->exec('CREATE TABLE flags (
    id BIGINT PRIMARY KEY,
    vuln_id TEXT,
    flag TEXT,
    vulnerability_name TEXT
)');
$setup->exec(
    "CREATE FUNCTION $functionName() RETURNS trigger LANGUAGE plpgsql AS \$\$
     BEGIN RAISE EXCEPTION 'synthetic catalog failure'; END
     \$\$"
);
$setup->exec(
    "CREATE TRIGGER $triggerName BEFORE INSERT ON flags
     FOR EACH ROW EXECUTE FUNCTION $functionName()"
);

putenv('DATABASE_URL=' . $databaseUrl);
$logPath = sys_get_temp_dir() . '/shopease-postgres-catalog-' . $suffix . '.log';
ini_set('error_log', $logPath);

$failure = null;
try {
    require __DIR__ . '/../config.php';
    $pdo = get_db();
    $flagCount = (int)$pdo->query('SELECT COUNT(*) FROM flags')->fetchColumn();
    $userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $advisoryLockCount = (int)$pdo->query(
        "SELECT COUNT(*) FROM pg_locks
         WHERE pid = pg_backend_pid() AND locktype = 'advisory' AND granted"
    )->fetchColumn();
    $log = file_get_contents($logPath);

    if (
        $flagCount !== 0
        || $userCount === 0
        || $pdo->inTransaction()
        || $advisoryLockCount !== 0
        || !is_string($log)
        || !str_contains($log, 'failed during inserting catalog entry VULN-001')
        || !str_contains($log, 'transaction_at_start=idle')
        || !str_contains($log, 'transaction_at_failure=idle')
        || !str_contains($log, 'rollback=not needed')
        || !str_contains($log, 'synthetic catalog failure')
    ) {
        throw new RuntimeException('Catalog failure did not leave the PostgreSQL connection usable and unlocked.');
    }

    echo "PASS: PostgreSQL catalog failures do not abort startup or retain an advisory lock.\n";
} catch (Throwable $exception) {
    $diagnosticLog = is_file($logPath) ? file_get_contents($logPath) : '';
    $failure =
        $exception->getMessage() . ($diagnosticLog ? "\n" . $diagnosticLog : '');
} finally {
    $setup->exec("DROP TRIGGER IF EXISTS $triggerName ON flags");
    $setup->exec("DROP FUNCTION IF EXISTS $functionName()");
    if (is_file($logPath)) {
        unlink($logPath);
    }
}

if ($failure !== null) {
    fwrite(STDERR, $failure . PHP_EOL);
    exit(1);
}
