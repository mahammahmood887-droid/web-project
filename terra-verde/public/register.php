<?php
require __DIR__ . '/../config/config.php'; guest_only();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['full_name'] ?? '');
  $email = strtolower(trim($_POST['email'] ?? ''));
  $pass = $_POST['password'] ?? '';
  $concern = in_array($_POST['concern'] ?? '', ['clarify','glow','calm','nourish'], true) ? $_POST['concern'] : null;
  $news = !empty($_POST['newsletter']);

  if (!csrf_ok()) $errors[] = 'Your session expired. Please try again.';
  if (mb_strlen($name) < 2) $errors[] = 'Please enter your name.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "That email doesn't look quite right.";
  if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
  if ($pass !== ($_POST['confirm'] ?? '')) $errors[] = "Passwords don't match.";

  if (!$errors) {
    $q = db()->prepare("SELECT id FROM users WHERE email=?"); $q->execute([$email]);
    if ($q->fetch()) $errors[] = 'An account with that email already exists.';
  }
  if (!$errors) {
    $pdo = db();
    try {
      $pdo->beginTransaction();
      $pdo->prepare("INSERT INTO users (full_name,email,password_hash,skin_concern) VALUES (?,?,?,?)")
          ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $concern]);
      if ($news) $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?) ON DUPLICATE KEY UPDATE unsubscribed_at = NULL")->execute([$email]);
      $pdo->commit();
      $_SESSION['flash'] = 'Account created. Sign in to start.';
      header('Location: login.php'); exit;
    } catch (PDOException $ex) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      if ($ex->getCode() === '23000') $errors[] = 'An account with that email already exists.';
      else throw $ex;
    }
  }
}
$sel = $_POST['concern'] ?? '';
?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create account — Terra Verde</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head><body>
<div class="auth">
  <aside class="auth-art">
    <div class="blob"></div>
    <svg viewBox="0 0 400 420"><ellipse cx="200" cy="150" rx="120" ry="140" fill="#FEECD8" opacity=".9"/><path d="M200 60 C260 100 280 200 220 260 C170 310 120 260 130 190 C138 130 160 80 200 60Z" fill="#DCA278"/><circle cx="150" cy="320" r="46" fill="#FFF9E2"/><path d="M60 200 Q100 150 150 190 Q120 240 60 200Z" fill="#A9B387" opacity=".8"/></svg>
    <div class="leaf-note"><strong>92%</strong>of ingredients grown within 60 miles of our studio</div>
  </aside>
  <main class="auth-main">
    <a class="brand" href="index.php"><svg class="mark" viewBox="0 0 26 26" fill="none"><path d="M13 2C13 2 4 6 4 15C4 20 8 24 13 24C18 24 22 20 22 15C22 6 13 2 13 2Z" fill="#A9B387"/><path d="M13 4V22" stroke="#3B3626" stroke-width="1" opacity="0.35"/></svg>Terra Verde</a>
    <h1>Start your ritual.</h1>
    <p class="auth-sub">Create an account to save your formulas, track orders, and get the harvest report first.</p>
    <?php foreach ($errors as $m): ?><div class="alert error" role="alert"><?= e($m) ?></div><?php endforeach; ?>
    <form class="auth-form" method="post" data-mode="register" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div class="field"><label for="full_name">Full name</label>
        <input type="text" id="full_name" name="full_name" autocomplete="name" value="<?= e($_POST['full_name'] ?? '') ?>" required><div class="err"></div></div>
      <div class="field"><label for="email">Email</label>
        <input type="email" id="email" name="email" autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>" required><div class="err"></div></div>
      <div class="field"><label for="password">Password</label>
        <div class="pw-wrap"><input type="password" id="password" name="password" autocomplete="new-password" required><button type="button" class="pw-toggle">Show</button></div>
        <div class="strength"><i></i></div><div class="err"></div></div>
      <div class="field"><label for="confirm">Confirm password</label>
        <input type="password" id="confirm" name="confirm" autocomplete="new-password" required><div class="err"></div></div>
      <div class="field"><label>What is your skin asking for? (optional)</label>
        <div class="concern-pick">
          <?php foreach (['clarify'=>'Clarifying','glow'=>'Glow','calm'=>'Calming','nourish'=>'Nourishing'] as $k=>$l): ?>
            <span><input type="radio" id="c-<?= $k ?>" name="concern" value="<?= $k ?>" <?= $sel===$k?'checked':'' ?>><label for="c-<?= $k ?>"><?= $l ?></label></span>
          <?php endforeach; ?>
        </div></div>
      <label class="check"><input type="checkbox" name="newsletter" checked> Send me the harvest report</label>
      <button class="btn-primary" type="submit">Create account</button>
    </form>
    <p class="switch">Already have an account? <a href="login.php">Sign in</a></p>
  </main>
</div>
<script src="script.js"></script>
</body></html>
