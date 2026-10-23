<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';

$user = current_user();
if (!$user) {
    header('Location: /login.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedFlag = is_string($_POST['flag'] ?? null) ? trim($_POST['flag']) : '';
    $upload = $_FILES['flag_file'] ?? null;
    if ($upload !== null && !is_array($upload)) {
        $message = 'The selected flag file is invalid.';
    } elseif (is_array($upload) && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $uploadError = $upload['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($uploadError !== UPLOAD_ERR_OK) {
            $message = $uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE
                ? 'The flag file is too large.'
                : 'The flag file could not be uploaded. Please try again.';
        } elseif (
            !is_string($upload['tmp_name'] ?? null)
            || !is_string($upload['name'] ?? null)
            || !is_uploaded_file($upload['tmp_name'])
            || !is_int($upload['size'] ?? null)
            || $upload['size'] > 16384
        ) {
            $message = 'Choose a valid flag file smaller than 16 KB.';
        } else {
            $fileContents = file_get_contents($upload['tmp_name']);
            if ($fileContents === false) {
                $message = 'The flag file could not be read.';
            } else {
                $fileContents = preg_replace('/^\xEF\xBB\xBF/', '', $fileContents);
                if (str_contains($fileContents, "\0") || preg_match('/[^\x09\x0A\x0D\x20-\x7E]/', $fileContents)) {
                    $message = 'The uploaded file must contain a plain-text flag.';
                } else {
                    $fileFlag = trim($fileContents);
                    if ($fileFlag === '') {
                        $message = 'The uploaded file is empty.';
                    } elseif ($submittedFlag !== '' && $submittedFlag !== $fileFlag) {
                        $message = 'The pasted flag and uploaded flag do not match.';
                    } else {
                        $submittedFlag = $fileFlag;
                    }
                }
            }
        }
    }

    if ($message === '' && $submittedFlag === '') {
        $message = 'Paste a flag or choose a flag file from your device.';
    }

    if ($message === '') {
        $pdo = get_db();
        $flagQuery = $pdo->prepare('SELECT id, vuln_id, vulnerability_name FROM flags WHERE flag = ?');
        $flagQuery->execute([$submittedFlag]);
        $flag = $flagQuery->fetch(PDO::FETCH_ASSOC);
    } else {
        $flag = false;
    }

    if ($flag) {
        $_SESSION['pending_award_flag_id'] = (int)$flag['id'];
        header('Location: /claim_award.php');
        exit;
    }

    if ($message === '') {
        $message = 'Invalid flag. Check the flag and try again.';
    }
}

$page_title = 'Submit a Lab Flag';
require __DIR__ . '/includes/header.php';
?>
<div class="card" style="max-width:720px;margin:32px auto;">
  <h2>Submit a discovered flag</h2>
  <p class="muted">Paste the flag or upload its downloaded plain-text file from your computer. After validation, add your name and email so your certificate and achievement can be attributed to you, even when sharing a demo account.</p>
  <?php if ($message !== ''): ?><div class="error" role="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <style>
    .flag-file-input { display:block; width:100%; margin:6px 0 18px; padding:12px; border:1px dashed #c9d2e8; border-radius:10px; background:#f7f8fc; color:#3a4560; }
    .flag-option-separator { margin:10px 0; color:#8791a6; font-size:12px; text-align:center; }
  </style>
  <form method="POST" enctype="multipart/form-data">
    <label for="flag">Paste challenge flag</label>
    <input id="flag" type="text" name="flag" placeholder="FLAG{...}" autocomplete="off">
    <p class="flag-option-separator">OR UPLOAD A DOWNLOADED FLAG FILE</p>
    <label for="flag-file">Downloaded flag file (plain text, up to 16 KB)</label>
    <input class="flag-file-input" id="flag-file" type="file" name="flag_file">
    <button type="submit">Verify flag</button>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
