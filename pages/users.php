<?php
require_once __DIR__ . '/../config.php';
$pdo = get_db();
$current_user = current_user();

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

// Fetch users
$users = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
?>
<h3>Manage Users</h3>
<table>
    <tr><th>ID</th><th>Username</th><th>Email</th><th>Balance</th><th>Is Admin</th><th>Action</th></tr>
    <?php foreach ($users as $u): ?>
    <tr>
        <td><?= htmlspecialchars($u['id']) ?></td>
        <td><?= htmlspecialchars($u['username']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td>$<?= htmlspecialchars((float)$u['balance']) ?></td>
        <td><?= $u['is_admin'] ? 'Yes' : 'No' ?></td>
        <td>
            <a href="/admin.php?page=edit_user.php&id=<?= (int)$u['id'] ?>">Edit</a>
            <?php if ($u['username'] !== 'admin'): ?>
                | <a href="/admin.php?page=users.php&delete_user=<?= (int)$u['id'] ?>">Delete</a>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
