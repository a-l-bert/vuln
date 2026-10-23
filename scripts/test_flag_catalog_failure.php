<?php
$dataDirectory = getenv('APP_DATA_DIR');
if (!is_string($dataDirectory) || $dataDirectory === '' || !is_dir($dataDirectory)) {
    throw new RuntimeException('Set APP_DATA_DIR to an existing isolated test directory.');
}

$databasePath = $dataDirectory . '/vulnapp.db';
if (file_exists($databasePath)) {
    throw new RuntimeException('Use an isolated APP_DATA_DIR without an existing database.');
}

$database = new PDO('sqlite:' . $databasePath);
$database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$database->exec('CREATE TABLE flags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vuln_id TEXT,
    flag TEXT,
    vulnerability_name TEXT
)');
$database->exec("CREATE TRIGGER reject_flag_catalog_insert BEFORE INSERT ON flags
    BEGIN SELECT RAISE(ABORT, 'synthetic catalog failure'); END");
$database = null;

$logPath = $dataDirectory . '/catalog-error.log';
ini_set('error_log', $logPath);
require __DIR__ . '/../config.php';

$pdo = get_db();
$flagCount = (int)$pdo->query('SELECT COUNT(*) FROM flags')->fetchColumn();
$userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$log = file_get_contents($logPath);
if ($flagCount !== 0 || $userCount === 0) {
    throw new RuntimeException('Catalog seeding did not roll back cleanly or prevented shop initialization.');
}
if (
    !is_string($log)
    || !str_contains($log, 'failed during inserting catalog entry VULN-001')
    || !str_contains($log, 'transaction_at_start=idle')
    || !str_contains($log, 'rollback=transaction rolled back')
    || !str_contains($log, 'synthetic catalog failure')
) {
    throw new RuntimeException('Catalog failure logs are missing stage, transaction, rollback, or original-error details.');
}

echo "PASS: catalog failures are diagnosed and rolled back without blocking shop initialization.\n";
