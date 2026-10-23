<?php
$dataDirectory = getenv('APP_DATA_DIR');
$logFile = getenv('AWARD_ERROR_LOG');
if (!is_string($dataDirectory) || $dataDirectory === '' || !is_dir($dataDirectory)) {
    throw new RuntimeException('Set APP_DATA_DIR to an existing isolated test directory.');
}
if (!is_string($logFile) || $logFile === '') {
    throw new RuntimeException('Set AWARD_ERROR_LOG to a temporary log file path.');
}

if (($argv[1] ?? '') === 'request') {
    session_save_path($dataDirectory . '/sessions');
    session_id('award-error-reference-test');
    session_start();
    $_SESSION = [
        'user_id' => 1,
        'username' => 'alice',
        'pending_award' => [
            'vuln_id' => 'VULN-004',
            'vulnerability_name' => 'SQL Injection in Login',
        ],
        'award_claim_csrf' => 'test-award-claim-csrf-token',
    ];
    session_write_close();
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['SCRIPT_NAME'] = '/claim_award.php';
    $_SERVER['HTTP_USER_AGENT'] = 'award error reference test';
    $_POST = [
        'csrf_token' => 'test-award-claim-csrf-token',
        'learner_name' => 'Error Reference Test',
        'learner_email' => 'award-error-reference@example.test',
    ];
    ini_set('error_log', $logFile);
    require __DIR__ . '/../claim_award.php';
    exit;
}

if (!is_dir($dataDirectory . '/sessions') && !mkdir($dataDirectory . '/sessions', 0775, true)) {
    throw new RuntimeException('The isolated session directory could not be created.');
}
require __DIR__ . '/../config.php';
$pdo = get_db();
$pdo->exec("CREATE TRIGGER test_force_award_failure BEFORE INSERT ON automatic_awards
    BEGIN SELECT RAISE(ABORT, 'synthetic award persistence failure'); END");
session_write_close();

$process = proc_open(
    [PHP_BINARY, __FILE__, 'request'],
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes,
    null,
    [
        'APP_DATA_DIR' => $dataDirectory,
        'AWARD_ERROR_LOG' => $logFile,
    ]
);
if (!is_resource($process)) {
    throw new RuntimeException('Unable to start the isolated claim request.');
}
fclose($pipes[0]);
$html = stream_get_contents($pipes[1]);
$childErrors = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$status = proc_close($process);
if ($status !== 0) {
    throw new RuntimeException('The isolated claim request failed: ' . $childErrors);
}

if (!preg_match('/reference ([a-f0-9]{12})/', $html, $matches)) {
    throw new RuntimeException('Failed claim response did not display its short reference ID.');
}
$reference = $matches[1];
$log = file_get_contents($logFile);
if (
    !is_string($log)
    || !str_contains($log, "Award claim failed [$reference]:")
    || !str_contains($log, 'synthetic award persistence failure')
) {
    throw new RuntimeException('The failed claim reference did not map to its server-side error log.');
}
if (str_contains($html, 'synthetic award persistence failure')) {
    throw new RuntimeException('The claim response exposed internal database error details.');
}

echo "PASS: failed award claims show a short support reference matching the server log without exposing database errors.\n";
