<?php

function safe_lab_simulation_profile(string $toggle): array {
    if (!preg_match('/^VULN_[A-Z0-9_]+$/', $toggle) || !defined($toggle)) {
        throw new InvalidArgumentException('Choose a configured vulnerability switch.');
    }

    $upper = strtoupper($toggle);
    if (str_contains($upper, 'SQLI') || str_contains($upper, 'SQL_')) {
        return [
            'domain' => 'Database input',
            'scenario' => 'A fixed synthetic request is compared with an in-memory query model.',
            'observation' => 'The simulation shows how treating input as data keeps the sample query structure unchanged.',
            'remediation' => 'Use parameterized queries, strict data types, and least-privilege database access.',
        ];
    }
    if (
        str_contains($upper, 'IDOR')
        || str_contains($upper, 'ACCESS_CONTROL')
        || str_contains($upper, 'PRIV_ESC')
        || str_contains($upper, 'HTTP_METHOD')
        || str_contains($upper, 'HTTP_VERB')
        || str_contains($upper, 'URL_QUIRKS')
    ) {
        return [
            'domain' => 'Authorization',
            'scenario' => 'Synthetic learner and owner identities are checked against an in-memory resource.',
            'observation' => 'The simulation compares the requested action with the sample owner and role policy; it never reads or changes real account data.',
            'remediation' => 'Authorize every action on the server using the authenticated identity and resource ownership.',
        ];
    }
    if (
        str_contains($upper, 'PATH_TRAVERSAL')
        || str_contains($upper, 'FILE_UPLOAD')
        || str_contains($upper, 'EXPOSED_BACKUP')
        || str_contains($upper, 'SOURCE_MAP')
        || str_contains($upper, 'COMMAND_INJECTION')
        || str_contains($upper, 'XXE')
        || str_contains($upper, 'SSRF')
    ) {
        return [
            'domain' => 'Files and outbound resources',
            'scenario' => 'A fixed filename or URL is evaluated against a virtual allowlist.',
            'observation' => 'The simulation reports whether the synthetic value passes policy; it performs no filesystem access, parsing, command execution, or network request.',
            'remediation' => 'Use fixed allowlists, canonical path checks, safe parsers, and explicit outbound destination controls.',
        ];
    }
    if (
        str_contains($upper, 'XSS')
        || str_contains($upper, 'DOM_')
        || str_contains($upper, 'PROTOTYPE')
        || str_contains($upper, 'POSTMESSAGE')
        || str_contains($upper, 'UNSAFE_EVAL')
        || str_contains($upper, 'CLIENT_SIDE')
        || str_contains($upper, 'CLICKJACKING')
        || str_contains($upper, 'CSRF')
    ) {
        return [
            'domain' => 'Browser and request integrity',
            'scenario' => 'A fixed synthetic page value or state-changing request is checked in a browser-policy model.',
            'observation' => 'The simulation displays the encoded value or rejected request decision as text; it does not execute scripts or change shop state.',
            'remediation' => 'Encode output by context, validate message origins, enforce CSRF protections, and set restrictive browser policies.',
        ];
    }
    if (
        str_contains($upper, 'RACE')
        || str_contains($upper, 'DOUBLE_SPEND')
        || str_contains($upper, 'VOUCHER')
        || str_contains($upper, 'WORKFLOW')
        || str_contains($upper, 'PRICE')
        || str_contains($upper, 'MASS_ASSIGNMENT')
        || str_contains($upper, 'ROUNDING')
        || str_contains($upper, 'INTEGER')
        || str_contains($upper, 'REFUND')
        || str_contains($upper, 'LOYALTY')
        || str_contains($upper, 'COUPON')
    ) {
        return [
            'domain' => 'Business rules and state',
            'scenario' => 'A fixed synthetic order and ledger are evaluated against a documented rule.',
            'observation' => 'The simulation compares the sample state before and after a modeled check; no balance, order, or inventory record is written.',
            'remediation' => 'Validate business invariants on the server and apply state transitions atomically.',
        ];
    }
    if (
        str_contains($upper, 'PASSWORD')
        || str_contains($upper, 'SESSION')
        || str_contains($upper, 'REMEMBER')
        || str_contains($upper, 'RESET')
        || str_contains($upper, 'LOGIN')
        || str_contains($upper, 'USERNAME')
        || str_contains($upper, 'RATE_LIMIT')
        || str_contains($upper, 'HARDCODED')
    ) {
        return [
            'domain' => 'Identity and session handling',
            'scenario' => 'A synthetic account and session lifecycle are checked without authenticating or changing any account.',
            'observation' => 'The simulation reports the expected policy decision for the fixed sample identity.',
            'remediation' => 'Use password hashing, rotate session identifiers after authentication, protect tokens, and rate-limit sensitive flows.',
        ];
    }

    return [
        'domain' => 'Input and information handling',
        'scenario' => 'A fixed synthetic value is checked against a documented validation or disclosure policy.',
        'observation' => 'The simulation presents the policy result using sample data only; it does not inspect application secrets or production records.',
        'remediation' => 'Validate at trust boundaries, minimize disclosed data, and apply explicit secure defaults.',
    ];
}
