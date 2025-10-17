<?php
require_once __DIR__ . '/middleware.php';

$pdo = db();
$count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($count > 10) {
  header('Location: /login.php');
  exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = trim($_POST['username'] ?? '');
  $p = $_POST['password'] ?? '';
  if ($u !== '' && $p !== '') {
    $hash = password_hash($p, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:u, :h)');
    $stmt->execute([':u' => $u, ':h' => $hash]);
    $msg = 'Usuario creado. Ya puedes iniciar sesión.';
  } else {
    $msg = 'Completa usuario y contraseña.';
  }
}
include __DIR__ . '/partials/header.php';
?>
<div class="row justify-content-center">
  <div class="col-sm-10 col-md-6 col-lg-5">
    <h1 class="mb-3">Instalación: crear admin</h1>
    <?php if ($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <div class="mb-3">
        <label class="form-label">Usuario</label>
        <input class="form-control" name="username" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input type="password" class="form-control" name="password" required>
      </div>
      <div class="d-grid">
        <button class="btn btn-success">Crear a