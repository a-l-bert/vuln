<?php
$dataDirectory = getenv('APP_DATA_DIR');
if (!is_string($dataDirectory) || $dataDirectory === '' || !is_dir($dataDirectory)) {
    throw new RuntimeException('Set APP_DATA_DIR to an existing isolated test directory.');
}

$sessionDirectory = $dataDirectory . '/sessions';
if (!is_dir($sessionDirectory) && !mkdir($sessionDirectory, 0775, true) && !is_dir($sessionDirectory)) {
    throw new RuntimeException('The isolated session directory could not be created.');
}

if (($argv[1] ?? '') === 'profile') {
    $_GET['id'] = '1';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['SCRIPT_NAME'] = '/profile.php';
    $_SERVER['HTTP_USER_AGENT'] = 'award view regression test';
    session_save_path($sessionDirectory);
    session_id('award-views-test');
    session_start();
    $_SESSION = ['user_id' => 1, 'username' => 'alice', 'is_admin' => false];
    session_write_close();
    require __DIR__ . '/../profile.php';
    exit;
}

if (($argv[1] ?? '') === 'heroes') {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['SCRIPT_NAME'] = '/admin.php';
    $_SERVER['HTTP_USER_AGENT'] = 'award view regression test';
    require __DIR__ . '/../pages/heroes.php';
    exit;
}

$databasePath = $dataDirectory . '/vulnapp.db';
if (file_exists($databasePath)) {
    throw new RuntimeException('Use an isolated APP_DATA_DIR without an existing database.');
}
require __DIR__ . '/../config.php';
$pdo = get_db();
$userQuery = $pdo->prepare('SELECT id, username, email FROM users WHERE username = ?');
$userQuery->execute(['alice']);
$user = $userQuery->fetch(PDO::FETCH_ASSOC);
$userQuery->closeCursor();
if (!$user) {
    throw new RuntimeException('The isolated test database is missing its learner account.');
}
$award = record_automatic_award(
    $pdo,
    $user,
    ['vuln_id' => 'VULN-004', 'vulnerability_name' => 'SQL Injection in Login'],
    'Award View Learner',
    'award-view@example.test'
);
$pdo = null;

$environment = [
    'APP_DATA_DIR' => $dataDirectory,
    'LAB_AUTH_USER' => 'test-lab-user',
    'LAB_AUTH_PASS' => 'test-lab-password',
];
$runChild = static function (array $arguments) use ($environment): string {
    $process = proc_open(
        array_merge([PHP_BINARY, __FILE__], $arguments),
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        null,
        $environment
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start the award view request.');
    }

    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException('The award view request failed: ' . $error);
    }
    return $output;
};

$profile = $runChild(['profile']);
$heroes = $runChild(['heroes']);
foreach ([
    'profile Awards &amp; Certificates heading' => str_contains($profile, 'Awards &amp; Certificates'),
    'profile recipient and certificate' => str_contains($profile, 'Award View Learner')
        && str_contains($profile, $award['award_code']),
    'profile no flag heading' => !str_contains($profile, 'Flags Earned'),
    'Hall recipient and certificate' => str_contains($heroes, 'Award View Learner')
        && str_contains($heroes, $award['award_code']),
    'Hall no flag column' => !preg_match('/<th>\s*Flag\s*<\/th>/i', $heroes),
    'Hall no demo-account column' => !str_contains($heroes, 'Demo account'),
] as $check => $passed) {
    if (!$passed) {
        throw new RuntimeException("Award view check failed: $check.");
    }
}

echo "PASS: learner profile and Hall of Heroes show account-based award/certificate details without flag UI.\n";
