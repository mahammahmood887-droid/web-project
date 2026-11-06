<?php
require __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'subscribe') {
  header('Content-Type: application/json');
  if (!csrf_ok()) 
    { http_response_code(400); 
  echo '{"error":"bad request"}'; exit; }
  $email = strtolower(trim($_POST['email'] ?? ''));
  if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(422); echo '{"error":"invalid email"}'; exit; }
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

$isAdmin  = !empty($_SESSION['uid']) && ($_SESSION['role'] ?? '') === 'admin';
$loggedIn = !empty($_SESSION['uid']) && !$isAdmin;
$products = db()->query("SELECT p.id,p.name,p.concern,p.concern_label,p.price,p.card_color,p.icon_svg, f.name AS farm, f.location AS farm_location
                         FROM products p LEFT JOIN farms f ON f.id=p.farm_id WHERE p.is_active=1 ORDER BY p.id")->fetchAll();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Terra Verde — Small-batch botanical skincare</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">

</head>
<body>

<header class="site-nav">
  <div class="nav-row">
    <div class="brand">
      <svg class="mark" viewBox="0 0 26 26" fill="none"><path d="M13 2C13 2 4 6 4 15C4 20 8 24 13 24C18 24 22 20 22 15C22 6 13 2 13 2Z" fill="#A9B387"/><path d="M13 4V22" stroke="#3B3626" stroke-width="1" opacity="0.35"/></svg>
      Terra Verde
    </div>
    <nav>
      <ul class="nav-links">
        <li><a href="#products" data-nav>Shop</a></li>
        <li><a href="#ritual" data-nav>Find your ritual</a></li>
        <li><a href="#stories" data-nav>Stories</a></li>
        <li><a href="#faq" data-nav>Questions</a></li>
      </ul>
    </nav>
    <?php if ($isAdmin): ?><a class="nav-cta" href="auth.php" style="text-decoration:none">Admin panel</a>
    <?php elseif ($loggedIn): ?><a class="nav-cta" href="index.php?page=bag" style="text-decoration:none">Bag <span class="bag-n">0</span></a>
    <?php else: ?><a class="nav-cta" href="login.php" style="text-decoration:none">Sign in</a><?php endif; ?>
    <button class="hamburger" id="hamburgerBtn" aria-label="Open menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
  <div class="mobile-menu" id="mobileMenu">
    <a href="#products">Shop</a>
    <a href="#ritual">Find your ritual</a>
    <a href="#stories">Stories</a>
    <a href="#faq">Questions</a>
    <?php if ($isAdmin): ?><a href="auth.php">Admin panel</a>
    <?php elseif ($loggedIn): ?><a href="index.php?page=bag">Bag (<span class="bag-n">0</span>)</a><a href="home.php">My account</a>
    <?php else: ?><a href="login.php">Sign in</a><?php endif; ?>
  </div>
</header>

<section class="hero">
  <div class="wrap">
    <div>
      <p class="hero-eyebrow">Small-batch, plant-based, made in Portland</p>
      <h1>Skincare that still smells like the garden it came from.</h1>
      <p class="hero-copy">We press, steep, and cold-blend every formula in batches under forty bottles — eucalyptus, oat, and clay sourced from three family farms we visit every season.</p>
      <div class="hero-actions">
        <button class="btn-primary" onclick="document.getElementById('products').scrollIntoView()">Shop the collection</button>
        <button class="btn-ghost" onclick="document.getElementById('ritual').scrollIntoView()">
          <span class="circle">→</span> Find your ritual
        </button>
      </div>
    </div>
    <div class="hero-visual">
      <div class="blob"></div>
      <svg viewBox="0 0 400 420">
        <ellipse cx="200" cy="150" rx="120" ry="140" fill="#FEECD8" opacity="0.9"/>
        <path d="M200 60 C260 100 280 200 220 260 C170 310 120 260 130 190 C138 130 160 80 200 60Z" fill="#DCA278"/>
        <circle cx="150" cy="320" r="46" fill="#E8ECCC"/>
        <path d="M60 200 Q100 150 150 190 Q120 240 60 200Z" fill="#A9B387" opacity="0.8"/>
      </svg>
      <div class="leaf-note">
        <strong>92%</strong>
        of ingredients grown within 60 miles of our studio
      </div>
    </div>
  </div>
</section>

<div class="strip">
  <div class="wrap">
    <span><i class="dot"></i>Cold-blended weekly</span>
    <span><i class="dot"></i>Glass, not plastic</span>
    <span><i class="dot"></i>Unscented option on every product</span>
    <span><i class="dot"></i>Ships Tuesdays &amp; Fridays</span>
  </div>
</div>

<section id="products">
  <div class="wrap">
    <div class="section-head">
      <h2>Fifteen formulas, one shared soil</h2>
      <p>Filter by what your skin is asking for this week. Every jar is labeled with the farm it came from.</p>
    </div>
    <div class="filters" id="filterBar">
      <button class="filter-btn active" data-filter="all">Everything</button>
      <button class="filter-btn" data-filter="clarify">Clarifying</button>
      <button class="filter-btn" data-filter="glow">Glow</button>
      <button class="filter-btn" data-filter="calm">Calming</button>
      <button class="filter-btn" data-filter="nourish">Nourishing</button>
    </div>
    <div class="product-grid" id="productGrid">
    </div>
  </div>
</section>

<section id="ritual">
  <div class="wrap">
    <div class="ritual">
      <div class="ritual-grid">
        <div>
          <h2>What is your skin asking for today?</h2>
          <p class="ritual-copy">Three quick questions and we'll point you to the one formula worth starting with — no quiz-funnel upsell, just a straight answer.</p>
          <div class="step-track" id="stepTrack">
            <span><i></i></span><span><i></i></span><span><i></i></span>
          </div>
        </div>
        <div class="quiz-card" id="quizCard"></div>
      </div>
    </div>
  </div>
</section>

<section id="stories">
  <div class="wrap">
    <div class="section-head" style="justify-content:center;text-align:center;">
      <div style="margin:0 auto;">
        <h2>From people who've run out and reordered</h2>
      </div>
    </div>
    <div class="testimonial-wrap">
      <div class="testimonial-track">
        <div class="testimonial-slides" id="tSlides">
          <div class="t-slide">
            <p class="quote">The clarifying mist is the first toner that hasn't made my skin feel tight. I keep it on my desk, not just the bathroom.</p>
            <p class="who">Priya N. — using Eucalyptus Mist since 2023</p>
          </div>
          <div class="t-slide">
            <p class="quote">My dermatologist actually asked what I was using. It was the oat cream. I didn't tell her it cost less than my old moisturizer.</p>
            <p class="who">Marcus T. — Ivory Oat Cream</p>
          </div>
          <div class="t-slide">
            <p class="quote">I'm sensitive to almost everything scented. The calm balm is the only thing my face hasn't rebelled against in a decade.</p>
            <p class="who">Sana K. — Sage Calm Balm</p>
          </div>
          <div class="t-slide">
            <p class="quote">The renewal mask smells like being handed a cup of tea in someone's kitchen. That's not a metaphor I use lightly.</p>
            <p class="who">Owen R. — Pistachio Renewal Mask</p>
          </div>
        </div>
      </div>
      <div class="t-controls">
        <button class="t-arrow" id="tPrev" aria-label="Previous story">←</button>
        <div class="t-dots" id="tDots"></div>
        <button class="t-arrow" id="tNext" aria-label="Next story">→</button>
      </div>
    </div>
  </div>
</section>

<section id="faq">
  <div class="wrap">
    <div class="section-head" style="justify-content:center;text-align:center;">
      <div style="margin:0 auto;">
        <h2>Questions before you order</h2>
      </div>
    </div>
    <div class="faq-list" id="faqList"></div>
  </div>
</section>

<section>
  <div class="wrap">
    <div class="newsletter">
      <h2>Get the next harvest report before we sell out of it</h2>
      <form class="newsletter-form" id="newsletterForm" novalidate>
        <input type="email" id="newsletterEmail" placeholder="you@example.com" required>
        <button type="submit">Notify me</button>
        <span class="form-msg" id="formMsg"></span>
      </form>
    </div>
  </div>
</section>

<footer>
  <div class="wrap">
    <div class="footer-grid">
      <div class="footer-col" style="max-width:280px;">
        <h4>Terra Verde</h4>
        <p style="color:var(--ink-soft);font-size:0.92rem;">Small-batch botanical skincare, blended in Portland from ingredients grown within 60 miles of the studio.</p>
      </div>
      <div class="footer-col">
        <h4>Shop</h4>
        <a href="#products">All formulas</a>
        <a href="#ritual">Find your ritual</a>
      </div>
      <div class="footer-col">
        <h4>Studio</h4>
        <a href="#stories">Stories</a>
        <a href="#faq">Questions</a>
      </div>
      <div class="footer-col">
        <h4>Say hello</h4>
        <a href="mailto:hello@terraverde.studio">hello@terraverde.studio</a>
        <a href="#">Instagram</a>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© 2026 Terra Verde Botanicals</span>
      <span>Portland, Oregon</span>
    </div>
  </div>
</footer>

<button class="back-to-top" id="backToTop" aria-label="Back to top">↑</button>

<script>window.TV = {loggedIn: <?= $loggedIn ? "true" : "false" ?>, products: <?= json_encode($products, JSON_HEX_TAG) ?>, csrf: <?= json_encode(csrf_token()) ?>};</script>
<script src="script.js"></script>
</body>
</html>