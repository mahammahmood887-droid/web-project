<?php
define('DB_HOST', getenv('TV_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('TV_DB_NAME') ?: 'terra_verde');
define('DB_USER', getenv('TV_DB_USER') ?: 'terra_user');
define('DB_PASS', getenv('TV_DB_PASS') !== false ? getenv('TV_DB_PASS') : 'password');

ini_set('display_errors', '0');
ini_set('log_errors', '1');
set_exception_handler(function (Throwable $t) {
  error_log((string)$t);
  if (!headers_sent()) http_response_code(500);

  if (($_POST['action'] ?? '') === 'subscribe')
  {
    if (!headers_sent()) header('Content-Type: application/json');
    echo '{"error":"server"}';
  } else {
    echo 'Something went wrong on our side. Please try again in a moment.';
  }
});

session_set_cookie_params(['httponly' => true,
 'samesite' => 'Lax',
  'secure' => !empty($_SERVER['HTTPS'])]);
session_start();

function db(): PDO {
  static $pdo;
  return $pdo ??= new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);
}
function e(?string $s): string 
{ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function csrf_token(): string 
{ return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_ok(): bool 
{ return hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? ''); }

require_once __DIR__ . '/../public/auth.php';
