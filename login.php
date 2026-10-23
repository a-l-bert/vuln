<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';

$error = null;
$sqlInjectionBypass = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = is_string($_POST['username'] ?? null) ? $_POST['username'] : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $user = null;

    // VULNERABLE: SQLi and weak auth
    if (VULN_SQLI_LOGIN) {
        $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
        try {
            $stmt = get_db()->query($sql);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Login query failed: ' . $e->getMessage());
            $error = "Invalid username or password.";
        }

        if ($user) {
            $normalQuery = get_db()->prepare('SELECT * FROM users WHERE username = ?');
            $normalQuery->execute([$username]);
            $normalUser = $normalQuery->fetch(PDO::FETCH_ASSOC);
            if ($normalUser) {
                $normalLoginSucceeds = VULN_WEAK_PASSWORD_HASHING
                    ? $password === $normalUser['password']
                    : password_verify($password, $normalUser['password']);
                $sqlInjectionBypass = !$normalLoginSucceeds;
            } else {
                $sqlInjectionBypass = true;
            }
        }
    } else {
        // SECURE: Prepared statements
        $stmt = get_db()->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check password hashing
        if ($user) {
            if (VULN_WEAK_PASSWORD_HASHING) {
                if ($password !== $user['password']) $user = null;
            } else {
                if (!password_verify($password, $user['password'])) $user = null;
            }
        }
    }

    if ($user) {
        // Session fixation vulnerability
        if (!VULN_SESSION_FIXATION) {
            session_regenerate_id(true);
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = (bool)$user['is_admin'];

        if ($sqlInjectionBypass) {
            unset($_SESSION['pending_award_flag_id'], $_SESSION['pending_award_error']);
            $_SESSION['pending_award'] = [
                'vuln_id' => 'VULN-004',
                'vulnerability_name' => 'SQL Injection in Login',
            ];
            header('Location: /claim_award.php');
            exit;
        }

        header('Location: /products.php');
        exit;
    } else {
        $error = VULN_USERNAME_ENUMERATION ? "Invalid username or password." : "Login failed.";
    }
}

$page_title = 'Sign In';
require __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:380px;margin:40px auto;">
  <h2 style="margin-top:0;">Sign in to ShopEase</h2>
  <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST" action="/login.php">
    <label>Username</label>
    <input type="text" name="username" required>
    <label>Password</label>
    <input type="password" name="password" required>
    <button type="submit">Sign In</button>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
