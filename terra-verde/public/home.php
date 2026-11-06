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
?>