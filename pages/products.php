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
<h3>Product catalog</h3>
<p class="admin-dashboard-intro">Add new items to your storefront or update an existing product's details and price.</p>
<?php if ($error !== null): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success !== null): ?><p role="status"><?= htmlspecialchars($success) ?></p><?php endif; ?>
<section class="admin-panel">
    <h4>Add a product</h4>
    <form method="POST" action="/admin.php?page=products.php" enctype="multipart/form-data" class="admin-form-grid">
        <input type="hidden" name="add_product" value="1">
        <label>Product name
            <input type="text" name="name" placeholder="Product name" required>
        </label>
        <label>Price
            <input type="number" step="0.01" name="price" placeholder="0.00" required>
        </label>
        <label class="admin-field-full">Description
            <textarea name="description" placeholder="Describe the product"></textarea>
        </label>
        <label>Product image URL
            <input type="url" name="image_url" placeholder="https://example.com/product.jpg">
        </label>
        <label>Or upload an image
            <input type="file" name="product_image" accept="image/jpeg,image/png,image/gif,image/webp">
        </label>
        <div class="admin-field-full"><button type="submit">Add product</button></div>
    </form>
</section>

<h4>Products (<?= count($products) ?>)</h4>
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Image</th><th>ID</th><th>Name</th><th>Price</th><th>Action</th></tr></thead>
        <tbody>
        <?php if (empty($products)): ?>
            <tr><td colspan="5" class="admin-empty">No products yet. Add your first product above.</td></tr>
        <?php endif; ?>
        <?php foreach ($products as $p): ?>
        <tr>
            <td>
                <?php if (!empty($p['image_url'])): ?>
                    <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="admin-image-thumb">
                <?php else: ?>
                    <span class="admin-badge">No image</span>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($p['id']) ?></td>
            <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
            <td>$<?= htmlspecialchars($p['price']) ?></td>
            <td><a href="/admin.php?page=edit_product.php&id=<?= (int)$p['id'] ?>">Edit product</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
