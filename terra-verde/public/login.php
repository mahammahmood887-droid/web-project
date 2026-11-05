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
?>