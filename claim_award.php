<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';

$user = current_user();
if (!$user) {
    header('Location: /login.php');
    exit;
}

$pendingAward = $_SESSION['pending_award'] ?? null;
$pendingFlagId = (int)($_SESSION['pending_award_flag_id'] ?? 0);
$pendingAwardError = !empty($_SESSION['pending_award_error']);
unset($_SESSION['pending_award_error']);
if ($pendingAwardError && !is_array($pendingAward) && $pendingFlagId < 1) {
    $pendingAward = [
        'vuln_id' => 'VULN-004',
        'vulnerability_name' => 'SQL Injection in Login',
    ];
    $_SESSION['pending_award'] = $pendingAward;
    $pendingAwardError = false;
}
if (!is_array($pendingAward) && $pendingFlagId < 1 && !$pendingAwardError) {
    header('Location: /products.php');
    exit;
}

$pdo = get_db();
$flag = is_array($pendingAward)
    && is_string($pendingAward['vuln_id'] ?? null)
    && is_string($pendingAward['vulnerability_name'] ?? null)
        ? [
            'id' => null,
            'vuln_id' => $pendingAward['vuln_id'],
            'vulnerability_name' => $pendingAward['vulnerability_name'],
        ]
        : false;
if (!$flag && $pendingFlagId > 0) {
    $flagQuery = $pdo->prepare('SELECT id, vuln_id, vulnerability_name FROM flags WHERE id = ?');
    $flagQuery->execute([$pendingFlagId]);
    $flag = $flagQuery->fetch(PDO::FETCH_ASSOC);
    if (!$flag) {
        unset($_SESSION['pending_award_flag_id']);
        $pendingAwardError = true;
    }
}

if (empty($_SESSION['award_claim_csrf'])) {
    $_SESSION['award_claim_csrf'] = bin2hex(random_bytes(32));
}

$error = null;
$award = null;
$learnerName = '';
$learnerEmail = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['award_claim_csrf'], $submittedToken)) {
        http_response_code(403);
        $error = 'This form expired. Reload the page and try again.';
    } else {
        $learnerName = trim(is_string($_POST['learner_name'] ?? null) ? $_POST['learner_name'] : '');
        $learnerEmail = trim(is_string($_POST['learner_email'] ?? null) ? $_POST['learner_email'] : '');
        if ($learnerName === '' || strlen($learnerName) > 100) {
            $error = 'Enter your name using no more than 100 characters.';
        } elseif (
            strlen($learnerEmail) > 254
            || filter_var($learnerEmail, FILTER_VALIDATE_EMAIL) === false
        ) {
            $error = 'Enter a valid email address using no more than 254 characters.';
        } else {
            try {
                $award = record_vulnerability_award(
                    $pdo,
                    $user,
                    $flag,
                    $learnerName,
                    $learnerEmail
                );
                $learnerName = $award['learner_name'] ?? $learnerName;
                $learnerEmail = $award['learner_email'] ?? $learnerEmail;
                unset($_SESSION['pending_award'], $_SESSION['pending_award_flag_id']);
            } catch (Throwable $exception) {
                $errorReference = bin2hex(random_bytes(6));
                error_log("Award claim failed [$errorReference]: " . $exception->getMessage());
                $error = "We could not save your achievement right now. Please retry and share reference $errorReference with your lab instructor if the problem continues.";
            }
        }
    }
}

$page_title = $award ? 'Achievement recorded' : 'Claim your achievement';
$awardTitle = is_array($flag) ? (string)($flag['vulnerability_name'] ?: $flag['vuln_id']) : 'Training challenge';
require __DIR__ . '/includes/header.php';
?>
<section class="card" style="max-width:700px;margin:32px auto;">
  <?php if ($pendingAwardError && !$flag): ?>
    <div class="error" role="alert">Your login bypass was detected, but we could not prepare the achievement record. Please contact your lab instructor; you do not need to repeat the login.</div>
    <a class="button-link" href="/products.php">Continue to ShopEase</a>
  <?php elseif ($award && (int)$award['user_id'] !== (int)$user['id']): ?>
    <div class="success" role="status">
      <strong>No duplicate award was created.</strong> This challenge has already been recorded for this email. Sign in to the account used for the original claim to access its certificate.
    </div>
    <a href="/products.php">Return to ShopEase</a>
  <?php elseif ($award): ?>
    <div class="success" role="status">
      <strong>Discovery recorded.</strong> Your achievement has been recorded for <?= htmlspecialchars($learnerName) ?>.
    </div>
    <h1>Congratulations, <?= htmlspecialchars($learnerName) ?>!</h1>
    <p>Your detected login bypass was <strong><?= htmlspecialchars($awardTitle) ?></strong>.</p>
    <p>Your unique certificate number is <strong><?= htmlspecialchars($award['award_code']) ?></strong>.</p>
    <p class="muted">This award is linked to <?= htmlspecialchars($learnerEmail) ?> and your ShopEase account <?= htmlspecialchars($user['username']) ?>.</p>
    <p><a class="button-link" href="/generate_cert.php?award_code=<?= rawurlencode($award['award_code']) ?>">Download your certificate</a></p>
    <a href="/products.php">Return to ShopEase</a>
  <?php else: ?>
    <span class="detail-kicker">Discovery confirmed</span>
    <h1>Claim your achievement</h1>
    <p class="muted">Your login bypass was detected. Enter your own name and email so the award and certificate identify you.</p>
    <?php if ($error !== null): ?><div class="error" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST" action="/claim_award.php">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['award_claim_csrf'], ENT_QUOTES, 'UTF-8') ?>">
      <label for="learner-name">Your full name</label>
      <input id="learner-name" type="text" name="learner_name" maxlength="100" autocomplete="name" value="<?= htmlspecialchars($learnerName) ?>" required>
      <label for="learner-email">Your personal email</label>
      <input id="learner-email" type="email" name="learner_email" maxlength="254" autocomplete="email" value="<?= htmlspecialchars($learnerEmail) ?>" required>
      <button type="submit">Record discovery and create certificate</button>
    </form>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
