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
<h3>Edit Product: <?= htmlspecialchars($product['name']) ?></h3>
<?php if ($error !== null): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success !== null): ?><p role="status"><?= htmlspecialchars($success) ?></p><?php endif; ?>
<form method="POST" enctype="multipart/form-data">
    <label>Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
    <label>Description</label>
    <textarea name="description"><?= htmlspecialchars($product['description']) ?></textarea>
    <label>Price</label>
    <input type="number" step="0.01" name="price" value="<?= (float)$product['price'] ?>" required>
    <label>Product image URL</label>
    <input type="url" name="image_url" value="<?= htmlspecialchars($product['image_url'] ?? '') ?>" placeholder="https://example.com/product.jpg">
    <label>Or upload a replacement image from your computer</label>
    <input type="file" name="product_image" accept="image/jpeg,image/png,image/gif,image/webp">
    <label><input type="checkbox" name="remove_image" value="1"> Remove current image</label>
    <button type="submit">Update Product</button>
</form>
