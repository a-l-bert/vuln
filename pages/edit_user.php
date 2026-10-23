<?php
require_once __DIR__ . '/../config.php';
$pdo = get_db();

$id = (int)($_GET['id'] ?? 0);
$current_user = current_user();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    http_response_code(404);
    echo '<p class="muted" role="status">User not found. Return to <a href="/admin.php?page=users.php">user management</a>.</p>';
    return;
}
$success = false;

// VULNERABLE: IDOR on profile edit (can edit anyone if ID is known)
if (!VULN_IDOR_PROFILE_EDIT && $current_user['id'] != $id && !$current_user['is_admin']) {
    die("Unauthorized.");
}

$can_edit_admin = ($current_user['username'] === 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $balance = (float)$_POST['balance'];
    
    if ($can_edit_admin && isset($_POST['is_admin'])) {
        $is_admin = (int)$_POST['is_admin'];
        $stmt = $pdo->prepare("UPDATE users SET balance = ?, is_admin = ? WHERE id = ?");
        $stmt->execute([$balance, $is_admin, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt->execute([$balance, $id]);
    }
    $success = true;
}

?>
<h3>Edit User: <?= htmlspecialchars($user['username']) ?></h3>
<?php if ($success): ?><p role="status">User updated.</p><?php endif; ?>
<form method="POST" action="/admin.php?page=edit_user.php&id=<?= (int)$id ?>">
    <label>Balance</label>
    <input type="number" step="0.01" name="balance" value="<?= (float)$user['balance'] ?>" required>
    
    <?php if ($can_edit_admin): ?>
    <label>Is Admin</label>
    <select name="is_admin">
        <option value="0" <?= !$user['is_admin'] ? 'selected' : '' ?>>No</option>
        <option value="1" <?= $user['is_admin'] ? 'selected' : '' ?>>Yes</option>
    </select>
    <?php endif; ?>
    
    <button type="submit">Update User</button>
</form>
