<?php
require_once __DIR__ . '/../config.php';
$pdo = get_db();
$error = null;
$success = null;

// Handle form submission to add product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    try {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? 0;
        $imageUrl = trim($_POST['image_url'] ?? '');
        $uploadedImage = save_product_image_upload();
        if ($uploadedImage !== null) {
            $imageUrl = $uploadedImage;
        }

        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image_url) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $imageUrl]);
        $success = 'Product added.';
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}

// Fetch products
$products = $pdo->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);
?>
<h3>Manage Products</h3>
<?php if ($error !== null): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success !== null): ?><p role="status"><?= htmlspecialchars($success) ?></p><?php endif; ?>
<form method="POST" action="/admin.php?page=products.php" enctype="multipart/form-data">
    <input type="hidden" name="add_product" value="1">
    <input type="text" name="name" placeholder="Product Name" required>
    <textarea name="description" placeholder="Description"></textarea>
    <input type="number" step="0.01" name="price" placeholder="Price" required>
    <label>Product image URL</label>
    <input type="url" name="image_url" placeholder="https://example.com/product.jpg">
    <label>Or upload an image from your computer</label>
    <input type="file" name="product_image" accept="image/jpeg,image/png,image/gif,image/webp">
    <button type="submit">Add Product</button>
</form>

<table>
    <tr><th>Image</th><th>ID</th><th>Name</th><th>Price</th><th>Action</th></tr>
    <?php foreach ($products as $p): ?>
    <tr>
        <td>
            <?php if (!empty($p['image_url'])): ?>
                <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width:64px; height:64px; object-fit:contain; background:#f8f9fb; border-radius:4px;">
            <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($p['id']) ?></td>
        <td><?= htmlspecialchars($p['name']) ?></td>
        <td>$<?= htmlspecialchars($p['price']) ?></td>
        <td><a href="/admin.php?page=edit_product.php&id=<?= (int)$p['id'] ?>">Edit</a></td>
    </tr>
    <?php endforeach; ?>
</table>
