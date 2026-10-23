<?php
/**
 * VULNAPP (PHP edition) — config + seed data.
 */

// All Flags Must Be Defined First
define('VULN_SQLI_PRODUCT_SEARCH', true);
define('VULN_IDOR_ORDER_VIEW', true);
define('VULN_PATH_TRAVERSAL_INCLUDE', true);
define('VULN_COMMAND_INJECTION', false);
define('VULN_XXE_IMPORT', false);
define('VULN_PARAMETER_POLLUTION', false);
define('VULN_COUPON_STACKING', false);
define('VULN_REDOS', false);
define('VULN_BROKEN_ACCESS_CONTROL', true);
define('VULN_HEADER_SQLI', true);
define('VULN_SQLI_LOGIN', true);
define('VULN_WEAK_PASSWORD_HASHING', true);
define('VULN_NO_RATE_LIMITING', true);
define('VULN_PREDICTABLE_SESSIONS', true);
define('VULN_SESSION_FIXATION', true);
define('VULN_USERNAME_ENUMERATION', true);
define('VULN_REFLECTED_XSS', true);
define('VULN_STORED_XSS', true);
define('VULN_CSRF', true);
define('VULN_OPEN_REDIRECT', true);
define('VULN_IDOR_PROFILE_EDIT', true);
define('VULN_PRICE_QUANTITY_TAMPERING', true);
define('VULN_MASS_ASSIGNMENT_CHECKOUT', true);
define('VULN_UNRESTRICTED_FILE_UPLOAD', true);
define('VULN_PATH_TRAVERSAL_DOWNLOAD', true);
define('VULN_SSRF', true);
define('VULN_INSECURE_DESERIALIZATION', true);
define('VULN_WEAK_REMEMBER_ME', true);
define('VULN_PREDICTABLE_RESET_TOKEN', true);
define('VULN_RACE_CONDITION', true);
define('VULN_RACE_MULTI_ENDPOINT', true);
define('VULN_RACE_SINGLE_PACKET', true);
define('VULN_RACE_DOUBLE_SPEND', true);
define('VULN_RACE_VOUCHER_OVERRUN', true);
define('VULN_CLICKJACKING_DRAG', true);
define('VULN_CSRF_METHOD_OVERRIDE', true);
define('VULN_CSRF_TOKEN_LEAK', true);
define('VULN_CSRF_FAIL_OPEN', true);
define('VULN_EXPOSED_SOURCE_MAP', true);
define('VULN_COOKIE_DEBUG_MODE', true);
define('VULN_AUTOCOMPLETE_ENABLED', true);
define('VULN_MISSING_CACHE_CONTROL', true);
define('VULN_VERBOSE_API_JSON', true);
define('VULN_VERBOSE_ERRORS', false);
define('VULN_EXPOSED_BACKUPS', true);
define('VULN_HARDCODED_CREDS', true);
define('VULN_DEBUG_ENDPOINTS', true);
define('VULN_MISSING_HEADERS', true);
define('VULN_WORKFLOW_STEP_SKIP', true);
define('VULN_ROUNDING_ERROR', true);
define('VULN_INTEGER_OVERFLOW', true);
define('VULN_NEGATIVE_REFUND', true);
define('VULN_LOYALTY_ABUSE', true);
define('VULN_HORIZONTAL_PRIV_ESC', true);
define('VULN_VERTICAL_PRIV_ESC', true);
define('VULN_HTTP_METHOD_SWAP', true);
define('VULN_URL_QUIRKS', true);
define('VULN_SPOOFABLE_ACCESS_CONTROL', true);
define('VULN_DOM_CLOBBERING', true);
define('VULN_CLIENT_PROTOTYPE_POLLUTION', true);
define('VULN_POSTMESSAGE_NO_ORIGIN', true);
define('VULN_UNSAFE_EVAL', true);
define('VULN_CLIENT_SIDE_VAL_TRUST', true);
define('VULN_HTTP_VERB_TAMPERING', true);
define('VULN_UNICODE_NORMALIZATION', true);
define('VULN_NULL_BYTE_INJECTION', true);
define('VULN_CASE_SENSITIVITY_BYPASS', true);
define('VULN_TRAILING_DOT_SLASH', true);
define('VULN_SQL_COMMENT_FILTER_BYPASS', true);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$databaseUrl = getenv('DATABASE_URL');
define('USE_POSTGRES', $databaseUrl !== false && $databaseUrl !== '');

if (!USE_POSTGRES) {
    $dataDir = getenv('APP_DATA_DIR') ?: __DIR__ . '/data';
    if (!is_dir($dataDir) && !mkdir($dataDir, 0775, true) && !is_dir($dataDir)) {
        throw new RuntimeException('Unable to create the application data directory.');
    }
    define('DB_PATH', $dataDir . '/vulnapp.db');
}
define('UPLOAD_DIR', __DIR__ . '/uploads/');

function get_db() {
    static $pdo = null;
    if ($pdo === null) {
        if (USE_POSTGRES) {
            $databaseUrl = getenv('DATABASE_URL');
            $parts = parse_url($databaseUrl);
            if ($parts === false || empty($parts['host']) || empty($parts['path'])) {
                throw new RuntimeException('DATABASE_URL is not a valid PostgreSQL connection URL.');
            }

            $query = [];
            parse_str($parts['query'] ?? '', $query);
            $sslMode = $query['sslmode'] ?? 'require';
            $channelBinding = $query['channel_binding'] ?? null;
            $dsn = sprintf(
                'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
                $parts['host'],
                $parts['port'] ?? 5432,
                rawurldecode(ltrim($parts['path'], '/')),
                $sslMode
            );
            if ($channelBinding !== null) {
                $dsn .= ';channel_binding=' . $channelBinding;
            }

            $pdo = new PDO(
                $dsn,
                rawurldecode($parts['user'] ?? ''),
                rawurldecode($parts['pass'] ?? ''),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } else {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        if (USE_POSTGRES) {
            $pdo->exec("SET TIME ZONE 'UTC'");
        }
        initialize_schema($pdo);
    }
    return $pdo;
}

function format_eat_datetime($timestamp) {
    if ($timestamp === null || $timestamp === '') {
        return '';
    }

    $dateTime = new DateTimeImmutable((string)$timestamp, new DateTimeZone('UTC'));
    return $dateTime
        ->setTimezone(new DateTimeZone('Africa/Kampala'))
        ->format('Y-m-d H:i:s T');
}

function insert_and_get_id(PDO $pdo, $sql, array $params = []) {
    if (USE_POSTGRES) {
        $stmt = $pdo->prepare($sql . ' RETURNING id');
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $pdo->lastInsertId();
}

function vulnerability_flag_catalog() {
    return [
        ['VULN-001', 'SQL Injection in Product Search'],
        ['VULN-002', 'IDOR on Order Viewing'],
        ['VULN-003', 'Path Traversal in Admin Panel'],
        ['VULN-004', 'SQL Injection in Login'],
        ['VULN-005', 'Weak Password Hashing'],
        ['VULN-006', 'No Login Rate Limiting'],
        ['VULN-007', 'Predictable Session Tokens'],
        ['VULN-008', 'Session Fixation'],
        ['VULN-009', 'Username Enumeration'],
        ['VULN-010', 'Error-Based SQL Injection'],
        ['VULN-011', 'Blind Boolean SQL Injection'],
        ['VULN-012', 'Blind Time-Based SQL Injection'],
        ['VULN-013', 'Second-Order SQL Injection'],
        ['VULN-014', 'Header-Based SQL Injection'],
        ['VULN-015', 'Reflected Cross-Site Scripting'],
        ['VULN-016', 'Stored Cross-Site Scripting'],
        ['VULN-017', 'Stored Cross-Site Scripting Filter Bypass'],
        ['VULN-018', 'Cross-Site Scripting via Attribute Injection'],
        ['VULN-019', 'DOM-Based Cross-Site Scripting'],
        ['VULN-020', 'Insecure Use of eval'],
        ['VULN-021', 'Broken Access Control'],
        ['VULN-022', 'IDOR on Profile Editing'],
        ['VULN-023', 'Horizontal Privilege Escalation'],
        ['VULN-024', 'Insecure Remember-Me Cookie'],
        ['VULN-025', 'HTTP Method Tampering'],
        ['VULN-026', 'URL Quirk Access-Control Bypass'],
        ['VULN-027', 'Price and Quantity Tampering'],
        ['VULN-028', 'Mass Assignment at Checkout'],
        ['VULN-029', 'Integer Overflow in Cart Quantity'],
        ['VULN-030', 'Unrestricted File Upload'],
        ['VULN-031', 'Path Traversal in Receipt Download'],
        ['VULN-032', 'Server-Side Request Forgery'],
        ['VULN-033', 'XML External Entity Injection'],
        ['VULN-034', 'Verbose Error Exposure'],
        ['VULN-035', 'Exposed Backup Files'],
        ['VULN-036', 'Debug Endpoint Information Disclosure'],
        ['VULN-037', 'Missing Security Headers'],
        ['VULN-038', 'Vertical Privilege Escalation'],
        ['VULN-039', 'SQL Comment Filter Bypass'],
    ];
}

function seed_vulnerability_flags(PDO $pdo) {
    $markerKey = 'vulnerability_flag_catalog_v4';
    $ownsTransaction = !$pdo->inTransaction();
    $savepoint = 'seed_vulnerability_flags_sp';

    if ($ownsTransaction) {
        $pdo->beginTransaction();
    } else {
        $pdo->exec("SAVEPOINT $savepoint");
    }

    try {
        if (USE_POSTGRES) {
            $pdo->query('SELECT pg_advisory_xact_lock(741902381)')->fetchColumn();
        }

        $marker = $pdo->prepare('SELECT metadata_value FROM app_metadata WHERE metadata_key = ?');
        $marker->execute([$markerKey]);
        if ($marker->fetchColumn() !== false) {
            if ($ownsTransaction) {
                $pdo->commit();
            } else {
                $pdo->exec("RELEASE SAVEPOINT $savepoint");
            }
            return;
        }

        $nextId = (int)$pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM flags')->fetchColumn();
        $find = $pdo->prepare('SELECT id FROM flags WHERE vulnerability_name = ?');
        $insert = $pdo->prepare(
            'INSERT INTO flags (id, vuln_id, vulnerability_name, flag)
             VALUES (?, ?, ?, ?) ON CONFLICT DO NOTHING'
        );
        $inserted = 0;

        foreach (vulnerability_flag_catalog() as [$vulnerabilityId, $vulnerabilityName]) {
            $find->execute([$vulnerabilityName]);
            if ($find->fetchColumn() !== false) {
                continue;
            }

            $flag = 'FLAG{' . strtoupper(bin2hex(random_bytes(16))) . '}';
            $insert->execute([$nextId++, $vulnerabilityId, $vulnerabilityName, $flag]);
            $inserted += $insert->rowCount();
        }

        $recordMarker = $pdo->prepare(
            'INSERT INTO app_metadata (metadata_key, metadata_value)
             VALUES (?, ?) ON CONFLICT (metadata_key) DO NOTHING'
        );
        $recordMarker->execute([$markerKey, gmdate('c')]);

        if ($ownsTransaction) {
            $pdo->commit();
        } else {
            $pdo->exec("RELEASE SAVEPOINT $savepoint");
        }

        if ($inserted > 0) {
            error_log("Seeded $inserted missing vulnerability flags into the active database.");
        }
    } catch (Throwable $exception) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        } elseif ($pdo->inTransaction()) {
            $pdo->exec("ROLLBACK TO SAVEPOINT $savepoint");
            $pdo->exec("RELEASE SAVEPOINT $savepoint");
        }
        error_log('Vulnerability flag catalog initialization failed: ' . $exception->getMessage());
    }
}

function save_product_image_upload($field = 'product_image') {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('The product image could not be uploaded.');
    }

    $image = getimagesize($file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    $mimeType = $image['mime'] ?? '';
    if ($image === false || !isset($extensions[$mimeType])) {
        throw new RuntimeException('Choose a valid JPG, PNG, GIF, or WebP image.');
    }

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0775, true) && !is_dir(UPLOAD_DIR)) {
        throw new RuntimeException('The product image directory is unavailable.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
        throw new RuntimeException('The product image could not be saved.');
    }

    return '/uploads/' . $filename;
}

function initialize_schema(PDO $pdo) {
    $autoId = USE_POSTGRES ? 'BIGSERIAL PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $timestampType = USE_POSTGRES ? 'TIMESTAMP' : 'DATETIME';
    $tables = [
        'users' => "(id $autoId, username TEXT, password TEXT, email TEXT, is_admin INTEGER DEFAULT 0, ssn TEXT, balance REAL DEFAULT 0.0)",
        'products' => "(id $autoId, name TEXT, description TEXT, price REAL, image_url TEXT)",
        'comments' => "(id $autoId, product_id INTEGER, author TEXT, body TEXT)",
        'orders' => "(id $autoId, user_id INTEGER, total REAL, shipping_address TEXT, status TEXT DEFAULT 'pending', created_at $timestampType DEFAULT CURRENT_TIMESTAMP)",
        'order_items' => "(id $autoId, order_id INTEGER, product_id INTEGER, quantity INTEGER)",
        'reviews' => "(id $autoId, product_id INTEGER, user_id INTEGER, comment TEXT)",
        'access_logs' => "(id $autoId, ua TEXT)",
        'remember_tokens' => '(id INTEGER PRIMARY KEY, user_id INTEGER, token TEXT)',
        'flags' => "(id $autoId, vuln_id TEXT, flag TEXT)",
        'hall_of_fame' => "(id $autoId, username TEXT, email TEXT, user_id INTEGER, vuln_id TEXT, flag_id INTEGER, learner_name TEXT, learner_email TEXT, award_code TEXT, timestamp $timestampType DEFAULT CURRENT_TIMESTAMP)",
        'automatic_awards' => "(id $autoId, username TEXT, email TEXT, user_id INTEGER, vuln_id TEXT, vulnerability_name TEXT, learner_name TEXT, learner_email TEXT, award_code TEXT, timestamp $timestampType DEFAULT CURRENT_TIMESTAMP)",
        'award_claims' => "(id $autoId, challenge_key TEXT NOT NULL, learner_email TEXT NOT NULL, award_table TEXT, award_id INTEGER, created_at $timestampType DEFAULT CURRENT_TIMESTAMP)",
        'activity_logs' => "(id $autoId, username TEXT, action TEXT, timestamp $timestampType DEFAULT CURRENT_TIMESTAMP)",
    ];
    foreach ($tables as $table => $definition) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS $table $definition");
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS app_metadata (metadata_key TEXT PRIMARY KEY, metadata_value TEXT)');

    $columns = [
        'users' => [
            'email' => 'TEXT',
            'is_admin' => 'INTEGER DEFAULT 0',
            'ssn' => 'TEXT',
            'balance' => 'REAL DEFAULT 0.0',
        ],
        'products' => ['image_url' => 'TEXT'],
        'orders' => [
            'shipping_address' => 'TEXT',
            'status' => "TEXT DEFAULT 'pending'",
            'created_at' => USE_POSTGRES ? 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP' : 'DATETIME',
        ],
        'flags' => ['vulnerability_name' => 'TEXT'],
        'hall_of_fame' => [
            'timestamp' => USE_POSTGRES ? 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP' : 'DATETIME',
            'email' => 'TEXT',
            'user_id' => 'INTEGER',
            'flag_id' => 'INTEGER',
            'learner_name' => 'TEXT',
            'learner_email' => 'TEXT',
            'award_code' => 'TEXT',
        ],
        'automatic_awards' => [
            'username' => 'TEXT',
            'email' => 'TEXT',
            'user_id' => 'INTEGER',
            'vuln_id' => 'TEXT',
            'vulnerability_name' => 'TEXT',
            'learner_name' => 'TEXT',
            'learner_email' => 'TEXT',
            'award_code' => 'TEXT',
            'timestamp' => USE_POSTGRES ? 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP' : 'DATETIME',
        ],
        'award_claims' => [
            'challenge_key' => 'TEXT',
            'learner_email' => 'TEXT',
            'award_table' => 'TEXT',
            'award_id' => 'INTEGER',
            'created_at' => USE_POSTGRES ? 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP' : 'DATETIME',
        ],
        'activity_logs' => ['timestamp' => USE_POSTGRES ? 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP' : 'DATETIME'],
    ];

    foreach ($columns as $table => $definitions) {
        if (USE_POSTGRES) {
            $stmt = $pdo->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ?');
            $stmt->execute([$table]);
            $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $existingColumns = $pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_COLUMN, 1);
        }
        foreach ($definitions as $column => $definition) {
            if (!in_array($column, $existingColumns, true)) {
                $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
            }
        }
    }

    $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS flags_vulnerability_name_unique ON flags (vulnerability_name)');
    $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS hall_of_fame_award_code_unique ON hall_of_fame (award_code)');
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS hall_of_fame_identity_award_unique
        ON hall_of_fame (user_id, flag_id, LOWER(learner_email))
        WHERE learner_email IS NOT NULL AND learner_email <> ''");
    $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS automatic_awards_code_unique ON automatic_awards (award_code)');
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS automatic_awards_identity_unique
        ON automatic_awards (user_id, vuln_id, LOWER(learner_email))");
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS award_claims_identity_unique
        ON award_claims (challenge_key, LOWER(learner_email))
        WHERE learner_email <> ''");
    seed_vulnerability_flags($pdo);
    backfill_award_codes($pdo);
    backfill_award_claim_registry($pdo);

    if (!USE_POSTGRES) {
        $pdo->exec("CREATE TRIGGER IF NOT EXISTS orders_created_at_default AFTER INSERT ON orders WHEN NEW.created_at IS NULL BEGIN UPDATE orders SET created_at = CURRENT_TIMESTAMP WHERE id = NEW.id; END");
        $pdo->exec("CREATE TRIGGER IF NOT EXISTS hall_of_fame_timestamp_default AFTER INSERT ON hall_of_fame WHEN NEW.timestamp IS NULL BEGIN UPDATE hall_of_fame SET timestamp = CURRENT_TIMESTAMP WHERE id = NEW.id; END");
        $pdo->exec("CREATE TRIGGER IF NOT EXISTS activity_logs_timestamp_default AFTER INSERT ON activity_logs WHEN NEW.timestamp IS NULL BEGIN UPDATE activity_logs SET timestamp = CURRENT_TIMESTAMP WHERE id = NEW.id; END");
        $pdo->exec("CREATE TRIGGER IF NOT EXISTS automatic_awards_timestamp_default AFTER INSERT ON automatic_awards WHEN NEW.timestamp IS NULL BEGIN UPDATE automatic_awards SET timestamp = CURRENT_TIMESTAMP WHERE id = NEW.id; END");
    }
}

function generate_certificate_number(PDO $pdo) {
    $exists = $pdo->prepare(
        'SELECT 1 FROM hall_of_fame WHERE award_code = ?
         UNION ALL
         SELECT 1 FROM automatic_awards WHERE award_code = ?
         LIMIT 1'
    );
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $code = (string)random_int(100000000, 999999999);
        $exists->execute([$code, $code]);
        if ($exists->fetchColumn() === false) {
            return $code;
        }
    }

    throw new RuntimeException('Unable to generate a unique certificate number.');
}

function backfill_award_codes(PDO $pdo) {
    $missing = $pdo->query("SELECT id FROM hall_of_fame WHERE award_code IS NULL OR award_code = ''")
        ->fetchAll(PDO::FETCH_COLUMN);
    $update = $pdo->prepare("UPDATE hall_of_fame SET award_code = ? WHERE id = ? AND (award_code IS NULL OR award_code = '')");

    foreach ($missing as $id) {
        $update->execute([generate_certificate_number($pdo), $id]);
    }
}

function backfill_award_claim_registry(PDO $pdo) {
    $markerKey = 'award_claim_registry_v2';
    $marker = $pdo->prepare('SELECT metadata_value FROM app_metadata WHERE metadata_key = ?');
    $marker->execute([$markerKey]);
    if ($marker->fetchColumn() !== false) {
        return;
    }

    $insert = $pdo->prepare(
        'INSERT INTO award_claims (challenge_key, learner_email, award_table, award_id)
         VALUES (?, ?, ?, ?) ON CONFLICT DO NOTHING'
    );
    $flagAwards = $pdo->query(
        "SELECT h.id, h.vuln_id, h.learner_email,
                COALESCE(f.vulnerability_name, CASE h.vuln_id
                    WHEN 'VULN-004' THEN 'SQL Injection in Login'
                    ELSE h.vuln_id END) AS vulnerability_name
         FROM hall_of_fame h
         LEFT JOIN flags f ON h.flag_id = f.id
         WHERE h.learner_email IS NOT NULL AND h.learner_email <> ''
         ORDER BY h.id"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($flagAwards as $award) {
        $challengeKey = award_claim_challenge_key($award['vuln_id'], $award['vulnerability_name']);
        $insert->execute([$challengeKey, $award['learner_email'], 'hall_of_fame', $award['id']]);
    }

    $automaticAwards = $pdo->query(
        "SELECT id, vuln_id, vulnerability_name, learner_email FROM automatic_awards
         WHERE learner_email IS NOT NULL AND learner_email <> ''
         ORDER BY id"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($automaticAwards as $award) {
        $challengeKey = award_claim_challenge_key($award['vuln_id'], $award['vulnerability_name']);
        $insert->execute([$challengeKey, $award['learner_email'], 'automatic_awards', $award['id']]);
    }

    $recordMarker = $pdo->prepare(
        'INSERT INTO app_metadata (metadata_key, metadata_value) VALUES (?, ?)
         ON CONFLICT (metadata_key) DO NOTHING'
    );
    $recordMarker->execute([$markerKey, gmdate('c')]);
}

function award_claim_challenge_key(string $vulnerabilityId, ?string $vulnerabilityName) {
    $challengeName = trim((string)$vulnerabilityName);
    return strtolower($challengeName !== '' ? $challengeName : trim($vulnerabilityId));
}

function reserve_award_claim(PDO $pdo, string $challengeKey, string $learnerEmail) {
    try {
        if ($pdo->inTransaction()) {
            $pdo->exec('SAVEPOINT reserve_award_claim_sp');
        }
        $reservation = $pdo->prepare(
            'INSERT INTO award_claims (challenge_key, learner_email)
             VALUES (?, ?)'
        );
        $reservation->execute([$challengeKey, $learnerEmail]);
        if ($pdo->inTransaction()) {
            $pdo->exec('RELEASE SAVEPOINT reserve_award_claim_sp');
        }
        return $reservation->rowCount() === 1;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->exec('ROLLBACK TO SAVEPOINT reserve_award_claim_sp');
        }
        if ($e->getCode() === '23505' || str_contains($e->getMessage(), 'UNIQUE constraint') || str_contains($e->getMessage(), 'violates unique constraint')) {
            return false;
        }
        throw $e;
    }
}

function find_registered_award(PDO $pdo, string $challengeKey, string $learnerEmail) {
    $claim = $pdo->prepare(
        'SELECT award_table, award_id FROM award_claims
         WHERE challenge_key = ? AND LOWER(learner_email) = LOWER(?)'
    );
    $claim->execute([$challengeKey, $learnerEmail]);
    $registeredClaim = $claim->fetch(PDO::FETCH_ASSOC);
    if (!$registeredClaim || $registeredClaim['award_id'] === null) {
        throw new RuntimeException('The existing award claim could not be resolved.');
    }

    $table = $registeredClaim['award_table'];
    if (!in_array($table, ['hall_of_fame', 'automatic_awards'], true)) {
        throw new RuntimeException('The existing award claim has an invalid record source.');
    }
    $awardQuery = $pdo->prepare(
        "SELECT id, user_id, timestamp, award_code, learner_name, learner_email
         FROM $table WHERE id = ?"
    );
    $awardQuery->execute([$registeredClaim['award_id']]);
    $award = $awardQuery->fetch(PDO::FETCH_ASSOC);
    if (!$award) {
        throw new RuntimeException('The registered award record no longer exists.');
    }
    $award['award_table'] = $table;
    return $award;
}

function ensure_registered_award_code(PDO $pdo, array $award) {
    if (!empty($award['award_code'])) {
        return $award;
    }

    $table = $award['award_table'] ?? null;
    if (!in_array($table, ['hall_of_fame', 'automatic_awards'], true)) {
        throw new RuntimeException('The existing award has an invalid record source.');
    }
    $award['award_code'] = generate_certificate_number($pdo);
    $update = $pdo->prepare("UPDATE $table SET award_code = ? WHERE id = ?");
    $update->execute([$award['award_code'], $award['id']]);
    return $award;
}

function record_vulnerability_award(
    PDO $pdo,
    array $user,
    array $flag,
    string $learnerName,
    string $learnerEmail
) {
    if (($flag['id'] ?? null) === null) {
        return record_automatic_award($pdo, $user, $flag, $learnerName, $learnerEmail);
    }

    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }

    try {
        $flagId = (int)$flag['id'];
        $challengeKey = award_claim_challenge_key(
            (string)$flag['vuln_id'],
            is_string($flag['vulnerability_name'] ?? null) ? $flag['vulnerability_name'] : null
        );
        if (!reserve_award_claim($pdo, $challengeKey, $learnerEmail)) {
            $award = find_registered_award($pdo, $challengeKey, $learnerEmail);
            $award = ensure_registered_award_code($pdo, $award);
            if ($ownsTransaction) {
                $pdo->commit();
            }
            return $award;
        }

        $awardCode = generate_certificate_number($pdo);
        $awardId = insert_and_get_id(
            $pdo,
            'INSERT INTO hall_of_fame (username, email, user_id, vuln_id, flag_id, learner_name, learner_email, award_code)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $user['username'],
                $user['email'] ?? '',
                $user['id'],
                $flag['vuln_id'],
                $flagId,
                $learnerName,
                $learnerEmail,
                $awardCode,
            ]
        );
        $claimUpdate = $pdo->prepare(
            "UPDATE award_claims SET award_table = 'hall_of_fame', award_id = ?
             WHERE challenge_key = ? AND LOWER(learner_email) = LOWER(?)"
        );
        $claimUpdate->execute([$awardId, $challengeKey, $learnerEmail]);
        $timestampQuery = $pdo->prepare('SELECT timestamp FROM hall_of_fame WHERE id = ?');
        $timestampQuery->execute([$awardId]);
        $award = [
            'id' => $awardId,
            'user_id' => $user['id'],
            'award_table' => 'hall_of_fame',
            'timestamp' => $timestampQuery->fetchColumn(),
            'award_code' => $awardCode,
            'learner_name' => $learnerName,
            'learner_email' => $learnerEmail,
        ];
        if ($ownsTransaction) {
            $pdo->commit();
        }
        return $award;
    } catch (Throwable $exception) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function record_automatic_award(
    PDO $pdo,
    array $user,
    array $vulnerability,
    string $learnerName,
    string $learnerEmail
) {
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }

    try {
        $vulnerabilityId = (string)$vulnerability['vuln_id'];
        $challengeKey = award_claim_challenge_key(
            $vulnerabilityId,
            is_string($vulnerability['vulnerability_name'] ?? null) ? $vulnerability['vulnerability_name'] : null
        );
        if (!reserve_award_claim($pdo, $challengeKey, $learnerEmail)) {
            $award = find_registered_award($pdo, $challengeKey, $learnerEmail);
            $award = ensure_registered_award_code($pdo, $award);
            if ($ownsTransaction) {
                $pdo->commit();
            }
            return $award;
        }

        $awardCode = generate_certificate_number($pdo);
        $awardId = insert_and_get_id(
            $pdo,
            'INSERT INTO automatic_awards
                (username, email, user_id, vuln_id, vulnerability_name, learner_name, learner_email, award_code)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $user['username'],
                $user['email'] ?? '',
                $user['id'],
                $vulnerabilityId,
                $vulnerability['vulnerability_name'],
                $learnerName,
                $learnerEmail,
                $awardCode,
            ]
        );
        $claimUpdate = $pdo->prepare(
            "UPDATE award_claims SET award_table = 'automatic_awards', award_id = ?
             WHERE challenge_key = ? AND LOWER(learner_email) = LOWER(?)"
        );
        $claimUpdate->execute([$awardId, $challengeKey, $learnerEmail]);
        $timestampQuery = $pdo->prepare('SELECT timestamp FROM automatic_awards WHERE id = ?');
        $timestampQuery->execute([$awardId]);
        $award = [
            'id' => $awardId,
            'user_id' => $user['id'],
            'award_table' => 'automatic_awards',
            'timestamp' => $timestampQuery->fetchColumn(),
            'award_code' => $awardCode,
            'learner_name' => $learnerName,
            'learner_email' => $learnerEmail,
        ];
        if ($ownsTransaction) {
            $pdo->commit();
        }
        return $award;
    } catch (Throwable $exception) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function seed_db($onPhaseChange = null) {
    $pdo = get_db();
    if ($onPhaseChange !== null) {
        $onPhaseChange('checking demo users');
    }
    if ((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() === 0) {
        if ($onPhaseChange !== null) {
            $onPhaseChange('seeding demo users');
        }
        $result = $pdo->exec("INSERT INTO users (username, password, email, is_admin, ssn) VALUES
        ('alice','alice123','alice@example.com',0,'123-45-6789'),
        ('bob','bobpass','bob@example.com',0,'987-65-4321'),
        ('admin','admin!2024','admin@example.com',1,'000-00-0000')");
        if ($result === false) {
            throw new RuntimeException('Demo user seed failed: ' . implode(' | ', array_filter($pdo->errorInfo())));
        }
    }

    if ($onPhaseChange !== null) {
        $onPhaseChange('checking demo products');
    }
    if ((int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() === 0) {
        if ($onPhaseChange !== null) {
            $onPhaseChange('seeding demo products');
        }
        $result = $pdo->exec("INSERT INTO products (name, description, price, image_url) VALUES
        ('Wireless Mouse','Ergonomic 2.4GHz wireless mouse',19.99,'https://images.unsplash.com/photo-1527814050087-3793815479db?auto=format&fit=crop&w=800&q=80'),
        ('Mechanical Keyboard','RGB backlit mechanical keyboard',59.99,'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=800&q=80'),
        ('USB-C Hub','7-in-1 USB-C hub with HDMI',29.99,'https://images.unsplash.com/photo-1625842268584-8f3296236761?auto=format&fit=crop&w=800&q=80'),
        ('Webcam 1080p','Full HD webcam with autofocus',34.99,'https://images.unsplash.com/photo-1598327105666-5b89351aff97?auto=format&fit=crop&w=800&q=80'),
        ('Laptop Stand','Aluminum adjustable laptop stand',24.99,'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?auto=format&fit=crop&w=800&q=80')");
        if ($result === false) {
            throw new RuntimeException('Demo product seed failed: ' . implode(' | ', array_filter($pdo->errorInfo())));
        }
    }
}

get_db();
seed_db();

class Logger {
    public $logFile = 'app.log';
    public $logMsg = 'default log entry';

    public function __destruct() {
        @file_put_contents($this->logFile, $this->logMsg, FILE_APPEND);
    }
}
