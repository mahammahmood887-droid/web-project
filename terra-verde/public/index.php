<?php
require __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'subscribe') {
  header('Content-Type: application/json');
  if (!csrf_ok()) { http_response_code(400); echo '{"error":"bad request"}'; exit; }
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
?>