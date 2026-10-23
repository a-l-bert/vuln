<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$validUser = getenv('LAB_AUTH_USER');
$validPass = getenv('LAB_AUTH_PASS');

if ($validUser === false || $validUser === '' || $validPass === false || $validPass === '') {
    http_response_code(503);
    exit('Lab access is not configured.');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $forwardedProto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $forwardedProto === 'https';

    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secureCookie,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

if (!empty($_SESSION['lab_access_authenticated'])) {
    return;
}

$requestUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
$returnTo = $_POST['return_to'] ?? $requestUri;
if (
    !is_string($returnTo)
    || $returnTo === ''
    || $returnTo[0] !== '/'
    || str_starts_with($returnTo, '//')
    || str_contains($returnTo, '\\')
    || preg_match('/[\r\n]/', $returnTo)
) {
    $returnTo = '/';
}

$loginError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lab_access_login'])) {
    $submittedUser = is_string($_POST['username'] ?? null) ? $_POST['username'] : '';
    $submittedPass = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    $sessionToken = (string)($_SESSION['lab_access_csrf'] ?? '');

    if ($sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
        http_response_code(403);
        $loginError = 'Your sign-in form expired. Please try again.';
    } elseif (
        !hash_equals($validUser, $submittedUser)
        || !hash_equals($validPass, $submittedPass)
    ) {
        http_response_code(403);
        $loginError = 'The username or password is incorrect.';
    } else {
        session_regenerate_id(true);
        $_SESSION['lab_access_authenticated'] = true;
        unset($_SESSION['lab_access_csrf']);
        header('Location: ' . $returnTo, true, 303);
        exit;
    }
}

if (empty($_SESSION['lab_access_csrf'])) {
    $_SESSION['lab_access_csrf'] = bin2hex(random_bytes(32));
}

header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lab sign in — ShopEase</title>
    <style>
        * { box-sizing: border-box; }
        body {
            display: grid;
            min-height: 100vh;
            margin: 0;
            padding: 24px;
            place-items: center;
            color: #202941;
            background: radial-gradient(ellipse at 15% 10%, #e4e8ff, transparent 42%), #f3f5fa;
            font: 16px/1.5 Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        main { width: min(100%, 420px); }
        .brand { margin: 0 0 18px; color: #3947ad; font-weight: 800; text-align: center; }
        .card {
            padding: clamp(24px, 7vw, 38px);
            border: 1px solid #e0e5ef;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 20px 60px #20294118;
        }
        h1 { margin: 0 0 8px; font-size: 26px; letter-spacing: -.04em; }
        .intro { margin: 0 0 24px; color: #69758d; }
        label { display: block; margin: 16px 0 7px; font-size: 14px; font-weight: 700; }
        input {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #d8deeb;
            border-radius: 10px;
            color: inherit;
            background: #f8f9fc;
            font: inherit;
        }
        input:focus { border-color: #5364d8; outline: 3px solid #5364d822; }
        button {
            width: 100%;
            margin-top: 22px;
            padding: 13px;
            border: 0;
            border-radius: 10px;
            color: #fff;
            background: #5364d8;
            font: inherit;
            font-weight: 750;
            cursor: pointer;
        }
        button:hover { background: #3947ad; }
        .error {
            margin: 0 0 16px;
            padding: 11px 13px;
            border: 1px solid #f1d0ce;
            border-radius: 9px;
            color: #a93a37;
            background: #fff1f0;
        }
        .footnote { margin: 14px 0 0; color: #8791a6; font-size: 13px; text-align: center; }
    </style>
</head>
<body>
<main>
    <p class="brand">◆ ShopEase Lab</p>
    <section class="card" aria-labelledby="signin-title">
        <h1 id="signin-title">Welcome back</h1>
        <p class="intro">Sign in to continue to the training shop.</p>
        <?php if ($loginError !== null): ?>
            <p class="error" role="alert"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="lab_access_login" value="1">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['lab_access_csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8') ?>">
            <label for="username">Username</label>
            <input id="username" name="username" autocomplete="username" required autofocus>
            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required>
            <button type="submit">Continue to ShopEase</button>
        </form>
    </section>
    <p class="footnote">Protected training environment</p>
</main>
<?php require __DIR__ . '/includes/password_toggle.php'; ?>
</body>
</html>
<?php
    exit;