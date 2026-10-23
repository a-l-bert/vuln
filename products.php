<?php
require __DIR__ . '/auth_gate.php';
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';

$page_title = 'Products';
require __DIR__ . '/includes/header.php';
?>
<section class="card shop-hero">
  <div>
    <h1>Make room for better tech.</h1>
    <p>Thoughtful everyday essentials, selected to make your workspace work a little smarter.</p>
  </div>
  <div class="shop-hero-note">Curated essentials&nbsp; · &nbsp;Easy checkout</div>
</section>
<div class="shop-toolbar">
  <h2>Explore the collection</h2>
  <label class="shop-search">
    <span class="muted">Search products</span>
    <input type="text" id="search" placeholder="Try “keyboard” or “webcam”" oninput="searchProducts(this.value)">
  </label>
</div>
<div class="product-grid" id="product-grid">
  <div class="card muted" role="status">Loading the collection…</div>
</div>

<script>
function searchProducts(query) {
    fetch('/api/search.php?q=' + encodeURIComponent(query))
        .then(response => {
            if (!response.ok) throw new Error('Unable to load products.');
            return response.text();
        })
        .then(data => {
            document.getElementById('product-grid').innerHTML = data;
        })
        .catch(() => {
            document.getElementById('product-grid').innerHTML =
                '<div class="card error" role="alert">We could not load the collection. Please refresh to try again.</div>';
        });
}
searchProducts('');
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
