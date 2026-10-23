<?php
require __DIR__ . '/config.php';
$superadminKey = getenv('SUPERADMIN_KEY');
if ($superadminKey === false || $superadminKey === '') {
    http_response_code(503);
    exit('Superadmin access is not configured.');
}
if (empty($_SESSION['superadmin_authenticated'])) {
    $loginError = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submittedKey = (string)($_POST['superadmin_key'] ?? '');
        if (!hash_equals($superadminKey, $submittedKey)) {
            http_response_code(403);
            $loginError = 'Invalid pass key.';
        } else {
            session_regenerate_id(true);
            $_SESSION['superadmin_authenticated'] = true;
            header('Location: /superadmin.php');
            exit;
        }
    }
    ?>
    <!doctype html><html lang="en"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Superadmin Sign In — ShopEase</title>
    <style>
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:16px; font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif; color:#202941; background:radial-gradient(ellipse at 18% 12%,rgba(103,119,220,.22),transparent 38%),radial-gradient(ellipse at 92% 86%,rgba(228,168,91,.16),transparent 34%),#edf0f7; }
        .login-shell { width:min(100%,440px); }
        .login-brand { display:flex; justify-content:center; align-items:center; gap:9px; margin:0 0 20px; color:#3947ad; font-size:17px; font-weight:800; letter-spacing:-.04em; }
        .login-card { padding:clamp(28px,7vw,42px); border:1px solid #e0e5ef; border-radius:22px; background:rgba(251,252,255,.96); box-shadow:0 24px 70px rgba(31,42,72,.14); }
        .login-icon { width:48px; height:48px; display:grid; place-items:center; margin-bottom:22px; border-radius:15px; background:#edf0ff; color:#5364d8; font-size:23px; }
        h1 { margin:0 0 8px; font-size:27px; letter-spacing:-.045em; }
        .login-subtitle { margin:0 0 27px; color:#69758d; font-size:14px; line-height:1.65; }
        label { display:block; margin-bottom:8px; color:#3a4560; font-size:13px; font-weight:700; }
        input { width:100%; padding:13px 14px; border:1px solid #d8deeb; border-radius:10px; background:#f7f8fc; color:#202941; font:inherit; outline:none; }
        input:focus { border-color:#5364d8; box-shadow:0 0 0 3px rgba(83,100,216,.14); background:#fff; }
        button { width:100%; display:flex; justify-content:center; margin-top:19px; padding:13px 16px; border:0; border-radius:10px; background:#5364d8; color:#fff; font:inherit; font-weight:750; cursor:pointer; transition:background .18s,transform .18s; }
        button:hover { background:#3947ad; transform:translateY(-1px); }
        .login-error { margin:0 0 18px; padding:11px 13px; border:1px solid #f1d0ce; border-radius:9px; background:#fff1f0; color:#a93a37; font-size:13px; }
        .login-footnote { margin:14px 0 0; color:#8791a6; font-size:12px; text-align:center; }
    </style></head><body>
    <main class="login-shell">
        <div class="login-brand"><span aria-hidden="true">◆</span> ShopEase</div>
        <section class="login-card" aria-labelledby="login-title">
            <div class="login-icon" aria-hidden="true">⌑</div>
            <h1 id="login-title">Superadmin sign in</h1>
            <p class="login-subtitle">Enter your pass key to open the ShopEase control center.</p>
            <?php if ($loginError): ?><p class="login-error" role="alert"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <form method="POST">
                <label for="superadmin-key">Pass key</label>
                <input id="superadmin-key" type="password" name="superadmin_key" autocomplete="current-password" required>
                <button type="submit">Continue to control center&nbsp; →</button>
            </form>
        </section>
        <p class="login-footnote">Protected administration area</p>
    </main>
    <?php require __DIR__ . '/includes/password_toggle.php'; ?>
    </body></html>
    <?php
    exit;
}
$pdo = get_db();
$adminError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_balance'])) {
        $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$_POST['amount'], $_POST['user_id']]);
    } elseif (isset($_POST['delete_user'])) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$_POST['user_id']]);
    } elseif (isset($_POST['promote_admin'])) {
        $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = ?")->execute([$_POST['user_id']]);
    } elseif (isset($_POST['add_product'])) {
        try {
            $image_url = trim($_POST['image_url'] ?? '');
            $uploadedImage = save_product_image_upload();
            if ($uploadedImage !== null) {
                $image_url = $uploadedImage;
            }
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image_url) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_POST['name'], $_POST['description'], $_POST['price'], $image_url]);
        } catch (RuntimeException $exception) {
            $adminError = $exception->getMessage();
        }
    } elseif (isset($_POST['edit_product'])) {
        try {
            $image_url = trim($_POST['image_url'] ?? '');
            $uploadedImage = save_product_image_upload();
            if ($uploadedImage !== null) {
                $image_url = $uploadedImage;
            }
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, image_url = ? WHERE id = ?");
            $stmt->execute([$_POST['name'], $_POST['description'], $_POST['price'], $image_url, $_POST['product_id']]);
        } catch (RuntimeException $exception) {
            $adminError = $exception->getMessage();
        }
    } elseif (isset($_POST['delete_product'])) {
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$_POST['product_id']]);
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$historicalAwards = $pdo->query(
    "SELECT h.username, COALESCE(h.email, u.email, '') AS email, h.vuln_id,
            h.learner_name, h.learner_email, h.award_code,
            COALESCE(f.vulnerability_name, CASE h.vuln_id
                WHEN 'VULN-004' THEN 'SQL Injection in Login'
                ELSE h.vuln_id END) AS vulnerability_name,
            h.timestamp
     FROM hall_of_fame h
     LEFT JOIN users u ON h.user_id = u.id OR (h.user_id IS NULL AND h.username = u.username)
     LEFT JOIN flags f ON h.flag_id = f.id
     ORDER BY h.timestamp DESC"
)->fetchAll(PDO::FETCH_ASSOC);
$automaticAwards = $pdo->query(
    "SELECT username, email, vuln_id, learner_name, learner_email, award_code,
            vulnerability_name, timestamp
     FROM automatic_awards
     ORDER BY timestamp DESC"
)->fetchAll(PDO::FETCH_ASSOC);
$heroes = array_merge($historicalAwards, $automaticAwards);
usort($heroes, static fn($left, $right) => strcmp($right['timestamp'], $left['timestamp']));
?>
<!DOCTYPE html><html><head><title>Superadmin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    * { box-sizing:border-box; }
    body { margin:0; padding:clamp(18px,4vw,42px); font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif; color:#202941; background:#eef1f7; }
    body > h2 { max-width:1280px; margin:0 auto 24px; color:#202941; font-size:28px; letter-spacing:-.04em; }
    .card { max-width:1280px; margin:0 auto 22px; padding:clamp(18px,3vw,30px); overflow:auto; border:1px solid #e0e5ef; border-radius:17px; background:#fbfcff; box-shadow:0 12px 32px rgba(31,42,72,.07); }
    .card h3 { margin:0 0 16px; font-size:18px; letter-spacing:-.025em; }
    table { width:100%; border-collapse:collapse; margin-top:10px; }
    th, td { padding:11px 10px; border-bottom:1px solid #e5e9f1; text-align:left; vertical-align:top; }
    th { color:#69758d; font-size:11px; text-transform:uppercase; letter-spacing:.06em; }
    input, button, textarea { font:inherit; font-size:13px; }
    input, textarea { padding:9px 11px; border:1px solid #d8deeb; border-radius:8px; background:#f7f8fc; color:#202941; }
    textarea { width:100%; min-height:70px; }
    .btn { padding:8px 11px; border:0; border-radius:8px; cursor:pointer; color:white; background:#5364d8; font-weight:700; }
    .btn-red { background:#c94d55; }
    .product-create-form { margin-bottom:20px; padding:18px; border:1px solid #e3e7f0; border-radius:12px; background:#f2f4fa; }
    .heroes-table { min-width:900px; }
    @media(max-width:700px) { body { padding:16px; } table { min-width:650px; } }
</style></head><body>
<h2>Superadmin Control Center</h2>
<div class="card"><h3>Manage Users</h3>
    <table><tr><th>User</th><th>Balance</th><th>Action</th></tr>
    <?php foreach($users as $u): ?>
    <tr><td><?= htmlspecialchars($u['username']) ?></td><td>$<?= htmlspecialchars((float)$u['balance']) ?></td>
    <td><form method="POST"><input type="hidden" name="user_id" value="<?= $u['id'] ?>">
    <input type="number" name="amount" style="width:50px" placeholder="$">
    <button type="submit" name="add_balance" class="btn">Add</button>
    <button type="submit" name="promote_admin" class="btn">Admin</button>
    <button type="submit" name="delete_user" class="btn btn-red">Delete</button></form></td></tr>
    <?php endforeach; ?></table>
</div>
<div class="card"><h3>Manage Products</h3>
    <?php if ($adminError !== null): ?><p role="alert"><?= htmlspecialchars($adminError) ?></p><?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="product-create-form">
        <input type="text" name="name" placeholder="Name" required>
        <textarea name="description" placeholder="Description" required></textarea>
        <input type="number" step="0.01" name="price" placeholder="Price" required>
        <label>Product image URL</label>
        <input type="url" name="image_url" placeholder="https://example.com/product.jpg">
        <label>Or upload an image from your computer</label>
        <input type="file" name="product_image" accept="image/jpeg,image/png,image/gif,image/webp">
        <button type="submit" name="add_product" class="btn">Add New Product</button>
    </form>
    <table><tr><th>Image</th><th>Name</th><th>Description</th><th>Price</th><th>Image URL / Upload</th><th>Action</th></tr>
    <?php foreach($products as $p): ?>
    <?php $productFormId = 'edit-product-' . (int)$p['id']; ?>
    <tr>
        <td>
            <form id="<?= htmlspecialchars($productFormId) ?>" method="POST" enctype="multipart/form-data"></form>
            <input type="hidden" form="<?= htmlspecialchars($productFormId) ?>" name="product_id" value="<?= (int)$p['id'] ?>">
            <?php if (!empty($p['image_url'])): ?><img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width:64px;height:64px;object-fit:contain;"><?php endif; ?>
        </td>
        <td><input form="<?= htmlspecialchars($productFormId) ?>" type="text" name="name" value="<?= htmlspecialchars($p['name']) ?>"></td>
        <td><textarea form="<?= htmlspecialchars($productFormId) ?>" name="description"><?= htmlspecialchars($p['description']) ?></textarea></td>
        <td><input form="<?= htmlspecialchars($productFormId) ?>" type="number" step="0.01" name="price" value="<?= (float)$p['price'] ?>"></td>
        <td><input form="<?= htmlspecialchars($productFormId) ?>" type="text" name="image_url" value="<?= htmlspecialchars($p['image_url'] ?? '') ?>" placeholder="https://example.com/product.jpg or /uploads/image.jpg"><input form="<?= htmlspecialchars($productFormId) ?>" type="file" name="product_image" accept="image/jpeg,image/png,image/gif,image/webp"></td>
        <td><button form="<?= htmlspecialchars($productFormId) ?>" type="submit" name="edit_product" class="btn">Save</button>
        <button form="<?= htmlspecialchars($productFormId) ?>" type="submit" name="delete_product" formnovalidate class="btn btn-red">Delete</button></td>
    </tr>
    <?php endforeach; ?></table>
</div>
<div class="card"><h3>Hall of Heroes</h3>
    <table class="heroes-table"><tr><th>Recipient</th><th>Email</th><th>Account</th><th>Certificate #</th><th>Vulnerability</th><th>Found At (EAT)</th></tr>
    <?php if (empty($heroes)): ?>
    <tr><td colspan="6">No discoveries have been recorded yet.</td></tr>
    <?php endif; ?>
    <?php foreach($heroes as $h): ?>
    <tr>
        <td><?= htmlspecialchars($h['learner_name'] ?: 'Legacy award') ?></td>
        <td><?= htmlspecialchars($h['learner_email'] ?? '') ?></td>
        <td><?= htmlspecialchars($h['username']) ?><?= $h['email'] !== '' ? ' · ' . htmlspecialchars($h['email']) : '' ?></td>
        <td><?= htmlspecialchars($h['award_code'] ?? '') ?></td>
        <td><?= htmlspecialchars($h['vulnerability_name']) ?></td>
        <td><?= htmlspecialchars(format_eat_datetime($h['timestamp'])) ?></td>
    </tr>
    <?php endforeach; ?></table>
</div>
</body></html>
