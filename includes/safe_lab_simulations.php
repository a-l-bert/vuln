<?php

function safe_lab_simulation_profile(string $toggle): array
{
    if (!preg_match('/^VULN_[A-Z0-9_]+$/', $toggle) || !defined($toggle) || !is_bool(constant($toggle))) {
        throw new InvalidArgumentException('Choose a configured vulnerability switch.');
    }

    if (preg_match('/SQL|DATABASE|QUERY|INJECTION/', $toggle)) {
        $profile = [
            'domain' => 'Database input',
            'scenario' => 'A fixed search term is matched against a tiny in-memory product list.',
            'remediation' => 'Use parameterized queries and validate input according to the expected data type.',
            'check' => 'database',
        ];
    } elseif (preg_match('/AUTHZ|ACCESS|ADMIN|ROLE|PRIVILEGE|OWNERSHIP|PERMISSION/', $toggle)) {
        $profile = [
            'domain' => 'Authorization',
            'scenario' => 'A synthetic learner requests a record owned by a different sample account.',
            'remediation' => 'Check the authenticated user and required permission for every protected action.',
            'check' => 'authorization',
        ];
    } elseif (preg_match('/FILE|PATH|UPLOAD|SSRF|URL|RESOURCE|INCLUDE/', $toggle)) {
        $profile = [
            'domain' => 'Files and outbound resources',
            'scenario' => 'A virtual path outside a fixed sandbox allowlist is evaluated without accessing a file or network.',
            'remediation' => 'Resolve paths under an explicit sandbox and allowlist outbound destinations.',
            'check' => 'resources',
        ];
    } elseif (preg_match('/XSS|CSRF|HTML|BROWSER|REQUEST|HEADER|COOKIE|SCRIPT/', $toggle)) {
        $profile = [
            'domain' => 'Browser and request integrity',
            'scenario' => 'A fixed display value is encoded and compared with its expected inert representation.',
            'remediation' => 'Escape output for its context and validate request origin tokens on state changes.',
            'check' => 'browser',
        ];
    } elseif (preg_match('/PRICE|CART|ORDER|PURCHASE|COUPON|PAYMENT|BUSINESS|STOCK|QUANTITY/', $toggle)) {
        $profile = [
            'domain' => 'Business rules',
            'scenario' => 'A synthetic purchase exceeds the sample account balance and should be declined.',
            'remediation' => 'Recalculate prices and enforce inventory, balance, and order rules on the server.',
            'check' => 'business',
        ];
    } elseif (preg_match('/LOGIN|PASSWORD|SESSION|IDENTITY|CREDENTIAL|BRUTE|MFA|TOKEN/', $toggle)) {
        $profile = [
            'domain' => 'Identity and session handling',
            'scenario' => 'A fixed sample credential is checked against an in-memory password hash.',
            'remediation' => 'Use password hashing, rate limits, secure session settings, and rotation after authentication.',
            'check' => 'identity',
        ];
    } else {
        $profile = [
            'domain' => 'General input and state handling',
            'scenario' => 'A fixed sample identifier is checked against a narrow allowlist.',
            'remediation' => 'Validate input at the server boundary and enforce least privilege.',
            'check' => 'general',
        ];
    }

    [$passed, $observation] = safe_lab_simulation_check($profile['check']);
    $profile['passed'] = $passed;
    $profile['observation'] = $observation;
    unset($profile['check']);

    return $profile;
}

function safe_lab_certificate_details(string $toggle): array
{
    safe_lab_simulation_profile($toggle);

    $name = str_replace('_', ' ', substr($toggle, 5));

    return [
        'vuln_id' => 'SAFE-LAB-' . substr($toggle, 5),
        'vulnerability_name' => 'Safe Lab Completion: ' . ucwords(strtolower($name)),
    ];
}

function safe_lab_simulation_check(string $check): array
{
    if ($check === 'database') {
        $queryTemplate = 'SELECT name FROM sample_products WHERE name = ?';
        $sampleProducts = ['Wireless Mouse', 'USB Hub'];
        $boundValue = 'Wireless Mouse';
        $matches = array_filter($sampleProducts, static fn($name) => $name === $boundValue);
        $passed = str_contains($queryTemplate, '?') && count($matches) === 1;

        return [$passed, 'PASS: a placeholder and one synthetic match were verified; no SQL was executed.'];
    }

    if ($check === 'authorization') {
        $actorId = 11;
        $ownerId = 12;
        $isAdmin = false;
        $allowed = $isAdmin || $actorId === $ownerId;

        return [!$allowed, 'PASS: access to another sample account was denied by the in-memory ownership check.'];
    }

    if ($check === 'resources') {
        $virtualPath = '../outside/sample.txt';
        $allowedPaths = ['/sandbox/catalog.csv'];
        $allowed = in_array($virtualPath, $allowedPaths, true);

        return [!$allowed, 'PASS: the virtual path was rejected by the fixed allowlist; no file or network operation occurred.'];
    }

    if ($check === 'browser') {
        $sampleValue = '<demo>';
        $encodedValue = htmlspecialchars($sampleValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $passed = $encodedValue === '&lt;demo&gt;';

        return [$passed, 'PASS: the fixed display value matched its inert HTML-encoded form; no script was executed.'];
    }

    if ($check === 'business') {
        $sampleBalance = 10;
        $sampleDebit = 12;
        $accepted = $sampleDebit <= $sampleBalance;

        return [!$accepted, 'PASS: the synthetic purchase exceeding the sample balance was declined.'];
    }

    if ($check === 'identity') {
        $sampleHash = password_hash('training-only-sample', PASSWORD_DEFAULT);
        $validCredential = password_verify('training-only-sample', $sampleHash);

        return [$validCredential, 'PASS: a fixed sample credential matched an in-memory password hash.'];
    }

    $sampleIdentifier = 'sample-item_1';
    $valid = preg_match('/^[A-Za-z0-9_-]{1,32}$/D', $sampleIdentifier) === 1;

    return [$valid, 'PASS: the fixed sample identifier matched the narrow allowlist.'];
}
