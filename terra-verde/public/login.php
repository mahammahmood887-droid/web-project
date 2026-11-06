<?php
require __DIR__ . '/../config/config.php'; guest_only();
$error = ''; $flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim($_POST['email'] ?? ''));
  $pass  = $_POST['password'] ?? '';
  $ip    = $_SERVER['REMOTE_ADDR'] ?? '';
  if (!csrf_ok()) { $error = 'Your session expired. Please try again.'; }
  else {
    db()->exec("DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY");
    $q = db()->prepare("SELECT COALESCE(SUM(email=? AND ip=?),0) AS pair, COALESCE(SUM(ip=?),0) AS by_ip, COALESCE(SUM(email=?),0) AS by_email
                        FROM login_attempts WHERE attempted_at > NOW() - INTERVAL 15 MINUTE");
    $q->execute([$email, $ip, $ip, $email]); $n = $q->fetch();
    if ($n['pair'] >= 5 || $n['by_ip'] >= 20 || $n['by_email'] >= 30) { $error = 'Too many attempts. Try again in 15 minutes.'; }
    else {
      $q = db()->prepare("SELECT id, password_hash, role FROM users WHERE email=?");
      $q->execute([$email]); $u = $q->fetch();
      if ($u && password_verify($pass, $u['password_hash'])) {
        login_user($u);
        db()->prepare("DELETE FROM login_attempts WHERE email=? AND ip=?")->execute([$email, $ip]);
        header('Location: ' . home_for_role($u['role'])); exit;
      }
      db()->prepare("INSERT INTO login_attempts (email, ip) VALUES (?,?)")->execute([$email, $ip]);
      $error = 'Email or password is incorrect.';
    }
  }
}
?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in — Terra Verde</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head><body>
<div class="auth">
  <aside class="auth-art">
    <div class="blob"></div>
    <svg viewBox="0 0 400 420"><ellipse cx="200" cy="150" rx="120" ry="140" fill="#FEECD8" opacity=".9"/>
    <path d="M200 60 C260 100 280 200 220 260 C170 310 120 260 130 190 C138 130 160 80 200 60Z" fill="#DCA278"/>
    <circle cx="150" cy="320" r="46" fill="#FFF9E2"/><path d="M60 200 Q100 150 150 190 Q120 240 60 200Z" fill="#A9B387" opacity=".8"/></svg>
    <div class="leaf-note">
      <strong>&lt;40</strong>bottles in every batch, poured weekly
    </div>
  </aside>
  <main class="auth-main">
    <a class="brand" href="index.php"><svg class="mark" viewBox="0 0 26 26" fill="none">
      <path d="M13 2C13 2 4 6 4 15C4 20 8 24 13 24C18 24 22 20 22 15C22 6 13 2 13 2Z" fill="#A9B387"/>
      <path d="M13 4V22" stroke="#3B3626" stroke-width="1" opacity="0.35"/>
    </svg>Terra Verde</a>
    <h1>Welcome back to the garden.</h1>
    <p class="auth-sub">Sign in to reorder your formulas and see this week's batch.</p>
    <?php if ($flash): ?>
      <div class="alert ok"><?= e($flash) ?></div>
      <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert error" role="alert"><?= e($error) ?></div>
      <?php endif; ?>
    <form class="auth-form" method="post" data-mode="login" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div class="field"><label for="email">Email</label>
        <input type="email" id="email" name="email" autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>" required>
        <div class="err"></div></div>
      <div class="field"><label for="password">Password</label>
        <div class="pw-wrap"><input type="password" id="password" name="password" autocomplete="current-password" required>
        <button type="button" class="pw-toggle">Show</button>
      </div><div class="err"></div></div>
      <button class="btn-primary" type="submit">Sign in</button>
    </form>
    <p class="switch">New here? <a href="register.php">Create an account</a></p>
  </main>
</div>
<script src="script.js"></script>
</body></html>
