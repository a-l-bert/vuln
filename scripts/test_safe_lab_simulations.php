<?php
require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/safe_lab_simulations.php';

$toggles = array_filter(
    get_defined_constants(true)['user'] ?? [],
    static fn($value, $name) => str_starts_with($name, 'VULN_') && is_bool($value),
    ARRAY_FILTER_USE_BOTH
);
$before = [
    'users' => (int)get_db()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'products' => (int)get_db()->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'orders' => (int)get_db()->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
];

if (count($toggles) !== 69) {
    throw new RuntimeException('Expected simulations for all 69 configured vulnerability switches.');
}

foreach ($toggles as $toggle => $enabled) {
    $simulation = safe_lab_simulation_profile($toggle);
    foreach (['domain', 'scenario', 'observation', 'remediation', 'passed'] as $field) {
        if (!isset($simulation[$field]) || trim($simulation[$field]) === '') {
            throw new RuntimeException("$toggle is missing simulation field $field.");
        }
    }
    if ($simulation['passed'] !== true) {
        throw new RuntimeException("$toggle did not pass its synthetic safety check.");
    }
}

try {
    safe_lab_simulation_profile('VULN_UNCONFIGURED_TEST');
    throw new RuntimeException('Unknown toggles should not be simulated.');
} catch (InvalidArgumentException) {
}

$after = [
    'users' => (int)get_db()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'products' => (int)get_db()->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'orders' => (int)get_db()->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
];
if ($before !== $after) {
    throw new RuntimeException('Safe simulations must not mutate shop data.');
}

echo "PASS: all 69 configured toggles have complete safe simulations; unknown toggles are rejected and shop records remain unchanged.\n";
