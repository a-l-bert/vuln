<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (getenv('DATABASE_URL') === false || getenv('DATABASE_URL') === '') {
    fwrite(STDERR, "Refusing to continue: DATABASE_URL must point to the Neon database.\n");
    exit(1);
}

if (!in_array('--confirm', $argv, true)) {
    fwrite(STDERR, "This permanently replaces all shop data with the demo users and products. Re-run with --confirm.\n");
    exit(1);
}

require_once __DIR__ . '/../config.php';

if (!USE_POSTGRES) {
    fwrite(STDERR, "Refusing to reset data: the active database is not PostgreSQL.\n");
    exit(1);
}

$pdo = get_db();
$resetMarker = 'demo_data_reset_v1';
$markerQuery = $pdo->prepare('SELECT metadata_value FROM app_metadata WHERE metadata_key = ?');
$markerQuery->execute([$resetMarker]);
if ($markerQuery->fetchColumn() !== false) {
    fwrite(STDOUT, "Neon demo data reset was already completed; leaving the database unchanged.\n");
    exit;
}

$tables = [
    'order_items',
    'orders',
    'comments',
    'reviews',
    'access_logs',
    'remember_tokens',
    'flags',
    'hall_of_fame',
    'automatic_awards',
    'award_claims',
    'activity_logs',
    'users',
    'products',
];

$references = $pdo->query("
    SELECT DISTINCT tc.table_name AS referencing_table, ccu.table_name AS referenced_table
    FROM information_schema.table_constraints tc
    JOIN information_schema.constraint_column_usage ccu
      ON ccu.constraint_name = tc.constraint_name
     AND ccu.constraint_schema = tc.constraint_schema
    WHERE tc.constraint_type = 'FOREIGN KEY'
      AND tc.table_schema = current_schema()
      AND ccu.table_name IN ('users', 'products', 'orders')
    ORDER BY tc.table_name, ccu.table_name
")->fetchAll(PDO::FETCH_ASSOC);
fwrite(STDOUT, 'Shop foreign-key references: ' . json_encode($references) . PHP_EOL);

$phase = 'clearing existing shop records';
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();
    foreach ($tables as $table) {
        $phase = "clearing $table";
        $deleted = $pdo->exec("DELETE FROM $table");
        $errorInfo = $pdo->errorInfo();
        if ($deleted === false || ($errorInfo[0] ?? '00000') !== '00000') {
            throw new RuntimeException('Delete failed: ' . implode(' | ', array_filter($errorInfo)));
        }
        $pdo->query('SELECT 1')->fetchColumn();
    }

    seed_db(static function ($nextPhase) use (&$phase) {
        $phase = $nextPhase;
    });

    $phase = 'recording reset completion';
    $markerInsert = $pdo->prepare('INSERT INTO app_metadata (metadata_key, metadata_value) VALUES (?, ?)');
    $markerInsert->execute([$resetMarker, gmdate('c')]);
    $pdo->commit();

    $summary = [];
    foreach (['users', 'products', 'orders', 'order_items'] as $table) {
        $summary[$table] = (int)$pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    }
    fwrite(STDOUT, 'Neon demo data reset complete: ' . json_encode($summary) . PHP_EOL);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Neon demo data reset failed while ' . $phase . ': ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
