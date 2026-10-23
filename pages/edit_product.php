<?php
require_once __DIR__ . '/../config.php';
$pdo = get_db();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    http_response_code(404);
    exit('Product not found.');
}
$error = null;
$success = null;
?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $imageUrl = trim($_POST['image_url'] ?? '');
        $uploadedImage = save_product_image_upload();
        if (!empty($_POST['remove_image'])) {
            $imageUrl = '';
        } elseif ($uploadedImage !== null) {
            $imageUrl = $uploadedImage;
        } elseif ($imageUrl === '' && empty($_POST['remove_image'])) {
            $imageUrl = $product['image_url'] ?? '';
        }

        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, image_url = ? WHERE id = ?");
        $stmt->execute([
            $_POST['name'] ?? '',
            $_POST['description'] ?? '',
            (float)($_POST['price'] ?? 0),
            $imageUrl,
            $id,
        ]);
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        $success = 'Product updated.';
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    }
}
?>
<h3>Edit product</h3>
<p class="admin-dashboard-intro">Update product details, pricing, or storefront imagery.</p>
<?php if ($error !== null): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success !== null): ?><p role="status"><?= htmlspecialchars($success) ?></p><?php endif; ?>
<section class="admin-panel">
    <h4><?= htmlspecialchars($product['name']) ?></h4>
    <form method="POST" enctype="multipart/form-data" class="admin-form-grid">
        <label>Name
            <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
        </label>
        <label>Price
            <input type="number" step="0.01" name="price" value="<?= (float)$product['price'] ?>" required>
        </label>
        <label class="admin-field-full">Description
            <textarea name="description"><?= htmlspecialchars($product['description']) ?></textarea>
        </label>
        <label>Product image URL
            <input type="url" name="image_url" value="<?= htmlspecialchars($product['image_url'] ?? '') ?>" placeholder="https://example.com/product.jpg">
        </label>
        <label>Upload a replacement image
            <input type="file" name="product_image" accept="image/jpeg,image/png,image/gif,image/webp">
        </label>
        <label class="admin-field-full"><span><input type="checkbox" name="remove_image" value="1"> Remove current image</span></label>
        <div class="admin-field-full admin-actions">
            <button type="submit">Save product</button>
            <a href="/admin.php?page=products.php">Cancel</a>
        </div>
    </form>
</section>
