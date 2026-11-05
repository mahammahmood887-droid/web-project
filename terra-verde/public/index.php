<?php
require __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'subscribe') {
  header('Content-Type: application/json');
  if (!csrf_ok())
     { http_response_code(400); echo '{"error":"bad request"}'; exit; }
  $email = strtolower(trim($_POST['email'] ?? ''));
  if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) 
    { http_response_code(422);
   echo '{"error":"invalid email"}'; exit; 
   }
  db()->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?) ON DUPLICATE KEY UPDATE unsubscribed_at = NULL")->execute([$email]);
  echo '{"ok":true}';
  exit;
}

if (($_GET['page'] ?? '') === 'bag') {
  require_customer();
  $q = db()->prepare("SELECT full_name FROM users WHERE id=?"); $q->execute([$_SESSION['uid']]); $u = $q->fetch();
  if (!$u) { $_SESSION = []; session_destroy(); header('Location: login.php'); exit; }
  $products = db()->query("SELECT id, name, price, card_color, icon_svg FROM products WHERE is_active=1")->fetchAll();
  $first = explode(' ', trim($u['full_name']))[0];
?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your bag — Terra Verde</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
<style>
.bag-row{display:grid;grid-template-columns:64px 1fr auto auto;gap:20px;align-items:center;padding:18px 20px;border-radius:22px;background:var(--card-bg,var(--peach));margin-bottom:14px;}
.bag-row svg{width:44px;height:44px;}
.bag-row h3{font-size:1.15rem;}
.bag-row .each{color:var(--ink-soft);font-size:.85rem;}
.qty{display:flex;align-items:center;gap:8px;}
.qty input{width:60px;padding:9px 12px;border-radius:20px;border:1px solid var(--line);font:inherit;background:#fff;}
.link-btn{background:none;border:none;color:var(--ink-soft);font-size:.85rem;text-decoration:underline;}
.summary{display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap;margin-top:28px;padding:28px 32px;border-radius:28px;background:var(--eucalyptus);}
.summary strong{font-family:'Fraunces',serif;font-size:1.8rem;font-weight:500;}
.sum{background:var(--eucalyptus);border-radius:28px;padding:28px;max-width:460px;margin-top:24px}
.sum .ln{display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid rgba(59,54,38,.15);font-size:.95rem}
.sum .tot{display:flex;justify-content:space-between;margin-top:16px;font-family:'Fraunces',serif;font-size:1.4rem}
@media(max-width:600px){.bag-row{grid-template-columns:48px 1fr;}.bag-row .qty,.bag-row .line{grid-column:2;}}
</style>
</head><body>
<header class="site-nav"><div class="nav-row">
  <a class="brand" href="home.php" style="text-decoration:none"><svg class="mark" viewBox="0 0 26 26" fill="none"><path d="M13 2C13 2 4 6 4 15C4 20 8 24 13 24C18 24 22 20 22 15C22 6 13 2 13 2Z" fill="#A9B387"/><path d="M13 4V22" stroke="#3B3626" stroke-width="1" opacity="0.35"/></svg>Terra Verde</a>
  <div class="nav-user"><a href="home.php">Keep shopping</a><a href="logout.php">Sign out</a></div>
</div></header>

<main><section class="welcome"><div class="wrap" style="max-width:820px" id="bagPage">
  <h1>Your bag</h1>
  <p id="bagCount">Nothing here yet.</p>
  <div style="margin-top:32px" id="bagList"></div>
  <div class="sample-bar" id="sampleBar" hidden><p id="sampleText"></p><div class="track"><i id="sampleFill"></i></div></div>
  <div class="summary" id="bagSummary" hidden>
    <div><span style="color:#4b4a34">Total</span><br><strong id="bagTotal">$0.00</strong></div>
    <button class="btn-primary" type="button" id="placeOrder">Place order</button>
  </div>
  <a class="btn-primary" id="browse" href="home.php#products" style="text-decoration:none;display:inline-block;margin-top:12px">Browse this week's batch</a>
</div></section></main>

<script>window.TV = {loggedIn: true, first: <?= json_encode($first) ?>, products: <?= json_encode($products, JSON_HEX_TAG) ?>};</script>
<script src="script.js"></script>
</body></html>
<?php
  exit;
}
