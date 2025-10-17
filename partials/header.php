
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Importadora "BELEN"</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="/favicon.webp">
  <link rel="shortcut icon" type="image/x-icon" href="/favicon.webp">
<link rel="stylesheet" href="../css/catalog.css">
        <!-- Google Fonts for handwritten style -->
  <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom mb-4">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="/">
      <img src="/uploads/loguito5.webp" alt="Logo" width="150" height="50">
      <b><span class="handwritten-style">La Herramienta Perfecta, </span>
    <span class="handwritten-style">Para Cada Trabajo.</span></b>
    </a>


    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item"><a class="nav-link tamanio" href="/">Inicio</a></li>
        <li class="nav-item"><a class="nav-link tamanio" href="/como_comprar.php">Como Comprar</a></li>
        <li class="nav-item"><a class="nav-link tamanio" href="/nosotros.php">Nosotros</a></li>
        <li class="nav-item"><a class="nav-link tamanio" href="/contacto.php">Contacto</a></li>
       <?php 
        if (!empty($_SESSION['user'])) {?>
          <li class="nav-item"><a class="nav-link tamanio" href="/logout.php">LogOut</a></li>
          <?php 
        }else {
?>
          <li class="nav-item"><a class="nav-link tamanio" href="/login.php">LogIn</a></li>
          <?php         }
        ?>
        <li class="nav-item">
          <a href="/cart.php" class="btn btn-highgreen">Carrito</a>
          <a class="btn btn-highlight" href="/index.php">Catálogo</a>
        </li>
      </ul>
    </div>


  </div>
</nav>
      <?php if (!empty($_SESSION['user'])): ?>
  <!-- Segundo navbar (solo visible para usuarios logueados) -->
  <nav class="navbar navbar-expand-lg navbar-light bg-light border-top border-bottom mb-3">
    <div class="container-fluid">
      <span class="navbar-text fw-bold me-3">Panel de administración:</span>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="adminNav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link tamanio2" href="/admin/">🏠 Admin</a></li>
          <li class="nav-item"><a class="nav-link tamanio2" href="/admin/products.php">📦 Productos</a></li>
          <li class="nav-item"><a class="nav-link tamanio2" href="/admin/orders.php">🧾 Pedidos</a></li>
          <li class="nav-item"><a class="nav-link tamanio2" href="/admin/categories.php">Categorías</a></li>
        </ul>

        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <span class="navbar-text text-muted me-2">
              👤 <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Administrador') ?>
            </span>
          </li>
          <li class="nav-item">
            <a class="btn btn-outline-danger btn-sm" href="/logout.php">Cerrar sesión</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
<?php endif; ?>
<div class="container">