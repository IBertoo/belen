<?php
require_once __DIR__ . '/config.php';

function db(): PDO {
  static $pdo = null;
  global $CONFIG;
  if ($pdo === null) {
    $dsn = 'mysql:host=' . $CONFIG['db_host'] . ';dbname=' . $CONFIG['db_name'] . ';charset=utf8mb4';
    $pdo = new PDO($dsn, $CONFIG['db_user'], $CONFIG['db_pass'], [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
  }
  return $pdo;
}

function csrf_token(): string {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}

function csrf_validate(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ok = isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']);
    if (!$ok) {
      http_response_code(400);
      exit('CSRF token inválido');
    }
  }
}