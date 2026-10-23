<?php
require_once __DIR__ . '/../config.php';
$pdo = get_db();
$current_user = current_user();
$error = null;
$success = null;

// Handle user deletion
if (isset($_GET['delete_user'])) {
    $id = (int)$_GET['delete_user'];
    // Prevent deleting the main admin
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user_to_delete = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_to_delete && $user_to_delete['username'] !== 'admin') {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        $error = 'Username, email, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $duplicate = $pdo->prepare('SELECT 1 FROM users WHERE username = ? OR email = ? LIMIT 1');
        $duplicate->execute([$username, $email]);
        if ($duplicate->fetchColumn() !== false) {
            $error = 'That username or email is already in use.';
        } else {
            try {
                $storedPassword = VULN_WEAK_PASSWORD_HASHING
                    ? $password
                    : password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (username, password, email, is_admin, ssn) VALUES (?, ?, ?, 0, ?)');
                $stmt->execute([$username, $storedPassword, $email, '']);
                $success = 'Customer account created.';
            } catch (PDOException $exception) {
                if ((string)$exception->getCode() === '23000' || str_contains(strtolower($exception->getMessage()), 'unique')) {
                    $error = 'That username or email is already in use.';
                } else {
                    throw $exception;
                }
            }
        }
    }
}

// Fetch users
$users = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
?>
<h3>Customers &amp; users</h3>
<p class="admin-dashboard-intro">Review customer accounts and manage their account details.</p>
<?php if ($error !== null): ?><div class="error" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success !== null): ?><p role="status"><?= htmlspecialchars($success) ?></p><?php endif; ?>
<section class="admin-panel">
    <h4>Add a customer account</h4>
    <form method="POST" action="/admin.php?page=users.php" class="admin-form-grid">
        <input type="hidden" name="add_user" value="1">
        <label>Username
            <input type="text" name="username" autocomplete="off" required>
        </label>
        <label>Email address
            <input type="email" name="email" autocomplete="email" required>
        </label>
        <label>Password
            <input type="password" name="password" autocomplete="new-password" minlength="8" required>
        </label>
        <div class="admin-field-full"><button type="submit">Create customer</button></div>
    </form>
</section>
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Balance</th><th>Role</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (empty($users)): ?>
            <tr><td colspan="6" class="admin-empty">No user accounts are available.</td></tr>
        <?php endif; ?>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['id']) ?></td>
            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>$<?= htmlspecialchars((float)$u['balance']) ?></td>
            <td><span class="admin-badge"><?= $u['is_admin'] ? 'Administrator' : 'Customer' ?></span></td>
            <td><div class="admin-actions">
                <a href="/admin.php?page=edit_user.php&id=<?= (int)$u['id'] ?>">Edit</a>
                <?php if ($u['username'] !== 'admin'): ?>
                    <a href="/admin.php?page=users.php&amp;delete_user=<?= (int)$u['id'] ?>">Delete</a>
                <?php endif; ?>
            </div></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
