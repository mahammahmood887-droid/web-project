<?php
require __DIR__ . '/../config/config.php'; require_customer();
$uid = $_SESSION['uid'];

$q = db()->prepare("SELECT full_name, email, skin_concern, created_at FROM users WHERE id=?");
$q->execute([$uid]); $user = $q->fetch();
if (!$user) {
  $_SESSION = []; session_destroy(); header('Location: login.php'); exit;
}

$q = db()->prepare("SELECT COUNT(*) FROM newsletter_subscribers WHERE email=? AND unsubscribed_at IS NULL");
$q->execute([$user['email']]); $subscribed = (bool)$q->fetchColumn();

$products = db()->query("SELECT p.id, p.name, p.concern, p.concern_label, p.price, p.card_color, p.icon_svg, f.name AS farm
                         FROM products p LEFT JOIN farms f ON f.id=p.farm_id WHERE p.is_active=1 ORDER BY p.id")->fetchAll();

$match = null;
if ($user['skin_concern']) {
  $q = db()->prepare("SELECT name, concern_label FROM products WHERE concern=? AND is_active=1 ORDER BY price LIMIT 1");
  $q->execute([$user['skin_concern']]); $match = $q->fetch();
}
$labels = ['clarify'=>'Clarifying', 'glow'=>'Glow', 'calm'=>'Calming', 'nourish'=>'Nourishing'];
$first = explode(' ', trim($user['full_name']))[0];
$catalog = array_map(fn($p) => ['id' => (int)$p['id'], 'name' => $p['name'], 'price' => $p['price'], 'concern' => $p['concern'],
  'concern_label' => $p['concern_label'], 'card_color' => $p['card_color'], 'icon_svg' => $p['icon_svg'], 'farm' => $p['farm']], $products);
?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Home — Terra Verde</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
<style>
.account{background:var(--pistachio);border-radius:22px;padding:26px 30px;display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}
.account dt{font-size:.85rem;color:var(--ink-soft);margin-bottom:4px;}
.account dd{margin:0;font-weight:500;word-break:break-word;}
@media (max-width:920px){.account{grid-template-columns:1fr;}}
</style>
</head><body>
<header class="site-nav"><div class="nav-row">
  <a class="brand" href="home.php" style="text-decoration:none"><svg class="mark" viewBox="0 0 26 26" fill="none"><path d="M13 2C13 2 4 6 4 15C4 20 8 24 13 24C18 24 22 20 22 15C22 6 13 2 13 2Z" fill="#A9B387"/><path d="M13 4V22" stroke="#3B3626" stroke-width="1" opacity="0.35"/></svg>Terra Verde</a>
  <div class="nav-user">
    <a href="index.php?page=bag">Bag <span class="bag-n">0</span></a>
    <span class="hide-sm"><?= e($user['full_name']) ?></span>
    <a href="index.php">Shop page</a>
    <a href="logout.php">Sign out</a>
  </div>
</div></header>

<main>
<section class="welcome"><div class="wrap">
  <h1>Good to see you, <?= e($first) ?>.</h1>
  <p>This week's batch is poured and ships Tuesday and Friday. Here's where your ritual stands.</p>
  <p class="ship-pill" id="shipNote"></p>
  <div class="stats">
    <div class="stat"><strong><?= e(date('M j, Y', strtotime($user['created_at']))) ?></strong><span>Member since</span></div>
    <div class="stat"><strong><?= $user['skin_concern'] ? e($labels[$user['skin_concern']]) : 'Not set' ?></strong><span>Skin concern</span></div>
    <div class="stat"><strong class="bag-n" style="background:none;color:inherit;padding:0;margin:0;font-size:2rem;text-align:left">0</strong><span>Items in your bag</span></div>
  </div>
</div></section>

<section style="padding:0 0 50px"><div class="wrap">
  <dl class="account">
    <div><dt>Name</dt><dd><?= e($user['full_name']) ?></dd></div>
    <div><dt>Email</dt><dd><?= e($user['email']) ?></dd></div>
    <div><dt>Harvest report emails</dt><dd><?= $subscribed ? 'Subscribed' : 'Not subscribed' ?></dd></div>
  </dl>
</div></section>

<?php if ($match): ?>
<section style="padding:0 0 60px"><div class="wrap"><div class="picked">
  <div><p style="color:var(--clay-deep);font-weight:500;margin-bottom:8px">Your ritual match</p>
    <h2><?= e($match['name']) ?></h2><p style="color:#4b4a34;margin-top:8px"><?= e($match['concern_label']) ?></p></div>
  <a class="btn-primary" href="index.php#ritual" style="text-decoration:none">Retake the quiz</a>
</div></div></section>
<?php endif; ?>

<section style="padding:0 0 50px"><div class="wrap"><div class="tip-card">
  <div><p class="tip-label">Ritual tip of the day</p><p class="tip-text" id="tipText"></p></div>
  <button class="btn-ghost" id="tipNext" type="button"><span class="circle">→</span> Another tip</button>
</div></div></section>

<section id="ordersSection" hidden style="padding:0 0 50px"><div class="wrap">
  <div class="section-head" style="margin-bottom:24px"><h2>Order again</h2><p>Your recent orders from this browser, one tap to refill your bag.</p></div>
  <div id="ordersList"></div>
</div></section>

<section id="savedSection" hidden style="padding:0 0 50px"><div class="wrap">
  <div class="section-head" style="margin-bottom:24px"><h2>Saved for later</h2><p>Tap the heart on any product to keep it here.</p></div>
  <div id="savedList"></div>
</div></section>

<section style="padding:20px 0 90px" id="products"><div class="wrap">
  <div class="section-head"><h2>This week's batch</h2><p>Every jar is labeled with the farm it came from.</p></div>
  <div class="product-grid">
    <?php foreach ($products as $p): ?>
    <div class="product-card" style="--card-bg:<?= e($p['card_color']) ?>">
      <svg class="icon" viewBox="0 0 52 52"><?= $p['icon_svg'] ?></svg>
      <h3><?= e($p['name']) ?></h3>
      <p class="concern"><?= e($p['concern_label']) ?><?= $p['farm'] ? ' · '.e($p['farm']) : '' ?></p>
      <div class="price-row"><span class="price">$<?= number_format($p['price'], 0) ?></span>
        <button class="add-btn" data-id="<?= (int)$p['id'] ?>" aria-label="Add <?= e($p['name']) ?> to bag">+</button></div>
    </div>
    <?php endforeach; ?>
  </div>
</div></section>
</main>

<script>window.TV = {loggedIn: true, concern: <?= json_encode($user['skin_concern']) ?>, products: <?= json_encode($catalog, JSON_HEX_TAG) ?>};</script>
<script src="script.js"></script>
</body></html>
