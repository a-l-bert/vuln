<?php 
$user = current_user(); 
if (!VULN_MISSING_HEADERS) {
    header("X-Frame-Options: SAMEORIGIN");
    header("Content-Security-Policy: default-src 'self'");
    header("X-Content-Type-Options: nosniff");
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — ' : '' ?>ShopEase</title>
  <style>
    :root {
      --brand:#5364d8;
      --brand-dark:#3947ad;
      --accent:#e4a85b;
      --bg:#eef1f7;
      --card:#fbfcff;
      --text:#202941;
      --muted:#69758d;
      --line:#e0e5ef;
      --shadow:0 14px 36px rgba(31,42,72,.08);
    }
    * { box-sizing: border-box; }
    body { margin:0; min-height:100vh; font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif; background:radial-gradient(ellipse at 80% 0%,#e3e8f7 0,transparent 38%),var(--bg); color:var(--text); }
    nav { min-height:72px; background:#202941; color:#fff; padding:14px clamp(18px,5vw,72px); display:flex; align-items:center; justify-content:space-between; gap:22px; box-shadow:0 8px 24px rgba(23,32,54,.16); }
    nav > div:last-child { display:flex; align-items:center; flex-wrap:wrap; justify-content:flex-end; gap:6px; }
    nav a { color:#e8ebf5; text-decoration:none; margin-left:8px; padding:9px 11px; border-radius:9px; font-size:13px; opacity:.92; transition:background .18s ease,color .18s ease; }
    nav a:hover { background:rgba(255,255,255,.11); color:#fff; }
    .brand { font-weight:750; font-size:19px; letter-spacing:-.04em; white-space:nowrap; }
    .container { width:min(1180px,100%); margin:38px auto 20px; padding:0 clamp(18px,4vw,32px); }
    .card { background:var(--card); border:1px solid rgba(218,225,239,.9); border-radius:18px; padding:clamp(22px,4vw,36px); box-shadow:var(--shadow); margin-bottom:22px; }
    input, button, textarea, select { font:inherit; font-size:14px; }
    input[type=text], input[type=password], input[type=email], input[type=number], input[type=url], textarea, select { width:100%; padding:12px 14px; color:var(--text); background:#f7f8fc; border:1px solid #d8deeb; border-radius:10px; margin-top:6px; margin-bottom:14px; outline:none; transition:border-color .18s,box-shadow .18s,background .18s; }
    input:focus, textarea:focus, select:focus { background:#fff; border-color:var(--brand); box-shadow:0 0 0 3px rgba(83,100,216,.14); }
    input[type=number] { max-width:150px; }
    button, .button-link { display:inline-flex; align-items:center; justify-content:center; gap:8px; background:var(--brand); color:#fff; border:0; padding:12px 18px; border-radius:10px; cursor:pointer; font-weight:700; text-decoration:none; transition:transform .18s,background .18s,box-shadow .18s; }
    button:hover, .button-link:hover { background:var(--brand-dark); color:#fff; transform:translateY(-1px); box-shadow:0 8px 18px rgba(57,71,173,.2); }
    label { font-size:13px; color:var(--muted); font-weight:650; }
    .error { background:#fff1f0; color:#a93a37; border:1px solid #f1d0ce; padding:12px 15px; border-radius:10px; margin-bottom:16px; font-size:14px; }
    .success { background:#edf8f0; color:#247346; border:1px solid #c8e8d1; padding:12px 15px; border-radius:10px; margin-bottom:16px; font-size:14px; }
    .product-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(min(100%,245px),1fr)); gap:20px; }
    .product { display:flex; flex-direction:column; min-width:0; background:var(--card); border:1px solid var(--line); border-radius:17px; padding:14px; box-shadow:0 8px 24px rgba(31,42,72,.055); transition:transform .2s,box-shadow .2s,border-color .2s; }
    .product:hover { transform:translateY(-4px); border-color:#c7cfee; box-shadow:0 18px 36px rgba(31,42,72,.12); }
    .product-image-frame { position:relative; width:100%; aspect-ratio:4/3; display:flex; align-items:center; justify-content:center; overflow:hidden; margin-bottom:15px; border-radius:12px; background:linear-gradient(145deg,#e9edf9,#f3f5fb 52%,#e7edf6); }
    .product-image-frame img { display:block; max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain; }
    .product-placeholder { flex-direction:column; gap:10px; color:#5665bc; }
    .product-placeholder::before,.product-placeholder::after { position:absolute; content:""; border:1px solid rgba(83,100,216,.14); border-radius:50%; }
    .product-placeholder::before { width:150px; height:150px; }
    .product-placeholder::after { width:205px; height:205px; }
    .placeholder-icon { z-index:1; font-size:54px; line-height:1; font-weight:300; }
    .placeholder-caption { z-index:1; font-size:10px; text-transform:uppercase; letter-spacing:.18em; color:#7782ba; }
    .product h3 { margin:2px 0 8px; font-size:16px; letter-spacing:-.02em; }
    .product a { color:var(--text); text-decoration:none; }
    .product a:hover { color:var(--brand-dark); }
    .product .muted { flex:1; min-height:40px; }
    .price { color:var(--brand-dark); font-weight:800; font-size:18px; letter-spacing:-.025em; }
    .muted { color:var(--muted); font-size:13px; line-height:1.6; }
    .comment { border-bottom:1px solid var(--line); padding:12px 0; }
    table { width:100%; border-collapse:collapse; }
    th, td { text-align:left; padding:11px 10px; border-bottom:1px solid var(--line); }
    th { color:var(--muted); font-size:12px; text-transform:uppercase; letter-spacing:.06em; }
    footer { text-align:center; color:var(--muted); font-size:12px; padding:28px 16px 38px; }
    .shop-hero { position:relative; overflow:hidden; display:grid; grid-template-columns:1fr auto; align-items:center; gap:24px; padding:clamp(26px,5vw,48px); background:linear-gradient(120deg,#232d49,#384571 65%,#4c5a9c); color:#fff; border:0; }
    .shop-hero::after { content:""; position:absolute; width:290px; height:290px; right:-90px; top:-150px; border:1px solid rgba(255,255,255,.14); border-radius:50%; box-shadow:0 0 0 32px rgba(255,255,255,.035),0 0 0 70px rgba(255,255,255,.025); }
    .shop-hero h1 { margin:0 0 10px; font-size:clamp(28px,4vw,42px); letter-spacing:-.045em; }
    .shop-hero p { max-width:570px; margin:0; color:#d4dbef; line-height:1.65; }
    .shop-hero-note { position:relative; z-index:1; padding:13px 16px; border:1px solid rgba(255,255,255,.22); border-radius:12px; background:rgba(255,255,255,.09); color:#f3f5ff; font-size:12px; white-space:nowrap; }
    .shop-toolbar { display:flex; align-items:center; justify-content:space-between; gap:14px; margin:24px 2px 15px; }
    .shop-toolbar h2 { margin:0; font-size:18px; letter-spacing:-.025em; }
    .shop-search { width:min(100%,350px); margin:0; }
    .shop-search input { margin:0; background:var(--card); }
    .product-actions { display:flex; justify-content:flex-end; margin-top:14px; }
    .product-actions a { color:var(--brand-dark); font-size:13px; font-weight:700; }
    .product-detail-layout { display:grid; grid-template-columns:minmax(0,1.05fr) minmax(280px,.95fr); gap:clamp(24px,5vw,58px); align-items:center; }
    .product-detail-layout .product-image-frame { aspect-ratio:1; margin:0; }
    .product-detail-layout .product-image-frame img { max-height:90%; }
    .product-detail-copy h1 { margin:10px 0 12px; font-size:clamp(28px,4vw,40px); line-height:1.12; letter-spacing:-.045em; }
    .product-detail-copy .price { margin:20px 0; font-size:26px; }
    .product-detail-copy .detail-description { color:var(--muted); line-height:1.8; }
    .product-detail-copy form { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-top:22px; }
    .product-detail-copy form input { margin:0; }
    .detail-kicker { color:var(--brand-dark); font-size:11px; font-weight:800; letter-spacing:.13em; text-transform:uppercase; }
    @media (max-width:680px) {
      nav { align-items:flex-start; flex-direction:column; gap:10px; }
      nav > div:last-child { justify-content:flex-start; margin-left:-8px; }
      .shop-hero { grid-template-columns:1fr; }
      .shop-hero-note { justify-self:start; white-space:normal; }
      .shop-toolbar { align-items:stretch; flex-direction:column; }
      .shop-search { width:100%; }
      .product-detail-layout { grid-template-columns:1fr; }
      .product-detail-layout .product-image-frame { max-height:420px; }
    }
  </style>
</head>
<body>
<nav>
  <div class="brand">◆ ShopEase</div>
  <div>
    <a href="/products.php">Products</a>
    <?php if ($user): ?>
      <?php if (!$user['is_admin']): 
          $cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
      ?>
        <span style="color:#fff; margin-left:18px; font-size:14px;">Balance: $<?= htmlspecialchars((float)$user['balance']) ?></span>
        <a href="/cart.php">Cart <?php if ($cart_count > 0): ?><span style="background:red; padding:2px 6px; border-radius:50%; font-size:10px;"><?= (int)$cart_count ?></span><?php endif; ?></a>
        <a href="/submit_flag.php">Submit Flag</a>
      <?php endif; ?>
      <a href="/profile.php?id=<?= (int)$user['id'] ?>">My Profile</a>
      <?php if ($user['is_admin']): ?>
        <a href="/admin.php">Admin</a>
      <?php endif; ?>
      <a href="/logout.php">Logout</a>
    <?php else: ?>
      <a href="/login.php">Login</a>
    <?php endif; ?>
  </div>
</nav>
<div class="container">
