<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';
require __DIR__ . '/includes/safe_lab_simulations.php';

$user = current_user();
if (!$user) {
    header('Location: /login.php');
    exit;
}

if (empty($_SESSION['safe_lab_csrf'])) {
    $_SESSION['safe_lab_csrf'] = bin2hex(random_bytes(32));
}

$simulation = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    $toggle = is_string($_POST['toggle'] ?? null) ? $_POST['toggle'] : '';
    if (!hash_equals($_SESSION['safe_lab_csrf'], $csrf)) {
        http_response_code(403);
        $error = 'This page expired. Reload and try the simulation again.';
    } else {
        try {
            $simulation = safe_lab_simulation_profile($toggle);
            if ($simulation['passed']) {
                $_SESSION['safe_lab_completed'][$toggle] = true;
            } else {
                $error = 'The synthetic safety check did not pass; this simulation was not recorded.';
            }
        } catch (InvalidArgumentException $exception) {
            http_response_code(400);
            $error = $exception->getMessage();
        }
    }
}

$toggles = array_filter(
    get_defined_constants(true)['user'] ?? [],
    static fn($value, $name) => str_starts_with($name, 'VULN_') && is_bool($value),
    ARRAY_FILTER_USE_BOTH
);
ksort($toggles);
$completed = $_SESSION['safe_lab_completed'] ?? [];
$selectedToggle = is_string($_POST['toggle'] ?? null) ? $_POST['toggle'] : '';
$page_title = 'Safe Vulnerability Simulations';
require __DIR__ . '/includes/header.php';
?>
<section class="card">
  <span class="detail-kicker">Contained training mode</span>
  <h1>Safe vulnerability simulations</h1>
  <p class="muted">Explore a simulation for every configured vulnerability switch. The exercises use fixed synthetic examples only: they do not execute attacks, query real shop records, read or write arbitrary files, run commands, or make network requests. A simulation demonstrates the security concept; it does not prove the live application is vulnerable.</p>
  <p><strong><?= count($completed) ?> / <?= count($toggles) ?></strong> simulations run in this browser session.</p>
  <?php if ($error !== null): ?><div class="error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
  <?php if ($simulation !== null): ?>
    <div class="success" role="status">
      <strong>Simulation complete: <?= htmlspecialchars(str_replace('_', ' ', substr($selectedToggle, 5)), ENT_QUOTES, 'UTF-8') ?></strong>
      <p><b>Domain:</b> <?= htmlspecialchars($simulation['domain'], ENT_QUOTES, 'UTF-8') ?></p>
      <p><b>Scenario:</b> <?= htmlspecialchars($simulation['scenario'], ENT_QUOTES, 'UTF-8') ?></p>
      <p><b>Observation:</b> <?= htmlspecialchars($simulation['observation'], ENT_QUOTES, 'UTF-8') ?></p>
      <p><b>Check:</b> <?= $simulation['passed'] ? 'PASS' : 'FAIL' ?></p>
      <p><b>Remediation:</b> <?= htmlspecialchars($simulation['remediation'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
  <?php endif; ?>
  <label for="lab-filter">Filter simulations</label>
  <input id="lab-filter" type="search" placeholder="Search by vulnerability name">
  <div class="product-grid" id="safe-lab-list">
    <?php foreach ($toggles as $toggle => $enabled): ?>
      <article class="product safe-lab-card" data-search="<?= htmlspecialchars(strtolower($toggle), ENT_QUOTES, 'UTF-8') ?>">
        <span class="detail-kicker"><?= $enabled ? 'Configured on' : 'Configured off' ?></span>
        <h3><?= htmlspecialchars(str_replace('_', ' ', substr($toggle, 5)), ENT_QUOTES, 'UTF-8') ?></h3>
        <p class="muted"><?= isset($completed[$toggle]) ? 'Simulation run in this session.' : 'Contained simulation available.' ?></p>
        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['safe_lab_csrf'], ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="toggle" value="<?= htmlspecialchars($toggle, ENT_QUOTES, 'UTF-8') ?>">
          <button type="submit">Run safe simulation</button>
        </form>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<script>
document.getElementById('lab-filter').addEventListener('input', (event) => {
  const query = event.target.value.trim().toLowerCase();
  document.querySelectorAll('.safe-lab-card').forEach((card) => {
    card.hidden = !card.dataset.search.includes(query);
  });
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
