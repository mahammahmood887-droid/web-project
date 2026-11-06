<?php
function redirect(string $url): void {
  header('Location: ' . $url);
  exit;
}

function auth_user(): ?array {
  static $user = false;
  if ($user !== false) return $user;
  if (empty($_SESSION['uid'])) return $user = null;

  $q = db()->prepare('SELECT id, full_name, email, role FROM users WHERE id=?');
  $q->execute([$_SESSION['uid']]);
  $user = $q->fetch() ?: null;

  if ($user) $_SESSION['role'] = $user['role'];
  else $_SESSION = [];
  return $user;
}

function home_for_role(string $role): string {
  return $role === 'admin' ? 'auth.php' : 'home.php';
}

function login_user(array $user): void {
  session_regenerate_id(true);
  $_SESSION['uid']  = (int)$user['id'];
  $_SESSION['role'] = $user['role'];
}

function require_login(): void {
  if (!auth_user()) redirect('login.php');
}

function require_customer(): void {
  require_login();
  if (auth_user()['role'] === 'admin') redirect('auth.php');
}

function require_admin(): void {
  $user = auth_user();
  if (!$user) redirect('login.php');
  if ($user['role'] !== 'admin') redirect('home.php');
}

function guest_only(): void {
  if (!empty($_SESSION['uid'])) redirect(home_for_role($_SESSION['role'] ?? 'customer'));
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__) return;

require_once __DIR__ . '/../config/config.php';
require_admin();
$me = auth_user();

function admin_save_product(array $farms, array $concerns, array $colors): array {
  $id      = (int)($_POST['id'] ?? 0);
  $name    = trim($_POST['name'] ?? '');
  $label   = trim($_POST['concern_label'] ?? '');
  $concern = $_POST['concern'] ?? '';
  $color   = $_POST['card_color'] ?? '';
  $price   = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
  $farm    = (int)($_POST['farm_id'] ?? 0);
  if ($farm && !in_array($farm, array_column($farms, 'id'))) $farm = 0;

  $invalid = $name === '' || mb_strlen($name) > 120
          || $label === '' || mb_strlen($label) > 120
          || !isset($concerns[$concern]) || !isset($colors[$color])
          || $price === false || $price <= 0 || $price > 9999;
  if ($invalid) {
    return ['err', 'Please fill every product field with valid values.', $id ? '&edit=' . $id : ''];
  }

  if ($id) {
    db()->prepare('UPDATE products SET name=?, farm_id=?, concern=?, concern_label=?, price=?, card_color=? WHERE id=?')
        ->execute([$name, $farm ?: null, $concern, $label, $price, $color, $id]);
    return ['ok', 'Product updated.'];
  }

  $icon = '<circle cx="26" cy="26" r="24" fill="#A9B387"/>';
  db()->prepare('INSERT INTO products (farm_id,name,concern,concern_label,price,card_color,icon_svg) VALUES (?,?,?,?,?,?,?)')
      ->execute([$farm ?: null, $name, $concern, $label, $price, $color, $icon]);
  return ['ok', 'Product added.'];
}

function admin_toggle_product(): array {
  db()->prepare('UPDATE products SET is_active = 1 - is_active WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
  return ['ok', 'Product visibility changed.'];
}

function admin_toggle_role(int $myId): array {
  $id = (int)($_POST['id'] ?? 0);
  if ($id === $myId) return ['err', 'You cannot change your own role.'];
  db()->prepare("UPDATE users SET role = IF(role='admin','customer','admin') WHERE id=?")->execute([$id]);
  return ['ok', 'Role changed.'];
}

function admin_delete_user(int $myId): array {
  $id = (int)($_POST['id'] ?? 0);
  if ($id === $myId) return ['err', 'You cannot delete your own account.'];
  db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
  return ['ok', 'User deleted.'];
}

function admin_btn(string $do, int $id, string $label, string $csrf, string $extra = ''): string {
  return '<form method="post" style="display:inline"' . $extra . '><input type="hidden" name="csrf" value="' . e($csrf) . '">'
       . '<input type="hidden" name="do" value="' . e($do) . '"><input type="hidden" name="id" value="' . $id . '">'
       . '<button class="link-btn" type="submit">' . e($label) . '</button></form>';
}

$tabs     = ['dashboard' => 'Dashboard', 'products' => 'Products', 'users' => 'Users', 'subscribers' => 'Newsletter'];
$concerns = ['clarify' => 'Clarifying', 'glow' => 'Glow', 'calm' => 'Calming', 'nourish' => 'Nourishing'];
$colors   = ['var(--peach)' => 'Peach', 'var(--ivory)' => 'Ivory', 'var(--pistachio)' => 'Pistachio', 'var(--eucalyptus)' => 'Eucalyptus', 'var(--clay)' => 'Clay'];
$page     = isset($tabs[$_GET['page'] ?? '']) ? $_GET['page'] : 'dashboard';
$farms    = db()->query('SELECT id, name FROM farms ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_ok()) {
    $result = ['err', 'Your session expired. Please try again.'];
  } else {
    switch ($_POST['do'] ?? '') {
      case 'product_save':   $result = admin_save_product($farms, $concerns, $colors); break;
      case 'product_toggle': $result = admin_toggle_product(); break;
      case 'user_role':      $result = admin_toggle_role((int)$me['id']); break;
      case 'user_delete':    $result = admin_delete_user((int)$me['id']); break;
      default:               $result = ['err', 'Something went wrong.'];
    }
  }
  $_SESSION['admin_msg'] = [$result[0], $result[1]];
  redirect('auth.php?page=' . $page . ($result[2] ?? ''));
}

$flash = $_SESSION['admin_msg'] ?? null;
unset($_SESSION['admin_msg']);
$csrf = csrf_token();

if ($page === 'dashboard') {
  $count = fn(string $sql) => (int)db()->query($sql)->fetchColumn();
  $stats = [
    'Customers'       => $count("SELECT COUNT(*) FROM users WHERE role='customer'"),
    'Admins'          => $count("SELECT COUNT(*) FROM users WHERE role='admin'"),
    'Active products' => $count('SELECT COUNT(*) FROM products WHERE is_active=1'),
    'Hidden products' => $count('SELECT COUNT(*) FROM products WHERE is_active=0'),
    'Subscribers'     => $count('SELECT COUNT(*) FROM newsletter_subscribers WHERE unsubscribed_at IS NULL'),
    'Farms'           => $count('SELECT COUNT(*) FROM farms'),
  ];
  $recent = db()->query('SELECT full_name, email, role, created_at FROM users ORDER BY id DESC LIMIT 5')->fetchAll();
}

if ($page === 'products') {
  $products = db()->query('SELECT p.*, f.name AS farm FROM products p LEFT JOIN farms f ON f.id=p.farm_id ORDER BY p.id')->fetchAll();
  $edit = null;
  if (!empty($_GET['edit'])) {
    foreach ($products as $p) {
      if ((int)$p['id'] === (int)$_GET['edit']) $edit = $p;
    }
  }
}

if ($page === 'users') {
  $users = db()->query('SELECT id, full_name, email, skin_concern, role, created_at FROM users ORDER BY id')->fetchAll();
}

if ($page === 'subscribers') {
  $subs = db()->query('SELECT email, subscribed_at, unsubscribed_at FROM newsletter_subscribers ORDER BY id DESC')->fetchAll();
}
?>