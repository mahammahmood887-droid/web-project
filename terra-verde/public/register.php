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
?>