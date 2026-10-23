<?php
$dataDirectory = getenv('APP_DATA_DIR');
if (!is_string($dataDirectory) || $dataDirectory === '' || !is_dir($dataDirectory)) {
    throw new RuntimeException('Set APP_DATA_DIR to an existing isolated test directory.');
}

$sessionId = 'award-submission-flow-test';
$sessionDirectory = $dataDirectory . '/sessions';
if (!is_dir($sessionDirectory) && !mkdir($sessionDirectory, 0775, true) && !is_dir($sessionDirectory)) {
    throw new RuntimeException('The isolated session directory could not be created.');
}

if (($argv[1] ?? '') === 'claim') {
    session_save_path($sessionDirectory);
    session_id($sessionId);
    session_start();
    session_write_close();
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['SCRIPT_NAME'] = '/claim_award.php';
    $_SERVER['HTTP_USER_AGENT'] = 'award submission flow test';
    $_POST = [
        'csrf_token' => 'award-submission-flow-csrf',
        'learner_name' => 'Award Flow Learner',
        'learner_email' => 'award-flow@example.test',
    ];
    require __DIR__ . '/../claim_award.php';
    exit;
}

if (($argv[1] ?? '') === 'certificate') {
    session_save_path($sessionDirectory);
    session_id($sessionId);
    session_start();
    session_write_close();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['SCRIPT_NAME'] = '/generate_cert.php';
    $_SERVER['HTTP_USER_AGENT'] = 'award submission flow test';
    $_GET['award_code'] = (string)($argv[2] ?? '');
    require __DIR__ . '/../generate_cert.php';
    exit;
}

$databasePath = $dataDirectory . '/vulnapp.db';
if (file_exists($databasePath)) {
    throw new RuntimeException('Use an isolated APP_DATA_DIR without an existing database.');
}

session_save_path($sessionDirectory);
session_id($sessionId);
session_start();
$_SESSION = [
    'lab_access_authenticated' => true,
    'user_id' => 1,
    'username' => 'alice',
    'is_admin' => false,
    'pending_award' => [
        'vuln_id' => 'VULN-004',
        'vulnerability_name' => 'SQL Injection in Login',
    ],
    'award_claim_csrf' => 'award-submission-flow-csrf',
];
session_write_close();

$environment = [
    'APP_DATA_DIR' => $dataDirectory,
    'LAB_AUTH_USER' => 'test-lab-user',
    'LAB_AUTH_PASS' => 'test-lab-password',
];
$runChild = static function (array $arguments) use ($environment): array {
    $process = proc_open(
        array_merge([PHP_BINARY, __FILE__], $arguments),
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        null,
        $environment
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start the isolated award request.');
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);
    if ($status !== 0) {
        throw new RuntimeException('The isolated award request failed: ' . $stderr);
    }
    return [$stdout, $stderr];
};

[$claimHtml] = $runChild(['claim']);
if (!preg_match('/generate_cert\.php\?award_code=([0-9]{9})/', $claimHtml, $matches)) {
    throw new RuntimeException('The award claim did not confirm success with a certificate link: ' . strip_tags($claimHtml));
}
$awardCode = $matches[1];

[$certificate] = $runChild(['certificate', $awardCode]);
if (
    !str_starts_with($certificate, '%PDF-1.4')
    || !str_contains($certificate, 'Award Flow Learner')
    || !str_contains($certificate, 'award-flow@example.test')
    || !str_contains($certificate, $awardCode)
) {
    throw new RuntimeException('The saved award did not generate a matching learner certificate PDF.');
}

require __DIR__ . '/../config.php';
$pdo = get_db();
$userQuery = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$userQuery->execute(['alice']);
$userId = $userQuery->fetchColumn();
$userQuery->closeCursor();
if ($userId === false) {
    throw new RuntimeException('The isolated test database is missing the seeded learner account.');
}
$awardQuery = $pdo->prepare(
    'SELECT learner_name, learner_email, award_code FROM automatic_awards
     WHERE user_id = ? AND vuln_id = ?'
);
$awardQuery->execute([$userId, 'VULN-004']);
$award = $awardQuery->fetch(PDO::FETCH_ASSOC);
if (
    !$award
    || $award['learner_name'] !== 'Award Flow Learner'
    || $award['learner_email'] !== 'award-flow@example.test'
    || $award['award_code'] !== $awardCode
) {
    throw new RuntimeException('The submitted learner identity and certificate number were not saved.');
}

echo "PASS: award submission saves learner details and generates the matching certificate PDF.\n";
